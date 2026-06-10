---
id: messenger.send.howto.text
title: 发送文字消息
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么发消息
  - 发文字消息
  - 怎么聊天
  - 发送文本
  - 发个字
  - 群里发消息
related_tools: [send_message]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 单条文字消息长度上限 5000 字（不含 HTML 标签），超出会自动转为长文本文件消息（.htm / .md）
  - 消息内容最大 200000 字，超过这个上限直接报错
  - 内容为空（去标签后 0 字）不允许发送
last_verified: v1.7.90
---

# 发送文字消息

发送文字消息（sendtext）是 DooTask 即时通讯最常用的操作。支持纯文本、富文本 HTML、Markdown、@提及、#任务、~文件、引用回复、编辑、撤回等。

## 入口

- 桌面端：打开任意会话 → 底部输入框，回车 / Cmd+Enter / Ctrl+Enter 发送
- 移动端：进会话 → 底部输入框 → 「发送」按钮

## 操作步骤

1. 在输入框输入文本
2. 可选：通过 / 或 工具栏插入富文本（加粗、列表、代码块、图片、emoji）
3. 可选：输入 @ 触发提及面板（输入 # 触发任务选择、~ 触发文件选择）
4. 提交（默认 Enter 发送，Shift+Enter 换行；可在设置里互换）

## 字段默认值

| 字段 | 默认值 |
|---|---|
| text_type | html（富文本）；传 md 或 markdown 走 Markdown 渲染 |
| reply_id | 0（不引用）；详见引用回复 |
| silence | no（正常推送，触发未读提醒）|

## 长内容降级

文字内容超过 5000 字时，后端自动把消息保存为 .htm / .md 文件，作为长文本附件发送，并展示前 200 字作为预览。

## 不支持

- 不支持纯空白 / 只有图片占位符的文本（reallen=0 时报错）
- 不支持直接发送超长内容到会话流，单条内容上限 200000 字符
- 不支持向已离职用户发起新单聊
