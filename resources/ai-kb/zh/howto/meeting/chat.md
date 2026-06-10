---
id: meeting.chat.howto
title: 会议中的文字消息
type: howto
feature: meeting
scope: end-user
locale: zh
aliases:
  - 会议聊天
  - 会议里发文字
  - 会议群消息
  - 会议讨论
  - 会议消息卡片
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 当前版本会议窗口内没有独立的文字聊天面板
  - 会议结束后再发的消息不会再关联到这场会议
last_verified: v1.7.90
---

# 会议中的文字消息

## 是什么
DooTask 会议使用 `MeetingMsg` 表把会议相关的消息卡片与具体的 `meetingid` 和对话 `dialog_id` 绑定。一场会议涉及以下三种典型消息卡片：

- 「会议邀请」卡片：发起 / 邀请时由 `meeting-alert` 系统机器人推送给被邀请人
- 「会议进行中」卡片：在被邀请人对话中保持「进行中」状态
- 「会议已结束」卡片：会议结束后由系统异步更新为已结束态

## 在哪里看到这些消息
- 与发起人或被邀请人的「私聊对话」里能看到上述卡片
- 群聊默认不会出现会议卡片（除非从该群发起会议）

## 想在会议中和参会人即时聊天
- 离开会议窗口，到对应对话里发普通消息（最常见做法）
- 桌面端可使用「最小化」按钮把会议浮窗收到一边，回到对话发消息

## 字段说明
| 字段 | 含义 |
|---|---|
| meetingid | 会议号 |
| dialog_id | 卡片所在对话 |
| msg_id | 对话消息 ID（关联到 WebSocketDialogMsg） |

## 不支持
- 会议窗口内部没有内嵌的群组聊天面板
- 不支持会议内点对点私聊
- 不支持向会议外的人广播会议中的消息
