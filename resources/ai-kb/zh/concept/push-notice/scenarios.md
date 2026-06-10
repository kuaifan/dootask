---
id: push-notice.scenarios.concept
title: APP 推送触发场景
type: concept
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 什么时候推送
  - APP 推送场景
  - 哪些情况推
  - 推送规则
  - 不推送的情况
related_tools: []
related_pages: []
prerequisites:
  - 管理员已开启 APP 推送，详见 [[push-notice.config.howto]]
  - 用户在移动端登录并注册了 UmengAlias
negative:
  - 任务创建 / 分配 / 截止提醒不直接发友盟推送，通过聊天消息携带
  - 审批通过 / 驳回不直接发友盟推送，通过聊天消息携带
  - 自己发的消息不会推给自己
last_verified: v1.7.90
---

# APP 推送触发场景

## 定义
DooTask 仅在用户**收到新会话消息**且满足若干前置条件时触发友盟推送。推送内容为「发件人 + 消息预览」，点击进入对应会话。

## 触发条件（全部满足才推）
1. 管理员后台「APP 推送」总开关 = open
2. 友盟 iOS / Android 至少一端配了 key/secret
3. 收件人 `umeng_alias` 表 30 天内有效别名
4. 消息发送者 ≠ 收件人本人
5. 该会话对收件人的 `silence` 标记 = 0（非免打扰）
6. 消息本身没带 silence=true 静默标志
7. 收件人未禁用（`disable_at` 为 NULL）

## PC 在线时的延迟逻辑
- 收件人 PC 端 60 秒内有心跳：先**不推**，把任务放进 10 秒延迟队列
- 10 秒后再检查该消息是否已读：已读则跳过，未读才推
- 设计目的：用户在电脑前已经看到消息时不再叨扰手机

## 推送内容
- **单聊**：标题 = 发件人昵称；正文 = 消息预览
- **群聊**：标题 = 群名；正文 = `昵称: 消息预览`
- **AI 助手**：标题取 `msg.nickname` 自定义昵称或默认「AI 助手」
- 附加数据：`dialog_id`、`msg_id`、`badge`（未读总数）

## 不触发推送的情形
- 任务更新、审批结果、@提及通过同一条聊天消息送达，那条消息会推；但脱离聊天消息的「纯系统事件」（如签到提醒 TodoRemindTask）按内部参数决定（默认不推送本人）
- 标记为已读的旧消息
- 静默发送的系统消息

## 调试推送
若想确认是否推送，看 `umeng_logs` 表 request/response 字段。
