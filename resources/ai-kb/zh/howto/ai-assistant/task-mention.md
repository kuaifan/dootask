---
id: ai-assistant.task-mention.howto
title: 任务讨论区里 @AI
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 任务里 @AI
  - 在任务下 at AI
  - AI 分析任务
  - 让 AI 看下这个任务
  - 任务讨论 @ 机器人
  - AI 接进任务
related_tools: [send_task_ai_message]
related_pages: [task_detail]
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已开启至少一个支持 tool call 的模型
  - 当前用户可访问该任务
negative:
  - "@AI 后的提问会被全部讨论区成员看到（公开提问）"
  - "AI 仅读取本任务的标题、描述、子任务、评论作为上下文，不会跨任务查"
  - "群里 @AI 与任务讨论区 @AI 是两条不同链路，结果格式略有差异"
last_verified: v1.7.90
---

# 任务讨论区里 @AI

## 这是什么
在任务详情页的「讨论」区输入框 @AI（出现在 @ 下拉列表里）然后提问，AI 会以任务上下文（标题/描述/子任务/讨论历史）为基础回答，结果同步发到讨论区，所有成员可见。

## 入口
- 桌面端：任务详情页 → 右侧/底部「讨论」标签 → @ → 选「AI」
- 移动端：任务详情页 → 「讨论」→ @ → 选「AI」

## 操作步骤
1. 打开任务详情，切到「讨论」
2. 在评论输入框输入 `@` 弹出候选
3. 选「AI 助手」
4. 输入提问（如"帮我看下这个任务还差什么"）
5. 发送，AI 会以消息形式回答到同一条讨论

## 适合问的
- "总结一下这个任务的进展"
- "根据描述帮我拆 5 个子任务"
- "讨论里大家在争什么？给我个概要"
- "下一步建议怎么做"

## 与浮窗 AI 的区别
- 浮窗：单人会话，结果只你能看到，可调全部工具
- 任务 @AI：公开讨论，回答全员可见，主要做总结/分析，少调改写类工具

## 不支持
- 不能 @AI 后让它跨任务汇总（要总结多个任务请用浮窗）
- 不能私聊它（@AI 的所有回复都对讨论区可见）
- 任务设有可见用户白名单时，名单外的人看不到 AI 回复

## 相关
- 子任务建议：[[ai-assistant.subtask-suggest.howto]]
- 任务总结：[[ai-assistant.task-summary.howto]]
- 浮窗入口：[[ai-assistant.entry.howto]]
