# DooTask WebDAV 技术设计方案

> 状态：设计稿
> 适用主程序版本：1.8.89
> 范围：DooTask「我的文件」和「共享文件」
> 默认策略：管理员全局启用，用户使用独立 WebDAV 应用密码连接

## 1. 目标与结论

为 DooTask 文件系统提供标准 WebDAV 访问，使用户可以通过 Windows、macOS、Linux 和支持 WebDAV 的办公软件访问文件，同时保持以下行为与网页端一致：

- 文件和目录权限一致。
- 写入生成文件历史版本。
- 移动、重命名、删除触发现有消息推送和搜索同步。
- 共享文件保持现有所有者、创建者、只读、读写权限语义。
- 凭据可以独立创建、过期和撤销，不影响主账号登录。
- 并发写入遵循 WebDAV 锁和 HTTP 条件请求，避免静默覆盖。

WebDAV 暴露的是由 `files`、`file_contents`、`file_users` 组成的虚拟文件系统，不直接暴露 `public/uploads` 物理目录。

## 2. 范围边界

### 2.1 首版包含

- 根目录固定包含 `files/` 和 `shared/` 两个虚拟集合。
- `files/`：当前用户拥有的根文件及全部子级。
- `shared/`：其他用户明确共享给当前用户的顶层共享项及全部子级。
- 文件和目录的查询、下载、上传、创建、复制、移动、重命名、删除。
- WebDAV 排他写锁、ETag、条件请求和 Range 下载。
- 管理员开关、用户应用密码、撤销、审计和运维指标。
- 文件历史、WebSocket 通知、Manticore 搜索同步及回收站行为。

### 2.2 首版不包含

- 聊天附件、任务附件、项目协作文件聚合视图。
- 匿名链接和游客访问。
- CalDAV、CardDAV。
- 将 DooTask 作为外部 WebDAV 的客户端或存储后端。
- Windows 文件扩展属性、NTFS ACL 和 POSIX 权限的完全映射。
- 离线同步客户端；系统只提供服务端协议。

### 2.3 不允许的实现

- 不允许将 `public/uploads/file` 直接配置为 Nginx WebDAV 目录。
- 不允许通过 URL 参数携带主登录 token。
- 不允许直接使用用户账号密码作为 WebDAV 密码。
- 不允许 DAV 控制器复制一套文件业务逻辑。
- 不允许在静态属性、单例或全局变量中保存当前 DAV 用户、锁或请求路径。

## 3. 产品闭环

### 3.1 管理员流程

1. 管理员进入「系统设置 > 文件设置 > WebDAV」。
2. 开启 WebDAV，并选择允许范围：全员或指定成员。
3. 配置凭据数量、有效期、单文件上限、复制上限和审计保留天数。
4. 保存后，状态接口返回服务地址和当前运行能力。
5. 管理员可以查看连接用户、最近失败和写操作审计，并可停用某个用户的全部 WebDAV 凭据。
6. 全局关闭时，所有 DAV 请求立即返回 `503 Service Unavailable`，凭据保留，以便重新启用；管理员可另行执行凭据全部撤销。

### 3.2 用户开通流程

1. 用户进入文件页面，在右上角加号右侧点击圆形“更多”图标，在菜单中选择「WebDAV」。
2. 页面打开 WebDAV 管理弹窗，展示管理员是否启用、服务器地址、支持范围和已有凭据。
3. 用户点击「创建应用密码」，填写设备名称并选择有效期。
4. 服务端返回一次性的用户名和应用密码；应用密码此后不可再次读取。
5. 页面提供服务器地址、用户名和密码字段及复制按钮，同时提示必须使用 HTTPS。
6. 用户在客户端连接后，页面更新最后使用时间、IP 和客户端名称。
7. 用户可以撤销单个凭据；撤销后新请求立即失败，已有锁同步失效。

### 3.3 文件操作闭环

每个写请求必须按以下顺序完成：

1. 认证应用密码并检查全局、用户和凭据状态。
2. 规范化并解析 DAV 路径，确认路径不能越过虚拟根和共享边界。
3. 校验资源权限、锁 token、ETag 和目标冲突。
4. 将请求体流式写入临时文件，并在写入时计算大小和 SHA-256。
5. 调用统一文件领域服务执行数据库与物理文件操作。
6. 写入新 `FileContent` 版本并更新 `File` 元数据。
7. 触发现有 WebSocket 消息、Observer 和 Manticore 同步。
8. 写入 DAV 审计日志并返回标准 DAV 状态码。
9. 无论成功失败都关闭流并清理临时文件；异常遗留由定时清理兜底。

## 4. URL 与目录模型

### 4.1 服务地址

```text
https://{host}/dav/
```

根目录使用固定、不随语言变化的 URI 段：

```text
/dav/
├── files/
└── shared/
```

固定 ASCII URI 可以避免用户切换语言后挂载路径失效。前端说明可将其翻译为「我的文件」和「共享文件」。

### 4.2 `files/` 映射

- `/dav/files/` 映射当前用户 `pid = 0 AND userid = 当前用户` 的资源。
- 后续每一段按 `pid + 完整文件名` 解析。
- 完整文件名为 `name`，文件有扩展名时为 `name.ext`。
- 文件夹不得带文件扩展名语义，按 `type = folder` 判断。

### 4.3 `shared/` 映射

共享根的每个顶层项使用以下稳定且无冲突的 DAV 名称：

```text
{原完整名称} [#{共享根文件ID}]
```

示例：

```text
/dav/shared/产品资料 [#128]/设计/首页.fig
/dav/shared/预算表 [#356].xlsx
```

