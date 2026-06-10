---
id: ai-assistant.send-message.howto
title: 让 AI 帮我发消息
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 发消息
  - AI 发群
  - 帮我发给小王
  - AI 自动发通知
  - 让 AI 发到群里
  - AI 代发
related_tools: [send_message]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 当前用户与目标对话/用户已存在会话或有权发起
negative:
  - 发送动作以当前登录用户名义执行，对方看到的是你发的（不是「AI 代发」标签）
  - AI 不会未经确认直接发，敏感/长内容会先弹消息预览让你确认
  - 不能发送已撤回/未上传完成的图片
last_verified: v1.7.90
---

# 让 AI 帮我发消息

## 这是什么
在 AI 浮窗描述要发的内容和对象，AI 会先调 `search_dialogs` 或 `search_users` 找到目标对话，再调 `send_message` 把消息发出去。消息以当前登录用户身份发送。

## 怎么问
- "给小王发：会议改到下午 3 点"
- "在『前端组』群里发：今晚 8 点上线，请大家配合"
- "把刚才那个总结发到项目讨论组"

## AI 会先确认
- **接收人/群歧义**：同名时列候选让你挑
- **长消息**：超过 200 字会先在浮窗里贴出预览，等你说「确认发」
- **跨群广播**：一次发多群通常会拆成多次确认

## 发送结果
发送成功后浮窗会显示消息 ID + 时间戳；如果对方禁言/不在好友列表/对话不存在，会回失败原因。

## 后续动作
- "撤回刚才那条" → 调 `update_message` 或 `delete_message`（依实现）
- "看下回复" → 调 `get_message_list`
- "发完顺便建个任务跟进" → 调 `create_task`

## 不支持
- 不能发语音/视频通话邀请
- 不能定时发送（无 schedule 工具）
- 不能伪装其他用户发送
- AI 不会自动把"@小王"翻成用户 mention，需要明确说"@用户 ID"

## 相关
- 找人：[[ai-assistant.search-users.howto]]
- 任务讨论区 @AI：[[ai-assistant.task-mention.howto]]
