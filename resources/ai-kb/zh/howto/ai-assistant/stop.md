---
id: ai-assistant.stop.howto
title: 中断 AI 助手回答
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 停止 AI 回答
  - 让 AI 停下来
  - 取消 AI 输出
  - AI 一直在打字
  - 终止流式
  - 重发问题
related_tools: []
related_pages: []
prerequisites:
  - AI 助手当前有一条流式回答正在输出
negative:
  - 中断后的部分回答保留，但状态变为 error，不会再续传
  - 中断不向上游模型扣费返还（看服务商）
  - 不支持「暂停后再继续」，只能停 + 重新提问
last_verified: v1.7.90
---

# 中断 AI 助手回答

## 何时需要中断
- AI 跑偏，回答方向不对，想立即停下重提问
- 模型陷入冗长输出
- 想换个模型重发同一问题

## 入口
当 AI 正在流式输出时，主动中断方式：
1. **新建会话**：右上角「新建会话」会先清掉当前流再开空会话（见 [[ai-assistant.new-chat.howto]]）
2. **关闭浮窗**：关闭浮窗会清理所有活跃 SSE 连接（见 [[ai-assistant.close.howto]]）
3. **发新问题**：发起新提问会自动清掉同会话的残留 SSE 流

## 中断时发生了什么
1. 前端调 SSE 客户端的 `unsunscribe()` 切断连接
2. 已收到的部分回答（rawOutput）保留在 message
3. 该 message 的 `status` 立即变为 `error`，附「会话中断」错误文案
4. message 持久化到 session（带 error 状态），刷新不会再卡 loading

## 流式中断恢复保护
- 即使页面崩溃 / 刷新，加载会话时如果发现某条 message 仍是 `streaming` 或 `waiting`，会自动归一为 `error`
- 避免「永远转圈」（见 `sanitizeResponsesForPersist`）

## 重新提问
中断后想重发同一问题：
1. 鼠标移到刚才被中断的提问气泡
2. 点编辑图标 → 修改后再发
3. 或直接在输入框输入新的问题（按 ↑ 可调出最近 50 条历史输入）

## 不支持
- 不支持「暂停 → 继续」，只能停 + 重发
- 不支持只中断单条；同会话所有进行中的流会一起停
- 不支持中断后撤销（再次发送会生成新一条 message）

## 相关
- [[ai-assistant.streaming.concept]]
- [[ai-assistant.close.howto]]
- [[ai-assistant.new-chat.howto]]