- `[#ID]` 只用于共享顶层 URI，子级保持原名称。
- `displayname` 属性返回原完整名称，不包含 `[#ID]`。
- 顶层 ID 防止不同所有者共享同名资源时产生歧义。
- 共享根重命名后 URI 名称变化，但 ID 保持不变；解析时必须同时验证 ID 和当前名称。旧路径返回 `404`，不做永久重定向，避免 DAV 客户端缓存错误。
- 用户自己共享出去的文件仍位于 `files/`，不在 `shared/` 重复展示。

### 4.4 路径规范化

- URL 路径按 UTF-8 解码，每段只解码一次。
- 拒绝非法 UTF-8、NUL、控制字符、`.`、`..`、空段和编码后的路径分隔符。
- 使用 Unicode NFC 作为比较前的规范形式，但数据库保存用户原始显示形式。
- 文件名继续禁止 `\\ / : * ? " < > |`。
- 新增 DAV 写入允许 1 至 200 个字符；现有网页端“至少 2 个字”的限制应同步改为至少 1 个字符，否则两种入口行为不一致。
- 路径比较遵循数据库当前排序规则；不得仅在 PHP 中做大小写敏感判断。
- 路径解析结果只可缓存于当前 `RequestContext`，不得跨请求缓存权限结果。

## 5. 协议能力

### 5.1 方法矩阵

| 方法 | 作用 | 首版行为 |
| --- | --- | --- |
| `OPTIONS` | 能力发现 | 返回 `DAV: 1, 2`、允许方法和 MS DAV 扩展头 |
| `PROPFIND` | 查询资源属性 | 支持 `Depth: 0/1`，对 `infinity` 返回 `403`，避免全树扫描 |
| `PROPPATCH` | 设置死属性 | 支持非保护属性，系统属性返回 `403` |
| `HEAD` | 文件元数据 | 与 GET 同头部，不返回内容 |
| `GET` | 下载文件 | 支持 Range、ETag、Last-Modified 和条件读取 |
| `PUT` | 新建或覆盖文件 | 流式写入；覆盖创建历史版本 |
| `MKCOL` | 创建文件夹 | 请求体非空返回 `415` |
| `COPY` | 复制资源 | 文件及目录；遵循 `Depth`、`Destination`、`Overwrite` |
| `MOVE` | 移动或重命名 | 同一 DAV 服务内；跨 `files/shared` 边界按权限判断 |
| `DELETE` | 删除资源 | 进入现有文件回收站；递归删除目录 |
| `LOCK` | 创建或刷新写锁 | 支持排他写锁和 lock-null 资源 |
| `UNLOCK` | 释放写锁 | 校验 `Lock-Token` 和凭据所属用户 |

不支持的方法返回 `405 Method Not Allowed`，并带 `Allow` 响应头。

### 5.2 属性

至少实现：

- `{DAV:}displayname`
- `{DAV:}resourcetype`
- `{DAV:}getcontentlength`
- `{DAV:}getcontenttype`
- `{DAV:}getetag`
- `{DAV:}getlastmodified`
- `{DAV:}creationdate`
- `{DAV:}supportedlock`
- `{DAV:}lockdiscovery`

不声明配额属性，直到项目存在真实的用户容量配额。文件夹大小不得在 `PROPFIND` 中递归计算。

### 5.3 ETag 与时间

- 文件强 ETag：`"f-{file_id}-v-{latest_file_content_id}"`。
- 空文件强 ETag：`"f-{file_id}-v-0"`。
- 文件夹弱 ETag：`W/"d-{file_id}-{updated_at_timestamp}"`。
- 虚拟根 ETag 包含用户 ID 和可见共享列表的最大更新时间。
- `Last-Modified` 使用 `files.updated_at`，统一输出 GMT。
- `PUT`、`MOVE`、`COPY`、`DELETE` 必须处理 `If-Match`、`If-None-Match` 和 DAV `If` 头。
- 条件不满足返回 `412 Precondition Failed`，不得继续写入。

### 5.4 内容读取与写入

- GET 只读取最新未删除 `FileContent`。
- 物理文件通过鉴权后的响应流输出，不返回 `uploads/...` 地址。
- 空文件返回长度为 0 的正常文件，不沿用网页预览接口的空 Office 模板。
- PUT 使用 `php://input` 对应的请求流分块写入临时文件，禁止 `getContent()` 整体载入内存。
- 当前 LaravelS/Swoole 的 `package_max_length` 虽为 1 GB，但该配置只是允许请求大小，不证明原始 PUT body 在进入 Laravel 前不会被 Swoole 聚合到内存。实现阶段必须先通过 RSS 压测验证请求入口；未通过时必须启用 9.6 节的独立 DAV 入口。
- 超过配置大小时尽早返回 `413 Content Too Large`，未知长度请求在流式累计超限时中断。
- 覆盖现有文件时保留 `files.id`，新增一条 `file_contents`，保证分享链接、历史记录和最近访问仍指向原文件。
- 新文件扩展名和 `type` 使用统一类型映射服务，不在 DAV 层复制 `match` 列表。

### 5.5 原子保存兼容

桌面客户端常使用“上传临时文件，再 MOVE 覆盖目标”的方式保存。服务端必须特殊处理：

1. 当前用户在目标目录创建临时文件。
2. MOVE 的目标已存在且 `Overwrite: T`。
3. 用户对目标有写权限，并持有需要的锁。
4. 服务端将临时文件最新内容作为目标文件的新版本，保留目标 `files.id`。
5. 删除临时文件记录，返回 `204 No Content`。

