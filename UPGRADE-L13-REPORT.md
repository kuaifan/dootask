# DooTask Laravel 8 → 13 升级报告

> 执行时间：2026-06-12 夜间（过夜任务）。分支：`light-spring`（本地 commits，未 push）。
> 配套镜像仓库：`/home/coder/workspaces/dockerfile`（本地 commit，未 push）。

## 结果总览

| 项目 | 结果 |
|---|---|
| 框架 | Laravel 8.83 → **13.15.0**，采用 13 新精简目录结构 |
| PHP / 运行时 | 8.0 → **8.4.21**，Swoole 4.x → **6.2.1**（phpswoole/swoole:6.2-php8.4 基底） |
| LaravelS | 3.7.19 → **3.8.7**，WebSocket / 21 个 Swoole Task / Swoole Table 架构**零重写** |
| 迁移 | `migrate:fresh --seed` **213 个全过**（含修复 2 个受 L11 `change()` 语义影响的历史迁移） |
| 测试 | phpunit 9 → 11.5，`php artisan test` **145 passed / 1 skipped / 0 failed**（skip 为套件自带的"无 Swoole 环境跳过"） |
| 安全 | `composer audit` **0 公告**（php-jwt 升 7.1 消除 CVE-2025-45769） |
| 运行时冒烟 | /health、登录、token 认证、WebSocket 握手(101+open帧)、Swoole Task 投递执行、头像生成、动态裁剪、404 兜底 **全过** |
| 运行中的现有实例 | **零接触**（全部验证在独立一次性容器栈完成） |

## Commits

dootask 仓库（light-spring 分支）：
1. `chore(upgrade): Laravel 8 直升 13（旧结构跑通）+ PHP 8.4 + 依赖升级与兼容修复`
2. `refactor(skeleton): 平移 Laravel 13 新目录结构`
3. `chore(upgrade): 收尾——compose 镜像 tag、php-jwt 7、升级报告`（本提交）

dockerfile 仓库（main 分支）：
- `feat(phpswoole): 新增 8.4 变体 Dockerfile`（`phpswoole/8.4.Dockerfile`）

## 容器镜像

- 新 Dockerfile：`dockerfile/phpswoole/8.4.Dockerfile`，CI 变体构建规则产出 tag **`kuaifan/php:8.4-swoole-8.0.rc21`**（= `8.4-` + config.ini 的 imageTag）。
  - ⚠️ tag 里的 "swoole-8.0" 来自现有 config.ini，命名易混淆。如想要更干净的 tag（如 `kuaifan/php:swoole6-php8.4`），需调整 build.sh 变体命名规则或 config.ini，建议你定夺。
  - 本地已构建并打好同名 tag（含 8.4-test 别名），`docker-compose.yml` 已指向新 tag。
  - 本地构建时 dooso 阶段用旧镜像提取的 doo.so 预编译产物替代（本机无 private-repo 源码）；**Dockerfile 本身的 golang 构建阶段与现行完全一致**，CI 会照常从源码构建。
- 关键变化：redis 扩展用 base 自带 6.3.0（弃 phpredis-5.3.2 tarball）；imagick 走 pecl（3.8.1）；移除显式 libzip4。
- 镜像验证：PHP 8.4.21 / swoole 6.2.1 / redis 6.3.0 / imagick 3.8.1 / gd / ffi / ldap / gmp / pdo_mysql 等 12 个关键扩展齐全，doo.so + /entrypoint.sh 就位。

## 依赖变更（composer.json）

**移除**：fideloper/proxy（→框架内置 TrustProxies）、fruitcake/laravel-cors（→内置 HandleCors，abandoned）、facade/ignition（13 内置错误页）、laravel/sail、madnest/madzipper（→原生 ZipArchive：`Base::zipAddFiles`）、手动钉的 symfony/mailer ^6。

