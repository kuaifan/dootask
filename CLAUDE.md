# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

DooTask 是一套开源的任务/项目管理系统，支持看板、任务、子任务、评论、对话、文件、报表等协作能力。

- **后端**：Laravel 8，运行在 LaravelS/Swoole 常驻进程上
- **前端**：Vue 2 + Vite
- **桌面端**：Electron 壳，核心逻辑复用 Web 前端

## 开发命令

所有命令通过 `./cmd` 脚本执行，确保与 Docker/容器环境一致：

```bash
# 服务管理
./cmd up              # 启动容器
./cmd down            # 停止容器
./cmd restart         # 重启容器
./cmd reup            # 重新构建并启动

# 开发构建
./cmd dev             # 启动前端开发服务器（需要 Node.js 20+）
./cmd serve           # dev 别名
./cmd prod            # 构建前端生产版本
./cmd build           # prod 别名

# Laravel/PHP
./cmd artisan ...     # 运行 Laravel Artisan 命令
./cmd composer ...    # 运行 Composer 命令
./cmd php ...         # 运行 PHP 命令

# Electron
./cmd electron        # 构建桌面应用

# 配置管理
./cmd port <端口>     # 修改服务端口
./cmd url <地址>      # 修改访问地址
./cmd env <键> <值>   # 设置环境变量
./cmd debug [true|false]  # 切换调试模式

# 数据库
./cmd mysql backup    # 备份数据库
./cmd mysql recovery  # 恢复数据库

# 其他
./cmd install         # 一键安装
./cmd update          # 升级项目
./cmd repassword      # 重置管理员密码
./cmd doc             # 生成 API 文档
./cmd https           # 配置 HTTPS
```

## 代码架构

### 后端 (`app/`)

**Controller (`app/Http/Controllers/Api/`)**：API 控制器，负责路由入口、参数校验、编排调用模型/模块、组装响应。保持控制器「薄」，业务异常通过 `App\Exceptions\ApiException` 抛出。

**Model (`app/Models/`)**：Eloquent 模型，负责表结构映射、关系、访问器/修改器、查询 Scope。避免在模型中堆积复杂业务逻辑。

**Module (`app/Module/`)**：跨控制器/跨模型的业务逻辑与独立功能子域：
- 外部集成：`AgoraIO/`、`Manticore/`
- 通用工具：`Lock.php`、`TextExtractor.php`、`Image.php`、`AI.php`
- 复杂业务逻辑：`Base.php`（核心业务）、`Doo.php`、`Timer.php`

**Tasks (`app/Tasks/`)**：Swoole 异步任务，用于后台处理：
- WebSocket 消息推送：`WebSocketDialogMsgTask.php`、`PushTask.php`
- 定时任务：`LoopTask.php`、`AutoArchivedTask.php`
- 搜索同步：`ManticoreSyncTask.php`

**Observers (`app/Observers/`)**：Eloquent 观察者，监听模型事件（created/updated/deleted）自动触发相关逻辑。

**Services (`app/Services/`)**：服务类，如 `WebSocketService.php`、`RequestContext.php`。

### 前端 (`resources/assets/js/`)

```
├── app.js, App.vue      # 应用入口与根组件
├── components/          # 通用与业务组件（看板、文件预览、聊天）
├── pages/               # 页面级组件（登录、项目、任务、消息、报表）
├── store/               # Vuex 状态管理
│   ├── state.js         # 状态定义
│   ├── mutations.js     # 同步修改
│   ├── actions.js       # 异步操作（含 API 调用封装）
│   └── getters.js       # 计算属性
├── routes.js            # 前端路由
├── functions/           # 业务函数
├── utils/               # 工具函数
├── directives/          # Vue 自定义指令
├── mixins/              # Vue 混入
└── language/            # 国际化翻译
```

API 调用应使用 `store/actions.js` 中已有的封装，避免在组件中散落 axios/fetch。

### LaravelS/Swoole 注意事项

- **避免在静态属性、单例、全局变量中存储请求级状态**——防止请求间数据串联和内存泄漏
- 构造函数、服务提供者、`boot()` 方法不会在每个请求重新执行
- 配置/路由变更需要 `./cmd php restart` 或容器重启才能生效
- 长生命周期逻辑（WebSocket、定时器）应复用现有模式，避免阻塞协程/事件循环

## 数据库

- 所有表结构变更必须通过 Laravel migration，禁止直接改库
- 使用 Eloquent 模型访问数据库

## 前端弹窗文案

调用 `$A.modalXXX`、`$A.messageXXX`、`$A.noticeXXX` 时，内部会自动处理 `$L` 翻译，调用方不要额外包 `$L`。仅当显式传入 `language: false` 时，才由调用方自行处理翻译。

## 交互规范

- **提问时附带建议**：当需要向用户提问或请求澄清时，应同时提供具体的建议选项或推荐方案，帮助用户快速决策，而非仅抛出开放式问题

## 语言偏好

- 技术总结和关键结论优先使用简体中文，除非用户明确要求其他语言

## 扩展规则

详见 @.claude/rules/graphiti.md 了解 Graphiti 长期记忆集成。