这样共享读写用户无需拥有目标文件的删除权限，也能完成 Office 原子保存。

## 6. 权限模型

### 6.1 权限级别映射

| DooTask 权限 | DAV 能力 |
| --- | --- |
| `-1` 无权限 | 统一表现为 `404`，避免泄露资源存在性 |
| `0` 只读 | PROPFIND、HEAD、GET、作为 COPY 来源 |
| `1` 读写 | 只读能力 + PUT、MKCOL、PROPPATCH、LOCK；可修改/重命名资源，不能删除或移走他人资源 |
| `1000` 所有者或创建者 | 全部能力，包括 DELETE、MOVE 和共享边界管理允许的操作 |

### 6.2 共享目录规则

- `shared/` 虚拟根永远不可写。
- 只读共享项内任何写方法返回 `403 Forbidden`。
- 读写共享项允许创建子项和更新已有内容。
- 用户创建的子项因 `created_id` 是当前用户，可由该用户移动和删除。
- 用户不得删除或移走共享所有者创建的资源。
- 对已有资源的同目录重命名按写权限处理，与现有 `add(id)` 行为一致。
- 原子覆盖按照 5.5 节处理，不把覆盖解释为删除目标。
- 不允许把 `shared/` 顶层项 MOVE 到 `files/`，也不允许改变共享关系。
- 从共享目录 COPY 到 `files/`：来源需可读，目标需可写，新副本归当前用户所有。
- 从 `files/` COPY 到共享目录：目标共享目录需读写，新副本所有者沿用共享根所有者，创建者为当前用户。

### 6.3 权限变化

- 每个请求实时读取当前共享权限，不依赖凭据创建时权限。
- 共享撤销后，后续请求立即变为 `404`。
- 对已锁资源撤销共享时，相关 DAV 锁同步删除。
- 用户停用、删除或被移出 WebDAV 允许范围时，所有凭据立即不可用并清理锁。

## 7. 认证与凭据

### 7.1 认证协议

- 使用 HTTPS 上的 HTTP Basic Authentication。
- Basic 用户名使用服务端生成的公开标识，例如 `dtw_01J...`。
- 密码使用 32 字节加密随机数生成的 base64url 字符串。
- 数据库只保存 Laravel `Hash::make()` 结果和密码末四位，不保存明文或可逆密文。
- 创建响应只返回一次完整密码。
- 不支持主账号密码、登录 token、URL token 和匿名访问。

选择独立公开用户名而不是邮箱，原因是：凭据可以独立撤销；无需处理 LDAP/SSO 密码；认证查询可以命中唯一索引；不会泄露登录邮箱。

### 7.2 凭据状态

凭据可处于：

- `active`：可正常认证。
- `expired`：超过 `expires_at`。
- `revoked`：用户或管理员撤销。
- `disabled`：全局开关、允许范围或用户状态导致不可用，不改变凭据记录。

认证成功后异步或限频更新 `last_used_at`、`last_used_ip`、`last_user_agent`，同一凭据最多每 5 分钟写库一次。

### 7.3 防护

- 按 IP 和公开用户名组合限流，建议失败 10 次/分钟后返回 `429`。
- 使用 `hash_equals` 或 Laravel Hash 校验，错误响应不区分用户名不存在、密码错误和凭据已撤销。
- `401` 必须返回 `WWW-Authenticate: Basic realm="DooTask WebDAV", charset="UTF-8"`。
- 管理界面创建和撤销凭据使用现有登录 token，并记录安全审计。
- 生产环境不是 HTTPS 时禁止创建凭据；已有 DAV 请求返回配置错误。

## 8. 数据模型

### 8.1 `webdav_credentials`

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `id` | bigint PK | 主键 |
| `public_id` | varchar(40) unique | Basic 用户名，不含秘密 |
| `userid` | bigint index | 所属用户 |
| `name` | varchar(100) | 用户填写的设备名称 |
| `password_hash` | varchar(255) | 应用密码哈希 |
| `password_suffix` | varchar(4) | 展示末四位 |
| `expires_at` | timestamp nullable | 过期时间 |
| `last_used_at` | timestamp nullable | 最近使用 |
| `last_used_ip` | varchar(45) nullable | 最近 IP |
| `last_user_agent` | varchar(255) nullable | 最近客户端 |
| `revoked_at` | timestamp nullable | 撤销时间 |
| `created_at/updated_at` | timestamps | 时间 |

不使用软删除，撤销记录保留到审计保留期结束。默认每用户最多 5 个有效凭据。

### 8.2 `webdav_locks`

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `id` | bigint PK | 主键 |
| `token` | varchar(100) unique | `opaquelocktoken:{uuid}` |
| `userid` | bigint index | 锁所有者 |
| `credential_id` | bigint index | 创建锁的凭据 |
| `file_id` | bigint nullable index | 已存在资源 ID；lock-null 时为空 |
| `uri` | varchar(1000) index prefix | 规范化 DAV URI |
| `uri_hash` | char(64) index | URI SHA-256，精确查询 |
| `owner` | varchar(255) nullable | 客户端 owner |
| `scope` | varchar(20) | 首版固定 exclusive |
| `depth` | varchar(20) | `0` 或 `infinity` |
| `timeout_at` | timestamp index | 过期时间 |
| `created_at/updated_at` | timestamps | 时间 |

- 默认锁 30 分钟，允许客户端请求 1 分钟至 2 小时。
- 通过定时任务清理过期锁。
- MOVE/重命名目录时，在同一事务内更新该资源及子资源锁 URI。
- 删除、撤销凭据或权限时删除相关锁。

