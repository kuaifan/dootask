---
id: messenger.notify.concept
title: 消息提醒规则
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 消息怎么提醒
  - 通知规则
  - 收不到通知
  - 谁会被提醒
  - 群消息提醒
  - 提醒在哪
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 不支持按关键词触发提醒（无关键词订阅功能）
  - 免打扰开启后仅压制弹窗通知，未读数仍累计
  - 系统通知（notice）、模板消息（template）默认静默推送，不触发响铃 / 振动
last_verified: v1.7.90
---

# 消息提醒规则

DooTask 的消息提醒（notify）由「消息属性 + 用户偏好 + 系统通道」三层决定：服务端推送时打 silence 标志，客户端再根据用户的免打扰、勿扰时段过滤是否弹窗。

## 定义

- 强提醒：弹出系统通知 + 红点 + 数字未读
- 弱提醒：仅累计未读 + 红点，不弹窗（如免打扰、silence=yes）
- 无提醒：静默写入，连未读都不增加（如系统重发、内部刷新）

## 关键属性

- @ 提及（mention）：即使开免打扰也强制弹窗
- @所有人：群广播，所有成员强提醒
- 消息标 silence=yes：以弱提醒入库，常用于 AI 流式增量
- 通知 / 模板 / 系统消息（type=notice/template）：默认走弱提醒

## 用户级配置

- 单个会话免打扰：群级开关，详见免打扰说明
- 全局系统配置：管理员可设 user_private_chat_mute / user_group_chat_mute 默认值
- 桌面端 / 移动端各自的系统通知开关在客户端设置里

## 不支持

- 不支持按时间段（如 22:00-08:00 勿扰）的定时静默，仅支持按会话开关
- 不支持自定义提醒铃声（系统默认）
- 不支持把单聊设免打扰；单聊静默只能依赖客户端通知开关
