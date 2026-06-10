---
id: messenger.bot.concept
title: 系统机器人会话
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 系统机器人
  - 机器人会话
  - 任务提醒机器人
  - 签到机器人
  - 谁给我发的系统消息
  - 这个机器人是谁
related_tools: [send_message]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 机器人会话不能被踢出 / 屏蔽，会随对应业务消息长期保留
  - 不能 @ 机器人触发动作，机器人是单向推送通道
  - 机器人不会触发未读 mention 数（不被加入 @ 提及统计）
last_verified: v1.7.90
---

# 系统机器人会话

DooTask 内置一组「系统机器人」（bot=1 的特殊用户），每个机器人有自己的单聊会话，用来推送特定类型的系统消息。出现在用户的消息列表里，但发送者是机器人而非真人。

## 定义

- bot_type=system-msg：系统消息（默认）
- bot_type=task-alert：任务提醒（截止 / 分配 / 完成 / 修改）
- bot_type=todo-alert：待办提醒
- bot_type=check-in：签到打卡通知
- bot_type=approval-alert：审批流转通知
- bot_type=meeting-alert：会议通知
- bot_type=anon-msg：匿名消息中转
- bot_type=okr-alert：OKR 推送
- bot_type=user-auto-xxxxxx：用户自定义机器人（6-20 字符标识）

## 关键属性

- 每个 bot_type 在每个用户处单独建一个单聊会话
- 推送方式：调用 sendbot 接口或对应业务事件触发
- 消息为 markdown 文本，最长 2000 字符
- 机器人消息可被自己撤回（不受 msg_rev_limit 限制）

## 与普通消息的区别

| 维度 | 真人消息 | 机器人消息 |
|---|---|---|
| 发送者 | 真实 userid | bot=1 的虚拟账号 |
| 撤回时限 | 受 msg_rev_limit 控制 | 无限制 |
| @ 提醒 | 计入 mention 统计 | 不计入 |

## 不支持

- 不支持给机器人单聊发消息（机器人不会回应）
- 不支持把机器人加入群组
- 不支持自定义机器人头像 / 替换内置机器人头像