### 8.3 `webdav_properties`

存储客户端通过 PROPPATCH 设置的死属性：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `id` | bigint PK | 主键 |
| `file_id` | bigint index | 资源 ID |
| `namespace` | varchar(255) | XML namespace |
| `name` | varchar(255) | 属性名 |
| `value` | longtext | 安全序列化后的 XML 值 |
| `created_at/updated_at` | timestamps | 时间 |

唯一键为 `file_id + namespace_hash + name_hash`。删除文件时一并删除；复制时复制死属性，移动时无需变化。

### 8.4 `webdav_operation_logs`

记录认证结果和写操作，GET/PROPFIND 只进入结构化访问日志与指标，避免数据库日志量失控。

字段至少包括：`request_id`、`userid`、`credential_id`、`method`、`uri`、`file_id`、`status`、`result`、`bytes`、`ip`、`user_agent`、`duration_ms`、`created_at`。

URI 可能包含敏感文件名，管理员页面默认只显示末级文件名，日志导出需要管理员权限。默认保留 90 天，由定时任务分批清理。

### 8.5 文件路径唯一性

WebDAV 要求同一集合内 URI 唯一，必须完成以下治理：

1. 增加只读审计命令，检测同一有效父目录下完整名称冲突。
2. 存在冲突时禁止管理员启用 WebDAV，并列出待处理文件 ID。
3. 所有网页 API 和 DAV 写入统一经过文件领域服务，并对父目录加分布式锁和数据库行锁。
4. 冲突检查使用 `pid + userid + name + ext + deleted_at IS NULL` 的现有数据库排序语义。
5. 不直接增加包含 `deleted_at` 的普通唯一索引，因为 MySQL 对 NULL 唯一值的行为不能保证软删除资源唯一；后续可通过生成列 `active_path_key` 增强约束。

## 9. 代码架构

### 9.1 依赖

在生产依赖中增加兼容当前 PHP 版本的 `sabre/dav` 稳定版本，并锁定小版本范围。引入前执行许可证、PHP 8.4 和 LaravelS 兼容验证。

不得使用 Sabre 的 SAPI 直接输出或 `exit`。需要将 Illuminate Request 桥接为 Sabre HTTP Request，再将 Sabre Response 转换为 Symfony Response/StreamedResponse。

### 9.2 新增模块建议

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/FileDavController.php
│   │   └── WebDavProtocolController.php
│   └── Middleware/WebDavRequest.php
├── Models/
│   ├── WebDavCredential.php
│   ├── WebDavLock.php
│   ├── WebDavProperty.php
│   └── WebDavOperationLog.php
├── Services/WebDav/
│   ├── WebDavServerFactory.php
│   ├── WebDavAuthBackend.php
│   ├── WebDavTree.php
│   ├── WebDavDirectory.php
│   ├── WebDavFile.php
│   ├── WebDavLockBackend.php
│   ├── WebDavPropertyBackend.php
│   ├── WebDavPathResolver.php
│   └── WebDavAuditService.php
└── Services/FileSystem/
    ├── FileSystemService.php
    ├── FileContentStorage.php
    ├── FileName.php
    ├── FileTypeResolver.php
    └── FileOperationResult.php
```

### 9.3 `FileSystemService` 边界

服务方法接收明确的 `User` 和结构化参数，不读取全局 `Request`，不返回 HTTP 响应：

```php
list(User $actor, int $parentId, string $scope): Collection
resolveChild(User $actor, int $parentId, string $fullName): File
createDirectory(User $actor, int $parentId, string $name): FileOperationResult
putFromStream(User $actor, int $parentId, string $fullName, $stream, PutOptions $options): FileOperationResult
rename(User $actor, File $file, string $newName): FileOperationResult
move(User $actor, File $file, int $targetParentId, MoveOptions $options): FileOperationResult
copy(User $actor, File $file, int $targetParentId, CopyOptions $options): FileOperationResult
delete(User $actor, File $file): FileOperationResult
read(User $actor, File $file, ?int $versionId = null): FileReadHandle
```

现有 `FileController` 的 add、copy、move、remove、content save/upload 逐步改为调用此服务，保持 API 响应不变。这样 DAV 和网页端共享同一事务、权限和副作用。

### 9.4 内容存储

`FileContentStorage` 负责：

- 临时流落盘、大小限制和哈希计算。
- 将临时文件原子移动到 `uploads/file/{type}/{Ym}/{fileId}/{contentKey}`。
- 打开最新内容只读流。
- 复制内容时创建独立物理文件，避免两个 `FileContent` URL 引用同一路径后其中一个清理导致另一个损坏。
- 删除物理文件前检查是否仍有其他 `FileContent` 引用同一 URL，兼容历史复制数据。
- DB 失败时清理已移动文件；进程异常时由孤儿文件扫描任务兜底。

### 9.5 请求生命周期

每次 DAV 请求创建新的 Sabre Server、树、认证 backend 和响应对象。当前认证用户写入 `RequestContext`，请求结束由 WebDAV middleware 清理。

禁止将以下对象注册为保存请求状态的单例：

- Sabre Server
- 当前 User
- 当前 Credential
- PathResolver 的节点缓存
- 请求/响应流

### 9.6 大文件请求入口

完整实现需要支持现有系统允许的最大文件，同时不能让单个 PUT 占用等量 Worker 内存。采用两级决策：

1. 首先在当前 LaravelS 入口分别上传 100 MB、500 MB、1 GB 文件，记录 Nginx、Swoole Worker 和容器 RSS 峰值。
2. 只有 RSS 增量保持在固定缓冲上限内，才允许 `/dav` 继续复用 LaravelS。
3. 如果 RSS 随文件大小线性增长，则生产架构增加独立 `webdav` PHP-FPM 容器；Nginx 仅将 `/dav/` 转发给该容器，普通 API 和 WebSocket 仍走 LaravelS。
4. 独立入口复用同一份 Laravel 代码、数据库、Redis 和项目文件卷，但每请求启动独立应用生命周期，通过 `php://input` 流式读取。
5. PHP-FPM 方案仍需验证 Nginx/FastCGI 是否落临时文件或流式传递，并统一临时目录容量、超时和请求大小。

