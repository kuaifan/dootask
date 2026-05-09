## 项目概述

Laravel 8 (LaravelS/Swoole) + Vue 2 (Vite) + Electron。开源任务/项目管理系统。

## 开发命令

所有命令通过 `./cmd` 脚本执行（不要直接运行 `php artisan` 等）：

- `./cmd dev` — 前端开发服务器（Node.js 20+）
- `./cmd prod` — 构建前端生产版本
- `./cmd artisan ...` / `./cmd composer ...` / `./cmd php ...` — PHP 相关命令

## Gotchas

### LaravelS/Swoole

- **避免在静态属性、单例、全局变量中存储请求级状态**——请求间共享进程，会导致数据串联和内存泄漏
- 构造函数、服务提供者、`boot()` 方法不会在每个请求重新执行
- 配置/路由变更需要 `./cmd php restart` 或容器重启才能生效
- 长生命周期逻辑（WebSocket、定时器）应复用现有模式，避免阻塞协程/事件循环

### 后端

- **非 REST 路由**：所有 API 通过 `Route::any('api/{resource}/{method}')` 路由到 `InvokeController`，URL 段映射为控制器方法（如 `api/project/lists` → `lists()`，带 action 则用双下划线：`api/project/invite/join` → `invite__join()`）
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

## Playwright 测试

- Playwright 测试结果放在 `tests/playwright-results/`，包含测试环境、测试用例、结果截图等信息

## 交互规范

- **提问时附带建议**：当需要向用户提问或请求澄清时，应同时提供具体的建议选项或推荐方案，帮助用户快速决策，而非仅抛出开放式问题

## 语言偏好

- 技术总结和关键结论优先使用简体中文，除非用户明确要求其他语言
