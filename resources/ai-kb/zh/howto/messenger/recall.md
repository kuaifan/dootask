---
id: messenger.recall.howto
title: 撤回消息
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么撤回消息
  - 撤回消息
  - 撤回一条
  - 收回消息
  - 误发了能撤回吗
  - 多久内可以撤回
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites:
  - 仅能撤回自己发送的消息
negative:
  - 撤回时限由系统管理员通过 msg_rev_limit（分钟数）配置，超出报「已超过 X 分钟，此消息不可撤回」
  - 默认 msg_rev_limit 为空表示不限制；若设置成 0 也表示不限制
  - 机器人发的消息可以无限期撤回（msg_rev_limit 校验对机器人放行）
  - 个人自聊会话（isSelfDialog）可以无限期撤回
last_verified: v1.7.90
---

# 撤回消息

撤回消息（withdraw）会软删除自己发送的消息（deletes 字段），群里所有人看到「XXX 撤回了一条消息」占位。撤回时限受系统配置 `msg_rev_limit` 控制（分钟）。

## 入口

- 桌面端：鼠标悬停自己发出的消息 → 右上角操作菜单 → 「撤回」
- 桌面端：长按 / 右键消息气泡 → 「撤回」
- 移动端：长按自己的消息气泡 → 「撤回」

## 操作步骤

1. 在会话流定位自己发的消息
2. 触发撤回操作
3. 二次确认后服务端校验：
   - 是否为消息发送者（必须）
   - 是否在 msg_rev_limit 时限内（如设置）
4. 通过校验则删除消息并推送给群成员

## 时限规则

| 场景 | 时限 |
|---|---|
| msg_rev_limit 为空 / 0 | 无限制 |
| msg_rev_limit = N 分钟 | 创建后 N 分钟内可撤 |
| 机器人发的消息 | 无限制 |
| 自聊（私人云笔记会话） | 无限制 |

## 与「编辑」的区别

- 撤回：消息消失，留占位
- 编辑：消息保留，内容更新，标记「已编辑」；时限由 msg_edit_limit 控制
- 编辑同样仅对自己消息有效

## 不支持

- 不支持撤回别人的消息（即使是群主、管理员也不行）
- 不支持撤回后再恢复（撤回不可逆）
- 不支持「仅对自己撤回」（撤回对群里所有人生效）