不得采用以下降级方式规避问题：把 1 GB body 放入 Redis、由 Swoole Worker 整体读取后再分块、或仅依靠提高容器内存。若独立入口尚未完成，管理员页面必须把 WebDAV 单文件上限限制为已压测证明安全的值。

## 10. 路由与 API

### 10.1 DAV 协议路由

在 SPA 兜底路由之前注册：

```text
OPTIONS  /dav/{path?}
PROPFIND /dav/{path?}
PROPPATCH /dav/{path?}
HEAD     /dav/{path?}
GET      /dav/{path?}
PUT      /dav/{path?}
MKCOL    /dav/{path?}
COPY     /dav/{path?}
MOVE     /dav/{path?}
DELETE   /dav/{path?}
LOCK     /dav/{path?}
UNLOCK   /dav/{path?}
```

`path` 使用 `.*` 约束。`/dav/*` 加入 CSRF 排除，但仍由 WebDAV Basic 认证保护。Nginx 明确增加优先于 SPA 的 `/dav/` location。该 location 按 9.6 节验证结果转发到 LaravelS 或独立 PHP-FPM DAV 入口；请求缓冲、临时目录和超时以实测的恒定内存为验收标准。

### 10.2 管理 API

管理接口使用 `api/file/dav/xxx` 命名，但由独立 `Api\FileDavController` 承载，不向冻结的巨型 `FileController` 新增方法：

| API | 方法 | 权限 | 用途 |
| --- | --- | --- | --- |
| `api/file/dav/adminsetting` | GET/POST | admin | 获取/保存全局配置 |
| `api/file/dav/adminstatus` | GET | admin | 运行状态、冲突审计和近期失败 |
| `api/file/dav/userrevoke` | POST | admin | 撤销用户全部凭据 |
| `api/file/dav/status` | GET | 登录用户 | 当前可用性、URL、策略 |
| `api/file/dav/credentials` | GET | 登录用户 | 凭据列表，不返回哈希 |
| `api/file/dav/create` | POST | 登录用户 | 创建并一次性返回密码 |
| `api/file/dav/revoke` | POST | 登录用户 | 撤销凭据 |

这些 URL 保持 `file/{method}/{action}` 的两段动态路由限制，控制器方法分别为 `dav__adminsetting`、`dav__adminstatus`、`dav__userrevoke`、`dav__status`、`dav__credentials`、`dav__create`、`dav__revoke`。

路由中先将 `method = dav` 明确分派到 `FileDavController`，再让其他 `file/{method}/{action}` 进入现有 `FileController`；现有 FileController 路由应增加排除 `dav` 的约束，避免相同 URI 模式产生不确定匹配。新增控制器和路由后运行 `./cmd artisan doc:api-map`。

这里的 `api/file/dav/xxx` 只承载网页使用的 JSON 管理接口。WebDAV 客户端仍连接 `/dav/{path?}` 协议路由，因为它需要任意深度路径、自定义 HTTP 方法、XML 多状态响应和独立异常处理。

### 10.3 API 契约

所有管理 API 继续使用 `Base::retSuccess()` / `Base::retError()`，不返回 WebDAV XML。核心载荷如下：

```text
POST api/file/dav/create
request:  { name: string, expire_days: int }
response: { id, public_id, password, password_suffix, url, expires_at }

POST api/file/dav/revoke
request:  { id: int }
response: { id, revoked_at }

GET api/file/dav/credentials
response: [{ id, public_id, name, password_suffix, expires_at,
             last_used_at, last_used_ip, last_user_agent, status }]

GET api/file/dav/status
response: { enabled, allowed, https, url, max_credentials,
            active_credentials, default_expire_days, max_expire_days,
            max_file_bytes }

POST api/file/dav/userrevoke
request:  { userid: int }
response: { userid, revoked_count, revoked_at }
```

- `password` 只存在于创建成功响应，列表和日志不得出现。
- `expire_days` 必须在管理员策略范围内；`0` 仅在管理员允许永不过期时有效。
- 撤销接口幂等，重复撤销返回成功和原 `revoked_at`。
- `status.enabled` 表示全局开关，`allowed` 表示当前用户是否在允许范围，两者不能混用。
- 管理设置保存采用字段白名单和完整归一化，前端未提交的敏感策略不得被空值覆盖。

### 10.4 配置

用户可配置策略存储在 `fileSetting`：

```text
webdav_enabled
webdav_permission_type        all / appoint
webdav_permission_userids
webdav_max_credentials
webdav_default_expire_days
webdav_max_expire_days
webdav_max_file_bytes
webdav_copy_max_nodes
webdav_audit_retention_days
```

协议硬限制和默认值放在 `config/dootask.php`，业务代码不直接读取 `env()`。配置/路由变更部署后需要重启 LaravelS。

## 11. 并发、事务与锁

### 11.1 两类锁

