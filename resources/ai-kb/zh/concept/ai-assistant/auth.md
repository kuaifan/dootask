---
id: ai-assistant.auth.concept
title: AI 助手鉴权（stream_key）
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 鉴权
  - AI 授权
  - stream_key
  - AI token
  - AI 流凭证
related_tools: []
related_pages: []
prerequisites:
  - 用户已登录
  - 当前用户允许聊天
negative:
  - stream_key 是一次性凭证，发完一条消息就失效
  - 流式接口 /ai/invoke/stream/* 不在主仓库，由 dootask-ai 容器提供
  - 普通用户不需要关心 stream_key，前端自动获取
last_verified: v1.7.90
---

# AI 助手鉴权（stream_key）

## 定义
AI 助手发送提问前必须先调用 `POST api/assistant/auth` 生成一次性 `stream_key`，再用它去开 SSE 流（`/ai/invoke/stream/{stream_key}`），既复用登录态又避免把用户 token 暴露给 ai 容器。

## 数据流
1. 用户点发送
2. 前端 `POST api/assistant/auth`，参数：`model_type`、`model_name`、`context`（JSON）、`locale`（zh/en，缺省取请求语言，语言包含 zh 视为 zh，否则 en）
3. 后端 `AssistantController::auth` 校验登录 + 聊天权限，写入临时凭证
4. 返回 `{stream_key: "xxx"}`
5. 前端开 SSE：`ai/invoke/stream/{stream_key}`
6. ai 容器消费凭证、转发上游模型，推回 `append/replace/done`

## 关键约束
- **一次性**：一个 stream_key 仅能开一次 SSE 流
- **绑定用户**：内嵌 `userid`，越权使用被拒
- **携带 context**：对话历史在 auth 阶段写入；ai 容器不反向查 DooTask 库
- **插件校验**：`AssistantController` 构造函数先做 `Apps::isInstalledThrow('ai')`；未装直接抛错

## 权限校验
`User::auth()->checkChatInformation()`：未登录 / 被禁止聊天 / 激活异常都会拒绝。

## 相关接口
| 接口 | 用途 |
|---|---|
| `POST api/assistant/auth` | 生成 stream_key |
| `GET api/assistant/models` | 拉可用模型列表 |
| `POST api/assistant/session/list` | 历史会话 |
| `POST api/assistant/session/save` | 保存当前会话 |
| `POST api/assistant/session/delete` | 删除 / 清空 |

## 不支持
- 不支持复用 stream_key
- 不支持匿名调用
- 不支持终端用户直接管理 token

## 相关
- [[ai-assistant.privacy.concept]]
- [[ai-assistant.streaming.concept]]
