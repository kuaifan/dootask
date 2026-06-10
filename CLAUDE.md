## 项目概述

Laravel 8 (LaravelS/Swoole) + Vue 2 (Vite) + Electron。开源任务/项目管理系统。

## 开发命令

所有命令通过 `./cmd` 脚本执行（不要直接运行 `php artisan` 等）：

- `./cmd artisan ...` / `./cmd composer ...` / `./cmd php ...` — PHP 相关命令

### AI 不要主动执行的命令

以下命令仅由用户人工触发，AI 不要主动跑——包括"任务完成后 sanity check"、"看下能不能编译"等场景：

- `./cmd dev` — 用户已自行运行 dev server，改完会自己 reload；AI 再跑会争抢进程
- `./cmd prod` / `./cmd build` — 发版才用，走 `/release` 流程

前端代码改动只做 Edit/Write，不要为了"验证"启动 dev server。用户明确说"跑一下 / 出包"时除外。

## Gotchas

### LaravelS/Swoole

- **避免在静态属性、单例、全局变量中存储请求级状态**——请求间共享进程，会导致数据串联和内存泄漏
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

## DooTask AI 知识库 (ai-kb) 同步规则

`dootask/resources/ai-kb/` 是**专给 AI 助手 RAG 检索的功能知识库**，与人类文档（`dootask-website`）独立。它直接影响产品内 AI 助手能否正确回答用户的「X 怎么用」。

**何时必须同步更新 ai-kb**：

- 新增、修改、删除任何用户可见的功能、菜单、按钮、流程、字段
- 调整 API 行为（错误码、参数含义、返回结构）
- 引入新插件 / 微应用，或修改权限 / 角色定义

**操作步骤**：

1. 在 `resources/ai-kb/_meta/feature-map.yaml` 找到对应 `feature` 的 chunk 清单
2. 按 `resources/ai-kb/_schema/chunk-style.md` 风格修改对应 markdown
3. 更新 frontmatter 的 `last_verified` 字段为当前主程序版本号
4. 该功能没有对应 chunk 时，按 `resources/ai-kb/_schema/frontmatter.md` 规范新建

**禁止**：

- 跨章节指代（"如上图所示"、"在前面一节"）——RAG 切块后会丢失上下文
- 把 dootask-website 的人类教程或截图直接复制过来
- 单独提交「只改 ai-kb」的 PR——应与触发它的主代码改动同一个 PR
- 改产品代码但不改 ai-kb（PR review 应拦截）

**自动化**：合入 main 后 CI 自动调用 AI 插件的 `POST /kb/reindex` 增量入库；失败由 AI 容器重启自动 `ingest_all` 兜底。

## Playwright 测试

- Playwright 测试结果放在 `tests/playwright-results/`，包含测试环境、测试用例、结果截图等信息

## 交互规范

- **提问时附带建议**：当需要向用户提问或请求澄清时，应同时提供具体的建议选项或推荐方案，帮助用户快速决策，而非仅抛出开放式问题

## 语言偏好

- 回复一律使用简体中文，除非用户明确要求其他语言
