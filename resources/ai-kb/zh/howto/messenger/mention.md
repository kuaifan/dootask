---
id: messenger.send.howto.mention
title: "@提及成员"
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么at人
  - "@某人"
  - 群里at所有人
  - 提及成员
  - 怎么at所有人
  - at全体
related_tools: [send_message, search_users]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 单聊里 @对方 不会产生额外提醒，因为单聊本身就是直达
  - "@所有人（all）只对群有效；项目讨论组限项目成员，部门群限部门成员"
  - "@已退群成员不会触发提醒，仅作为文本展示"
last_verified: v1.7.90
---

# @提及成员

@提及（mention）会在被提及成员的「消息列表 → 该会话」上显示红点和「@」标记，并触发桌面 / 移动端通知。即使设置了免打扰，被 @ 的消息仍会强制提醒。

## 入口

- 桌面端：输入框输入 `@` 触发下拉成员搜索
- 移动端：长按输入框上方「@」快捷按钮 或 输入 `@` 字符
- 也可在工具栏点击「@」图标弹出成员列表

## 操作步骤

1. 输入 `@` 后开始输入对方昵称 / 邮箱
2. 在弹出的候选列表选中目标
3. 输入框生成可点击的 `@张三` 蓝色标签
4. 按回车发送

## 特殊提及类型

| 写法 | 含义 |
|---|---|
| @某成员 | 单点提醒该成员 |
| @所有人 | 群级广播；强制提醒所有成员（含免打扰） |
| 邮箱形式 @user@xxx | 系统自动转换为对应用户 mention |

## 与 #任务 / ~文件 的区别

- `@` 后跟用户：人员提及
- `#` 后跟任务：任务引用，参见任务对话内提及创建 [[task.create.howto.via-mention]]
- `~` 后跟文件：文件引用

## 不支持

- 不支持在 markdown 模式下用纯文本 `@xxx` 触发提醒，必须用富文本 mention 标签
- 不支持对机器人 @提及（机器人不会被加入未读 mention 列表）