- 协议锁：`webdav_locks`，对客户端可见，实现 DAV `LOCK/UNLOCK`。
- 服务端互斥锁：复用 `App\Module\Lock`，保护同一父目录的命名空间和同一文件版本写入。

两者不可相互替代。即使客户端未主动 LOCK，服务端仍必须使用短期互斥锁保证事务一致性。

### 11.2 加锁顺序

为避免死锁，统一按以下顺序：

1. 规范化资源路径。
2. 检查 DAV 锁 token。
3. 获取按数字 ID 排序后的父目录分布式锁。
4. 开启数据库事务。
5. 按 ID 升序 `lockForUpdate` 锁父目录、源资源、目标资源。
6. 再次检查权限、名称冲突和条件请求。
7. 写数据库并提交。
8. 事务外投递可重试的通知；现有必须同步的副作用保持原行为。

### 11.3 失败恢复

- 请求体接收失败：删除临时文件，不创建 File/FileContent。
- 物理文件移动失败：回滚数据库。
- 数据库失败：删除本次新物理文件；删除失败记入孤儿清理队列。
- 消息或搜索异步投递失败：主文件操作成功，记录失败并走现有重试机制。
- 客户端断开：检测连接状态并停止继续读取，finally 清理临时文件。
- MOVE/COPY 多资源失败：不得留下半棵可见目录；先在事务中完成元数据，超出同步上限直接在执行前拒绝。

## 12. 状态码与错误映射

| 场景 | 状态码 |
| --- | --- |
| 未提供或无效凭据 | `401 Unauthorized` |
| 全局关闭或维护中 | `503 Service Unavailable` |
| 无查看权限或资源不存在 | `404 Not Found` |
| 有查看权限但无写权限 | `403 Forbidden` |
| 同名目标且不允许覆盖 | `412 Precondition Failed` |
| ETag 或 DAV If 条件失败 | `412 Precondition Failed` |
| 资源被其他锁占用 | `423 Locked` |
| 父目录不存在 | `409 Conflict` |
| 文件夹达到 300 项 | `507 Insufficient Storage` |
| 文件或复制规模超限 | `413 Content Too Large` 或 `507` |
| 不支持的方法 | `405 Method Not Allowed` |
| PROPFIND/PROPPATCH 多状态 | `207 Multi-Status` |
| PUT 新建成功 | `201 Created` |
| PUT 覆盖成功 | `204 No Content` |
| MOVE/COPY 成功 | `201` 或 `204` |
| DELETE 成功 | `204 No Content` |

DAV 路由的异常必须由 DAV 专用异常渲染器转换为 XML 或空响应，不能落入全局 `ApiException` JSON 响应。

## 13. 安全设计

- 只允许 HTTPS；反向代理场景使用可信的 `X-Forwarded-Proto` 判断。
- XML 使用禁用外部实体和网络访问的解析器，限制 XML 体积和节点数量，防止 XXE 与 XML bomb。
- 拒绝双重编码、路径穿越、编码斜杠、超长路径和控制字符。
- `Destination` 必须属于当前 Host 和 `/dav/` 基础路径，拒绝跨服务 COPY/MOVE。
- 响应不暴露物理路径、SQL、文件所有者邮箱和权限查询细节。
- 下载设置 `Content-Disposition`、正确 MIME、`X-Content-Type-Options: nosniff`。
- 凭据明文只出现于创建响应；前端不得写入 localStorage、日志或埋点。
- 操作审计覆盖凭据创建、撤销、认证失败以及全部 DAV 写方法。
- 管理员允许名单变更、用户停用和密码策略变化不需要重发 WebDAV 密码，但必须实时影响访问状态。

## 14. 性能与容量

- PROPFIND `Depth: 1` 一次批量查询子节点和最新内容 ID，避免 N+1。
- 当前每目录最多 300 项，可在单次响应内返回；仍应使用游标式内部查询和固定字段选择。
- 共享根一次查询全部可见共享根，结果只在当前请求缓存。
- GET/PUT 采用 1 MB 左右分块流式处理，实际块大小通过压测确定。
- Nginx、LaravelS、PHP 临时目录和应用限制必须统一，避免某一层提前截断。
- `package_max_length = 1 GB` 只代表 Swoole 接受上限，不作为流式能力证明；入口进程 RSS 是强制验收指标。
- COPY 目录默认最多 10,000 个节点；执行前先计数，超限不启动复制。
- 大文件和长请求设置独立的 Nginx 超时，不影响普通 API。
- 审计写入可通过 Swoole Task 异步投递，但认证失败和安全事件必须保证记录或进入结构化日志。

## 15. 前端设计

### 15.1 管理端

在现有「文件设置」增加 WebDAV 区域：

- 启用开关。
- 允许使用范围：全员/指定成员。
- 每用户凭据数、默认有效期、最大有效期。
- 单文件和目录复制限制。
- 当前服务 URL 与 HTTPS 状态。
- 路径冲突审计状态；存在冲突时禁用开启按钮并提供文件 ID 列表。
- 最近 24 小时认证失败数和写入失败数。

### 15.2 用户端

在文件页面右上角现有加号按钮右侧增加圆形“更多”按钮：

- 使用现有图标库的 `ios-more`，按钮尺寸、圆形样式和固定占位与加号保持一致。
- 点击后打开下拉菜单，首版包含「WebDAV」入口，后续文件级全局能力可以继续放入该菜单。
- 入口在“我的文件”和“共享文件”板块显示；“协作文件”不属于 DAV 范围，不显示该入口。
- 选择「WebDAV」后打开独立管理弹窗，不跳转到系统设置或个人安全页面。
- 弹窗展示可用状态、服务地址和凭据列表：设备名、末四位、创建时间、过期时间、最后使用时间和客户端。
- 弹窗内可以创建应用密码；创建成功后一次性展示连接信息。
- 弹窗内可以撤销单个凭据并二次确认。
- 管理员关闭时弹窗只展示不可用状态，不能创建新凭据。
- 移动端空间不足时保留加号和更多两个固定尺寸图标，搜索框优先收缩，按钮不得换行或覆盖。

