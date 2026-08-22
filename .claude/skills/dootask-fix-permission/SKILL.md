---
name: dootask-fix-permission
description: 修复 DooTask 整个项目的目录和文件权限：根目录 chmod 755，bootstrap/cache、docker、public、storage chown 回调用用户且目录 chmod 775，public、主程序 AI 知识库与应用知识库补充只读访问权限。用于 Nginx 静态文件 403/Permission denied、AI 知识库无法读取、install/build EACCES 或可写目录检测失败；优先使用 sudo ./cmd permission，赋权不删数据。
---

# DooTask 目录权限修复

项目根目录如果缺少 `x` 穿越权限，Nginx 即使看到 `public` 中的文件也无法访问。容器内进程还常以 **root** 写入挂载目录（`storage`、`public/uploads`、`bootstrap/cache` 等），导致宿主机当前用户没有写权限。常见现象：

- Nginx 日志出现 `stat() failed (13: Permission denied)`，静态文件请求被回退到 Laravel 首页
- `./cmd install` 报「目录【xxx】权限不足」/目录权限检测失败
- `./cmd build`（vite）报 `EACCES: permission denied, copyfile`
- Laravel 运行时写 `storage`/`bootstrap/cache` 失败

对齐 `./cmd permission`/`./cmd install` 的赋权逻辑：项目根目录设为 `755`；四个可写目录做 `chmod 775`（仅目录）+ `chown` 回调用 sudo 的用户；`public` 普通文件用 `a+r` 补充 Nginx 所需读权限；`resources/ai-kb` 目录用 `a+rx`、文件用 `a+r`；应用包目录沿用 `docker` 的 `775` 目录权限，并给用于定位知识库的 `config.yml` 和 `ai-kb` 内的 Markdown 补充读权限。上述知识库权限供非 root AI 容器只读访问。

## 适用目录

```text
.                # 项目根目录，只修本层为 755
bootstrap/cache
docker
public           # 目录 775，普通文件 a+r；含真实上传数据
storage
resources/ai-kb  # 目录 a+rx、普通文件 a+r；AI 容器只读挂载
docker/appstore/apps  # config.yml 与 ai-kb 内的 Markdown 文件 a+r
```

## 核心原则：赋权，不删数据

`public/uploads` 含真实上传文件。永远优先 `chown` 改属主，不要删数据。即便用户说“清理一下”，也只允许清理临时目录 `public/uploads/tmp`，切勿删除 uploads 下其他内容。

## 前置检查

1. 在项目根目录执行，确认存在 `cmd` 和上述四个目录。
2. 用 `ls -ld .` 检查项目根目录是否缺少组/其他用户的 `x` 穿越权限。
3. 确认可使用 sudo；改 root 属主的文件或目录需要 root 权限。
4. 用 `find public -type f ! -perm -004 -print` 检查 Nginx 用户可能无法读取的静态文件。
5. 默认修复项目根目录、四个可写目录、`public` 文件、主程序知识库和应用知识库只读权限；若用户只想解决 build 的 uploads 报错，可只处理 `public/uploads`。

检查通过后，汇报将执行的命令，向用户确认一次再执行。

## 执行

确认后优先执行独立权限修复命令（不依赖 Docker 正在运行）：

```shell
sudo ./cmd permission
```

旧版 `cmd` 没有 `permission` 命令时，手动执行：

```shell
# 1) 保证 Nginx 可穿越项目根目录（不递归）
sudo chmod 755 .

# 2) 可写目录属主修回当前用户（递归）
sudo chown -R "$(id -u):$(id -g)" bootstrap/cache docker public storage

# 3) 可写目录权限 775（仅目录）
find bootstrap/cache docker public storage -type d -exec chmod 775 {} \;

# 4) public 普通文件只补充读权限，保留现有写入/执行位
find public -type f -exec chmod a+r {} \;

# 5) AI 知识库目录补充读取/穿越权限，文件只补充读权限
find resources/ai-kb -type d -exec chmod a+rx {} \;
find resources/ai-kb -type f -exec chmod a+r {} \;

# 6) 应用知识库依赖 config.yml 定位，当前应用统一使用 ai-kb 目录
find docker/appstore/apps -type f -name "config.yml" -exec chmod a+r {} \;
find docker/appstore/apps -type f -path "*/ai-kb/*" -name "*.md" -exec chmod a+r {} \;
```

只想解决 build 的 uploads 报错时，可只执行：

```shell
sudo chown -R "$(id -u):$(id -g)" public/uploads
```

执行后用 `ls -ld . bootstrap/cache docker public storage resources/ai-kb` 抽查目录，并用 `find public resources/ai-kb -type f ! -perm -004 -print`、`find docker/appstore/apps -type f -name "config.yml" ! -perm -004 -print` 及 `find docker/appstore/apps -type f -path "*/ai-kb/*" -name "*.md" ! -perm -004 -print` 确认不再有缺少 others 读权限的文件。然后重试之前失败的静态文件访问、AI 知识库检索或 install/build/update。

## 失败处理

- `chmod`/`chown` 报权限不足：立即停止，提示使用有 root 权限的账户，或经 docker 以 root 执行；不要静默跳过。
- 任何步骤失败都立即停止并报告，不自动重试。

## 禁止项

| 错误做法 | 正确做法 |
|---------|---------|
| build 报 uploads EACCES 就 `rm` 删文件 | `chown` 修属主，保留数据 |
| 删整个 `public/uploads` 清场 | 最多清 `public/uploads/tmp`，别碰真实上传数据 |
| 对文件无差别 `chmod 777` | 可写目录 `chmod 775` + `chown` 回当前用户 |
| 把 `public` 所有文件强制改为 `644` | 用 `chmod a+r` 只补读权限，保留现有权限位 |
| 递归 `chmod 755` 整个项目 | 只对项目根目录执行 `chmod 755 .` |
| 不加 sudo 直接 chown root 文件 | 改属主需 root |

## Red Flags

- “uploads 复制失败，删掉再 build” → 不，`chown` 赋权，不丢数据。
- “777 一把梭最省事” → 不，按 install 的 775（目录）+ chown。
- “根目录不可穿越，递归 chmod 全仓库” → 不，只修项目根目录为 755。
- “静态文件 403，给整个项目所有文件加读权限” → 不，只对 `public` 普通文件执行 `a+r`。
- “权限不够就跳过这个目录” → 不，报告交用户处理 sudo。