**升级**：laravel/framework ^13.0、laravel/tinker ^3.0、predis ^2.3（2.4.1）、maatwebsite/excel ^3.1.69、mews/captcha ^3.5、laravolt/avatar ^6.5、directorytree/ldaprecord-laravel ^4.0、overtrue/pinyin ^5.3、orangehill/iseed ^3.8、firebase/php-jwt ^7.1、phpoffice/phpword ^1.4、phppresentation ^1.2、smalot/pdfparser、matomo/device-detector ^6.5；dev：phpunit ^11.5、collision ^8.6、ide-helper ^3.7、migrations-generator ^7.4、mockery ^1.6、faker ^1.24、hhxsv5/laravel-s ~3.8.0。

**锁定**：guanguans/notify ~1.28.0（5.x API 全重写，另行排期）；**symfony/console ^7.4**（关键：LaravelS Portal 与 symfony/console 8 的 `configure(): void` 类型断言不兼容，7.4 LTS 是 Laravel 13 官方支持区间）。

## 主要代码改动

### 兼容性修复（阶段 1）
- `$dates` 属性移除（L10）：`AbstractModel` 改 `getCasts()` 合并默认 datetime 列（子模型 `$casts` 同名键优先，保持原全局语义）；UserTaskBrowse/UserRecentItem/ManticoreSyncFailure 改 `$casts`
- Carbon 3：4 处 `diffInSeconds` 显式 absolute + `(int)`（C3 默认返回**带符号浮点**）
- LdapRecord v4：`config/ldap.php` `use_ssl/use_tls` → `use_tls/use_starttls`（**env 变量名保持 LDAP_SSL/LDAP_TLS 不变**，老部署无感）；`LdapUser::$objectClasses` 补 array 类型
- pinyin v5 静态 API：`Base::getFirstCharter`（abbr+surname）、`Base::cn2pinyin`（permalink）
- laravolt/avatar 6.5 上游 bug：纵向对齐传 `middle` 而 intervention 4.1.3 枚举只认 `center` → `App\Module\PatchedAvatar` 子类覆写 `buildAvatar()`；头像响应改 `response()->file()`（v6 `save()` 返回 Image 对象）
- Symfony Console 8 兼容（保留在代码里，虽然最终锁了 7.4）：`ManticoreSyncLock::handleSignal` 新签名 + pcntl 回调解耦
- **非 Swoole 运行时守卫**（artisan/测试上下文原本会炸 `Target class [swoole] does not exist`）：`AbstractTask::task()`（覆盖全部 21 个 Task 类的投递）、`PushTask::push()`、`AbstractData/OnlineData`（swoole table 缺失时返回默认值）
- PHP 8 警告硬化（测试中警告转异常暴露的老问题）：`Setting::getSettingAttribute`、`Ihttp::ihttp_request` 缺键访问加 `??` 守卫；`Report::getLastOne` 隐式可空参数（PHP 8.4 deprecation）

### 迁移修复（重要！影响全新安装）
Laravel 11+ 的 `change()` 会**丢弃未重申的修饰符**（旧版 doctrine/dbal 会保留）。两个历史迁移在 fresh 安装时会产出错误 schema（NOT NULL 无默认值，曾导致后续 backfill 迁移失败）：
- `2023_12_07_160642_update_owner_add_index_some_20231217.php`：15 个 `integer(...)->change()` 全部重申 nullable/default/comment（原始值从迁移历史链逐列还原）
- `2025_08_10_215202_update_files_name_length_to_200.php`：`files.name` 重申 nullable/default/comment

**已部署的生产库不受影响**（这两个迁移早已在 L8 下执行过，不会重跑）。

### 新目录结构（阶段 3）
- `bootstrap/app.php`：`Application::configure()` 链式（withRouting/withMiddleware/withExceptions）
- 删除：Http/Console Kernel、Exceptions/Handler、RouteServiceProvider、EventServiceProvider、AuthServiceProvider、BroadcastServiceProvider、7 个框架默认中间件子类 + TrustHosts
- 迁入新位置：`webapi` 别名、信任代理/TrimStrings/CSRF 排除（配置 API）、api 限流 + 14 个模型观察者 + Registered 监听 → `AppServiceProvider::boot`、图片裁剪 → `App\Exceptions\ImagePathHandler`（withExceptions 调用）
- `config/app.php` 移除 providers/aliases 数组（用框架默认集，补齐 9~13 新增 provider；Redis alias 因 ext-redis 原生类存在不会触发冲突）
- `artisan`、`public/index.php` 换 13 骨架版；新增 `bootstrap/providers.php`
- 保留：InvokeController 动态路由模式（项目约定）、WebApi 中间件、routes/web.php 全部路由