所有新增可见中文同步登记到 `language/original-web.txt` 和 `language/original-api.txt`，并更新相关 ai-kb 功能 chunk。

## 16. 可观测性与运维

### 16.1 指标

至少统计：

- 按方法和状态码的请求数。
- 认证成功、失败、限流次数。
- 活跃用户和活跃凭据数。
- GET/PUT 字节数及耗时分布。
- 锁创建、冲突、超时数。
- ETag 冲突和覆盖次数。
- 临时文件、孤儿文件数量及清理失败数。
- DAV 写入后的消息/搜索同步失败数。

### 16.2 日志关联

- 每个请求生成 `request_id` 并加入响应头 `X-Request-Id`。
- DAV 操作日志、应用日志和 Nginx 日志都记录该 ID。
- 密码、Authorization、Lock-Token 不得进入日志。
- URI 记录前去除认证信息并限制长度。

### 16.3 定时维护

新增任务：

- 每分钟或按需清理过期 DAV 锁。
- 每日清理过期/撤销且超过保留期的凭据记录。
- 每日分批清理过期操作日志。
- 复用临时文件清理任务清理超时 DAV 上传目录。
- 定期扫描无 FileContent 引用的物理孤儿文件，只报告；自动删除需另行评审。

## 17. 测试方案

### 17.1 单元测试

- 路径编码、NFC、非法字符、穿越和双重解码。
- 完整文件名与 `name/ext/type` 转换。
- `files/shared` 节点解析和共享顶层 `[#ID]`。
- 权限矩阵的每个方法。
- ETag 和全部条件请求组合。
- 锁创建、刷新、继承、冲突、过期和撤销。
- WebDAV 状态码与 DooTask 异常映射。
- 应用密码生成、哈希校验、过期和撤销。

### 17.2 Feature 测试

- OPTIONS 和 Basic challenge。
- PROPFIND Depth 0/1 的 XML 响应。
- PUT 新建、覆盖及历史版本。
- MKCOL、COPY、MOVE、DELETE 全流程。
- 临时文件 MOVE 覆盖目标且保留目标 ID。
- 共享只读、共享读写、创建者删除和越权访问。
- Range GET、HEAD、空文件和大文件流。
- 两客户端并发 PUT、锁冲突和 ETag 冲突。
- 凭据撤销、用户停用、全局关闭立即生效。
- 写操作后 WebSocket 推送和 Manticore Task 被正确投递。
- 数据库/物理写入异常时无可见半成品。

### 17.3 协议与客户端测试

- 使用 WebDAV Litmus 测试套件作为协议基线。
- `curl` 覆盖所有方法和条件头。
- Windows 11 文件资源管理器：挂载、Office 保存、重命名、覆盖、删除。
- macOS Finder：连接、复制目录、锁定编辑、断线重连。
- Linux `davfs2`：挂载和并发文件操作。
- Microsoft Office/LibreOffice：临时文件原子覆盖、锁刷新和冲突提示。
- 中文、空格、`#`、`%`、emoji、超长名称和大小写冲突文件。

客户端测试记录环境、步骤、状态码和结果截图到 `tests/playwright-results/` 或新增的 DAV 测试结果目录；协议测试不伪装为 Playwright 自动化结果。

### 17.4 质量门禁

实现完成后执行：

```text
./cmd composer stan
npm run lint
npm run check:lang
./cmd artisan doc:api-map
```

不主动运行 `./cmd dev`、`./cmd prod` 或 `./cmd build`。

## 18. 实施阶段与验收

### 阶段 0：领域服务收敛

- 先完成 9.6 节请求体内存验证，并确定 LaravelS 或独立 PHP-FPM 入口；该结论记录到测试结果。
- 建立 FileSystemService、类型解析和内容存储。
- 现有文件 API 迁入服务，接口行为保持兼容。
- 修复复制内容物理引用和路径并发问题。
- 增加路径冲突审计命令。

验收：大文件入口架构已经用 RSS 数据确定；原文件页面全部操作通过；现有 API 响应无回归；新增并发测试通过。

### 阶段 1：开关与凭据

- 增加迁移、模型、管理员配置和用户凭据界面。
- 完成 Basic backend、限流、撤销和安全审计。
- 功能开关默认关闭。

验收：凭据只展示一次；撤销和全局关闭立即生效；日志无秘密信息。

### 阶段 2：只读协议

- 完成 OPTIONS、PROPFIND、HEAD、GET、ETag、Range。
- 完成 `files/shared` 虚拟树和权限隐藏。
- 接入 Litmus 与三个操作系统的只读验证。

验收：大文件恒定内存；无权限资源不泄露；共享列表无重复和歧义。

### 阶段 3：写协议

- 完成 PUT、MKCOL、COPY、MOVE、DELETE、PROPPATCH。
- 完成统一事务、副作用、临时文件和原子覆盖。
- 完成共享权限矩阵。

验收：网页端和 DAV 互相实时可见；覆盖产生历史；异常不留半成品。

### 阶段 4：锁与兼容

- 完成 LOCK/UNLOCK、DAV If 头和锁清理。
- 完成 Windows/macOS/Office 兼容修正和性能压测。
- 完成运维仪表和告警。

