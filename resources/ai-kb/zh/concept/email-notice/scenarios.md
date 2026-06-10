---
id: email-notice.scenarios.concept
title: 邮件通知场景
type: concept
feature: email-notice
scope: admin
locale: zh
aliases:
  - 什么时候发邮件
  - 邮件场景
  - 系统会发哪些邮件
  - 邮件触发
  - 邮件类型
related_tools: []
related_pages: []
prerequisites:
  - 管理员已配置 SMTP，见 [[email-notice.config.howto]]
negative:
  - 任务分配、审批通知、@提及不会单独发邮件，只通过站内消息和 APP 推送
  - 未读消息邮件只汇总指定时间窗口内的未读，不是每条消息都发
  - 邮件发送失败不会重试，也不会通知发起方
last_verified: v1.7.90
---

# 邮件通知场景

## 定义
DooTask 仅在少数系统级事件中通过邮件触达用户。所有事件都走管理员配置的 SMTP 通道。

## 全部触发场景
| 场景 | 收件人 | 触发条件 |
|---|---|---|
| 注册邮箱验证 | 新注册用户 | `reg_verify` 开启时，注册即发验证链接 |
| 修改邮箱验证码 | 用户原邮箱 | 用户提交「修改邮箱」时发 6 位验证码（30 分钟有效）|
| 注销账号验证码 | 用户当前邮箱 | 用户提交「删除账号」时发验证码 |
| 未读消息汇总 | 启用通知的用户 | `notice_msg` 开启且在 `msg_unread_time_ranges` 时间范围内，未读消息超过指定分钟数时按用户汇总发送 |
| 测试邮件 | 管理员指定地址 | 管理员点「邮件发送测试」时发一封测试 |

## 未读消息邮件的精细规则
- 单聊未读 ≥ `msg_unread_user_minute` 分钟才汇入下次邮件
- 群聊未读 ≥ `msg_unread_group_minute` 分钟才汇入下次邮件
- 任一值 = -1 → 该类型完全不发
- 仅 `text / file / record / meeting` 这 4 类消息会被汇总
- 标记为静默（silence）的消息不进邮件
- 忽略地址列表里的用户永远不收

## 不在邮件范围内
- 任务创建 / 状态变更 / 截止提醒
- 审批通过 / 驳回 / 抄送
- 项目邀请
- @提及 / 引用 / 评论

以上靠 [[push-notice.concept]] 和站内消息（WebSocket）通知。
