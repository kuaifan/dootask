---
id: push-notice.silent.howto
title: 单个会话免打扰
type: howto
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 会话免打扰
  - 群消息免打扰
  - 关闭某个群推送
  - 静音群聊
  - 不推送某个群
  - mute
  - silence
related_tools: []
related_pages: [messenger]
prerequisites:
  - 已加入想免打扰的会话
negative:
  - 免打扰只对该单个会话生效，不影响其他会话
  - 免打扰只屏蔽 APP 推送和未读邮件，不屏蔽 WebSocket 站内消息（消息列表仍能看到）
  - 没有「按时段免打扰单个会话」，只有总开关
last_verified: v1.7.90
---

# 单个会话免打扰

## 入口
对任意会话（单聊 / 群聊）开启免打扰：

- 桌面端：消息列表 → 右键目标会话 → 「免打扰」开关
- 桌面端：进入会话 → 顶部会话名 → 设置面板 → 「免打扰」开关
- 移动端：进入会话 → 顶部「···」→ 「消息免打扰」

## 工作原理
- 后端记录 `web_socket_dialog_users.silence` = 1
- 该用户对该会话的所有新消息：
  - 不触发友盟 APP 推送（`silence=1` 会被 [[push-notice.scenarios.concept]] 过滤）
  - 不汇总进未读邮件（EmailNoticeTask 跳过 silence=1 的记录）
  - 角标计数也不算它（badge 未读统计排除 silence）
- 但消息**仍会**到达 WebSocket，仍出现在消息列表和会话内

## 不影响的功能
- @提及：被@到仍会通知（独立通道）
- 任务相关：任务详情页通知不受会话级免打扰影响
- 消息搜索：仍能搜到这些消息

## 全局静音
DooTask 没有「全局所有会话静音」开关。要短期不被打扰：
- 移动端：关闭手机系统的 DooTask 通知权限
- 桌面端：见 [[desktop-notify.toggle.howto]]

## 不支持
- 不支持按时间段（如 22:00-08:00）的单会话免打扰，需用 [[mobile-notify.silent.howto]] 系统级时段
- 部分会话类型免打扰（如「只静音群聊不静音单聊」）