验收：Litmus 目标用例通过；Office 原子保存稳定；并发编辑不静默丢失数据。

### 阶段 5：灰度上线

- 先对指定内部用户启用。
- 观察至少一个完整凭据和锁超时周期。
- 检查错误率、孤儿文件、同步失败和数据库慢查询。
- 再逐步扩大允许范围，最后由管理员决定是否全员开放。

### 工作包与依赖

| 工作包 | 内容 | 前置依赖 | 交付判定 |
| --- | --- | --- | --- |
| W0 | LaravelS/PHP-FPM 大文件入口验证 | 无 | 形成 RSS 数据和确定的部署拓扑 |
| W1 | FileSystemService、类型解析、内容存储 | 无 | 原网页文件 API 全部复用服务且行为无回归 |
| W2 | 凭据、锁、属性、审计迁移与模型 | 无 | 迁移和模型单测通过，不修改现有文件数据 |
| W3 | 管理配置、用户凭据 API 与前端 | W2 | 开启、创建、一次展示、撤销、停用形成闭环 |
| W4 | Sabre 请求桥、Basic backend、DAV 中间件 | W0、W2 | OPTIONS 和认证挑战符合协议，异常不返回 JSON |
| W5 | 虚拟树、路径解析、PROPFIND/HEAD/GET | W1、W4 | 我的文件和共享文件只读客户端验证通过 |
| W6 | PUT/MKCOL/COPY/MOVE/DELETE | W1、W5 | 写入历史、权限、副作用和失败补偿测试通过 |
| W7 | LOCK/UNLOCK、PROPPATCH、条件请求 | W2、W5、W6 | 并发编辑返回正确 412/423，无静默覆盖 |
| W8 | 审计、指标、清理任务和管理状态 | W2、W4 | 可定位失败请求，过期数据自动分批收敛 |
| W9 | Litmus、系统客户端、Office 和压测 | W5、W6、W7、W8 | 目标兼容矩阵和性能门禁全部有记录 |
| W10 | API map、语言、ai-kb、部署和运维文档 | W3 至 W9 | 文档与最终行为一致，版本号完成复核 |

W0、W1、W2 可以并行；W4 不得在 W0 未定结论时固化部署实现；W6 不得绕过 W1 直接写模型。每个工作包都应包含对应自动化测试，避免把测试集中到 W9 才补。

## 19. 发布、回滚与数据安全

### 19.1 发布前

- 数据库备份。
- 执行路径冲突审计，存在冲突则停止启用。
- 验证 HTTPS、代理头、大文件限制和临时目录容量。
- 安装依赖并完成 PHP 8.4/LaravelS 冒烟测试。
- 迁移只新增表和索引，不删除现有数据。

### 19.2 回滚

1. 首先关闭 `webdav_enabled`，立即阻断协议流量。
2. 保留凭据、锁和审计表，便于调查和再次启用。
3. 回滚协议路由和代码不影响已有 `files/file_contents` 数据。
4. 不自动删除 DAV 创建的文件，因为它们已经是正常 DooTask 文件。
5. 如需卸载表结构，必须另行确认并先导出审计；不作为常规代码回滚步骤。

### 19.3 兼容承诺

- DAV 创建的文件必须能在网页端正常预览、下载、移动和恢复历史。
- 网页端修改必须在下一次 DAV 请求立即可见。
- 禁用 WebDAV 不改变任何文件、共享关系或历史版本。
- 后续升级不得改变 `files/`、`shared/` URI 名称和共享顶层 ID 规则。

## 20. 风险与决策记录

| 风险 | 处理决策 |
| --- | --- |
| 现有控制器含业务逻辑 | 先收敛到 FileSystemService，再接 DAV |
| 应用密码被窃取 | 强制 HTTPS、只存哈希、可撤销、限流、审计 |
| Swoole 请求状态串联 | 每请求建 Server，用户和缓存放 RequestContext |
| 客户端静默覆盖 | ETag + DAV If + LOCK，失败返回 412/423 |
| 共享写权限与删除权限不同 | 保留现有语义，原子覆盖不解释为删除目标 |
| 同名共享根冲突 | 共享顶层 URI 固定附加 `[#file_id]` |
| 大文件耗尽内存 | 全链路流式、统一上限、临时目录监控 |
| Swoole 在 Laravel 前聚合 PUT body | RSS 压测作为门禁；不满足时使用独立 PHP-FPM DAV 入口 |
| 复制内容共享物理 URL | 新复制创建独立内容，旧数据删除前查引用 |
| 异常留下物理孤儿 | 补偿清理 + 孤儿扫描报告 |
| DAV 错误落成 JSON | 独立中间件和异常响应转换 |
| 路径冲突导致 URI 不唯一 | 启用前审计、父目录锁、统一服务写入 |

## 21. 完成定义

只有同时满足以下条件，WebDAV 才算功能闭环：

- 管理员可以启用、限制、观测和关闭服务。
- 用户可以创建、使用、查看状态和撤销应用密码。
- `files/shared` 的读写与网页权限一致。
- 所有协议方法返回标准状态码和 XML。
- 写入保留历史并触发现有通知、搜索和回收站行为。
- 锁、ETag 和条件请求可以防止并发静默覆盖。
- 大文件不会整体进入 PHP 内存，失败会清理临时文件。
- Windows、macOS、Linux 和办公客户端有可追溯的验证结果。
- 功能默认关闭，可灰度，可即时停用，停用不破坏文件数据。
- API 对照表、语言文件、ai-kb、运维文档和测试在同一次功能交付中同步更新。
