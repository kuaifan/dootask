## 项目概述

Laravel 13 (LaravelS/Swoole, PHP 8.4) + Vue 2 (Vite) + Electron。开源任务/项目管理系统。

## 开发命令

所有命令通过 `./cmd` 脚本执行（不要直接运行 `php artisan` 等）：

- `./cmd artisan ...` / `./cmd composer ...` / `./cmd php ...` — PHP 相关命令

### AI 不要主动执行的命令

以下命令仅由用户人工触发，AI 不要主动跑——包括"任务完成后 sanity check"、"看下能不能编译"等场景：

- `./cmd dev` — 用户已自行运行 dev server，改完会自己 reload；AI 再跑会争抢进程
- `./cmd prod` / `./cmd build` — 发版才用，走 `/release` 流程

前端代码改动只做 Edit/Write，不要为了"验证"启动 dev server。用户明确说"跑一下 / 出包"时除外。

### 质量门禁（改完代码必须自查，CI 同步在跑，见 .github/workflows/tests.yml）

- `./cmd composer stan` — phpstan（level 1 + baseline，存量已封存，新增错误必须清零）
- `npm run lint` — ESLint（error 必须为 0；warn 是存量遗留，见 eslint.config.mjs 注释）
- `npm run check:lang` — 校验前端 `$L()` 字面量是否已登记到 `language/original-web.txt`
- 改动控制器 public 方法或路由后跑 `./cmd artisan doc:api-map` 重新生成对照表

## 代码检索地图（先查表，再 grep）

- API URL ↔ 控制器方法对照：`routes/api-map.md`（生成式文件，勿手改）
- 前端事件总线（mitt）收发对照：`docs/events-map.md`（`npm run events:map` 重新生成）
- `$A` / `$L` 全局工具类型声明：`types/dootask-globals.d.ts`（新增 `$A` 方法须同步此文件）

## 架构增量规则（只约束新增代码，存量"动到哪迁到哪"）

- **巨型文件冻结**：不再往 `ProjectController`、`UsersController`、`DialogController`、`app/Module/Base.php`、`resources/assets/js/store/actions.js` 新增方法/函数；新功能领域开新控制器或新模块文件（动态路由天然支持多控制器）
- **业务编排归层**：跨模型的业务流程写在 `app/Module/`（或 `app/Services/`），模型只保留数据访问与自身状态变更；Swoole Task 只做投递与调用，不直接编排业务
- **配置读取**：业务代码禁止直接 `env()`，统一走 `config()`（项目自有配置集中在 `config/dootask.php`）

## Gotchas

### LaravelS/Swoole

- **避免在静态属性、单例、全局变量中存储请求级状态**——请求间共享进程，会导致数据串联和内存泄漏
  - 要存请求级状态，用 `RequestContext::save('key', $value)` / `RequestContext::get('key')`（参考 `User::authInfo()` 的用法，见 `app/Services/RequestContext.php`）
- 构造函数、服务提供者、`boot()` 方法不会在每个请求重新执行
- 配置/路由变更需要 `./cmd php restart` 或容器重启才能生效
- 长生命周期逻辑（WebSocket、定时器）应复用现有模式，避免阻塞协程/事件循环

### 后端

- **非 REST 路由**：API 控制器（继承 `InvokeController`）在 `routes/web.php` 按资源注册路由，URL 段映射为控制器方法（如 `api/project/lists` → `lists()`，带 action 则用双下划线：`api/project/invite/join` → `invite__join()`）
  - 路由最多两段：方法名最多一个双下划线（`method__action`），不支持 `method__action__xxx`（无对应路由，访问 404）
- **响应格式**：统一使用 `Base::retSuccess($msg, $data)` / `Base::retError($msg)`，返回 `{"ret": 1, "msg": "...", "data": {...}}`——不要用 `response()->json()`
- 业务异常通过 `App\Exceptions\ApiException` 抛出，不要用通用 Exception
- 模型继承 `AbstractModel`，使用 `Model::createInstance($params)` 创建——不要用 `new Model()` 或 `Model::create()`
- 认证使用 `Doo::userId()`——不要用 `auth()->user()`
- 参数校验在控制器方法中手动进行——不要创建 FormRequest 类
- 异步任务使用 Swoole Task（`app/Tasks/`）——不要用 Laravel Queue
- `app/Module/` 存放跨控制器/跨模型的业务逻辑（非标准 Laravel 目录）
- 所有表结构变更必须通过 Laravel migration，禁止直接改库

### 前端

- API 调用使用 `store.dispatch("call", params)`，不要在组件中直接 axios/fetch
- `$A.modalXXX`、`$A.messageXXX`、`$A.noticeXXX` 内部自动处理 `$L` 翻译，调用方不要额外包 `$L`。仅当传入 `language: false` 时由调用方自行处理翻译

### 国际化

- 新增用户可见文本须追加原文（简体中文）到：前端 `language/original-web.txt`，后端 `language/original-api.txt`（去重）
- 前端翻译用 `$L("文本")`，动态值用 `(*)` 占位：`$L('共(*)条', n)`——禁止拼接翻译

## ai-kb 同步规则

`resources/ai-kb/` 是产品内 AI 助手 RAG 检索的功能知识库（目录结构、写作规范、索引机制见其 `README.md` 与 `_schema/`）。

- **同步时机**：改动用户可见的功能/菜单/按钮/流程/字段、API 行为（错误码、参数含义、返回结构）、插件/微应用、权限/角色定义时，必须在同一次提交中同步更新 ai-kb，不要把 ai-kb 改动单独拆成一个提交
- **怎么改**：在 `_meta/feature-map.yaml` 找到对应 feature 的 chunk 清单，按 `_schema/chunk-style.md` 与 `_schema/frontmatter.md` 修改或新建 chunk，并把 frontmatter 的 `last_verified` 更新为当前主程序版本号
- **改完即止**：无需触发任何索引操作，插件容器启动时会自动对账收敛

## Playwright 测试

- Playwright 测试结果放在 `tests/playwright-results/`，包含测试环境、测试用例、结果截图等信息

## 交互规范

- **提问时附带建议**：当需要向用户提问或请求澄清时，应同时提供具体的建议选项或推荐方案，帮助用户快速决策，而非仅抛出开放式问题

## 语言偏好

- 回复一律使用简体中文，除非用户明确要求其他语言