### 测试
- `phpunit.xml` 迁移 11 schema（coverage→source）；`.gitignore` 加 `/.phpunit.cache`
- `UserImportParseTest`：部门存在性断言改为自建数据 + DatabaseTransactions（原测试硬编码部门 ID 3/5，fresh 库上与升级无关地必败——同一提交引入的测试与查库逻辑脱节）

## 验证证据（一次性容器栈：l13-mariadb 10.7.3 + l13-redis + l13-php）

```
php artisan --version            → Laravel Framework 13.15.0
migrate:fresh --seed             → 213 DONE, exit 0（两库各验一次）
php artisan test                 → 145 passed, 1 skipped (368 assertions)
GET  /health                     → 200 "ok"
POST /api/users/login（错误密码） → {"ret":0,...}（结构化错误，含验证码策略）
GET  /api/users/info + token     → {"ret":1,"msg":"success","userid":1,"identity":["admin"]}
WS   /ws?action=web&token=...    → upgrade=true status=101，收到 {"type":"open","data":{"fd":4,"ud":1}}
Swoole Task                      → LineTask×2/PushTask 投递执行完毕（task_workers 软删 + error NULL）
GET  /avatar/张三.png            → 200，合法 PNG（intervention v4 + imagick 3.8）
GET  /uploads/.../crop/percentage=40x0 → 200（新结构 withExceptions 路径）
composer audit                   → 0 advisories
```

一次性栈尚在运行（供你晨检复现）。清理命令：
```bash
docker rm -f l13-php l13-mariadb l13-redis && docker network rm l13-net
# 仓库根目录的 .env 是为一次性栈造的测试配置（已 gitignore），可删
```

## 遗留风险与 TODO

1. **LaravelS 3.8.7 + Laravel 13 属"约束开放、未经上游宣告"的组合**——本次 HTTP/WS/Task/Timer-free 路径全部实测通过，但建议在测试环境全功能回归后再发版；上游如发 3.8.8+ 留意跟进。
2. **未实测路径**（缺外部服务，代码层面已确认 API 未变）：SMTP 邮件（guanguans/notify 1.28 + SystemController 的 symfony mailer DSN 直连）、LDAP 登录/同步（ldaprecord 2→4 是大版本跳跃，**建议优先人工回归**）、友盟推送、OnlyOffice JWT（php-jwt 7 强制 HMAC 密钥 ≥32 字节，Laravel 标准 APP_KEY 51 字符无虞，仅自定义短 APP_KEY 的部署会受影响）。
3. **guanguans/notify 1.28**（2024-01 停更）在 PHP 8.4 上游未声明测试；邮件链路回归时留意。建议排期迁 5.x。
4. **`php artisan route:list` 会因 ApproveController 构造器在"审批应用未安装"时抛异常而失败**——既有行为（L8 同样如此），与本次升级无关，但新人易误判。
5. **镜像 tag 命名**：`kuaifan/php:8.4-swoole-8.0.rc21` 中 "swoole-8.0" 历史包袱，见上文容器章节。
6. L13 的 `cache.serializable_classes` 加固未在本项目 config/cache.php 显式配置（保持升级前行为）；如未来要启用白名单，需排查缓存对象的代码。
7. 前端（Vue/Electron）、ai-kb 未动——本次为纯后端框架/运行时升级，无用户可见功能变化。
8. 发版前建议：测试环境用新镜像跑 `./cmd update` 全流程 + Playwright 回归；`bin/inotify` 开发热重载路径未实测（dev 场景）。
