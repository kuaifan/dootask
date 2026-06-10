---
id: messenger.task-mention.concept
title: 对话内 #任务 引用与创建
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么at任务
  - 群里发任务
  - 怎么发个任务链接
  - 消息里引用任务
  - 怎么把任务发到群
  - 群里建任务
related_tools: [create_task, send_message]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - "#任务 弹窗仅在群对话支持，单聊不显示创建按钮"
  - 引用任务需当前用户有该任务的可见权限，否则群其他成员看到的是「无权访问」占位
  - 不支持引用未保存的草稿任务，必须先创建
last_verified: v1.7.90
---

# 对话内 #任务 引用与创建

在 messenger 输入框中输入 `#` 会触发任务面板，可：1) 引用已有任务（生成可点击的 #任务名 链接卡）；2) 直接基于输入内容快速创建新任务（自动归属当前任务群对应的项目或弹出项目选择）。

## 定义

- 任务引用：以 `<span class="mention task" data-id="xxx">#任务名</span>` 形态嵌入消息文本
- 任务速建：通过 #提及面板的「+ 创建新任务」入口走完整流程，详见 [[task.create.howto.via-mention]]

## 关键属性

- 引用渲染：群里其他成员看到链接卡片，标题、负责人、状态实时刷新
- 权限保护：被引用任务对查看人不可见时显示"无权访问"
- 在任务群（group_type=task）里 #提及会优先把任务挂到本群对应的源任务上

## 与其他类似语法

- `@用户`：人员提及，详见 @提及成员
- `#任务`：任务引用 / 创建
- `~文件`：文件引用（通过 sendfileid 发分享链接）

## 与转发任务的区别

- #任务 引用：在文本内嵌入链接卡
- sendtaskid 转发：发一条独立的任务卡片消息（带留言），适合「把任务派到群里讨论」

## 不支持

- 不支持在单聊里"快速创建任务"卡片
- 不支持 # 多个任务并列（每条消息逐个 # 即可，但不能"批量创建"）
