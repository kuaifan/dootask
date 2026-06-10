---
id: ai-assistant.streaming.concept
title: AI 助手流式输出
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 流式回答
  - 边写边出
  - AI 一边打字一边显示
  - SSE 流
  - AI 实时输出
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 不支持 WebSocket 推送，固定走 SSE
  - 不支持手动重试单条；流断后该 message 标记 error，需重新发送
  - 同会话并发流会被前一个的发送动作清掉
last_verified: v1.7.90
---

# AI 助手流式输出

## 定义
AI 助手通过 **SSE（Server-Sent Events）** 接收上游 LLM 的逐 token 输出，每收到一个 chunk 就追加到当前 message 的 `rawOutput`，呈现 ChatGPT 般「边写边出」的效果。

## 数据流
1. `POST api/assistant/auth` → 拿 stream_key
2. `EventSource ai/invoke/stream/{stream_key}`
3. 监听三类事件：
   - `append` → 追加（`entry.rawOutput += chunk`）
   - `replace` → 整段替换（`entry.rawOutput = chunk`）
   - `done` → 流结束（`entry.status = 'completed'`）
4. 断流 / 异常 → `handleStreamFailed`

## 事件类型

| 事件 | 用途 |
|---|---|
| `append` | 普通增量 token（最常见） |
| `replace` | 整段替换（工具结果回填、思考链重写） |
| `done` | 流结束，payload 含 `error` 字段表示上游报错 |

## 状态机
每条 AI message 4 个状态：
- `waiting` → 已发出但首个 chunk 未到
- `streaming` → 正在接收 chunk
- `completed` → `done` 正常收到
- `error` → 上游错误、连接失败、用户中断

## 并发与归属
- 每个流绑定 `owner = {sessionKey, sessionId, localId}`
- 用户切到其他会话后，原流仍后台跑，结束时写回原会话存储
- 发新问题前会清掉同会话的所有活跃流，防止两段对话错位

## 中断恢复保护
- 关浮窗 / 页面崩溃后，下次加载会话发现 message 仍是 `streaming/waiting` 则归一为 `error`
- 避免「永远转圈」（`sanitizeResponsesForPersist`）

## 滚动行为
- 视图在最底部时新 chunk 自动 `scrollToBottom`
- 用户向上翻历史时（距底 > 20px）不强制下拉

## 不支持
- 不支持 WebSocket
- 不支持 chunk 级别撤销
- 不支持自动重试断流

## 相关
- [[ai-assistant.stop.howto]]
- [[ai-assistant.modal.concept]]
- [[ai-assistant.session-save.howto]]
