---
id: email-notice.concept
title: 邮件通知是什么
type: concept
feature: email-notice
scope: admin
locale: zh
aliases:
  - 邮件通知
  - 邮件提醒
  - SMTP 通知
  - 系统发邮件
  - 邮箱通知
  - 收不到邮件
related_tools: []
related_pages: []
prerequisites:
  - 管理员已在系统设置「邮箱设置」配好 SMTP 服务器
negative:
  - 未配置 SMTP 时所有邮件功能都不发邮件，但不会报错给用户
  - 邮件通道只用于系统通知（注册验证 / 改邮箱 / 未读消息 / 删除账号验证），不能用作客户营销邮件
  - 邮件发送依赖第三方 SMTP，DooTask 自身不内置邮件服务器
last_verified: v1.7.90
---

# 邮件通知是什么

## 定义
邮件通知是 DooTask 通过管理员配置的 SMTP 服务器，向用户邮箱发送系统通知的能力。系统级配置存放在 `emailSetting`，包含 SMTP 服务器、端口、账号、密码、忽略地址、未读消息提醒规则等字段。

## 关键属性
- **全局开关**：未配 SMTP 时邮件链路不工作（不报错，也不发出）
- **注册验证**：`reg_verify` = open 时新注册账号需邮箱验证才能登录，修改邮箱/删除账号也走验证码
- **未读消息提醒**：`notice_msg` = open 时按时间范围 `msg_unread_time_ranges` 把未读消息汇总成邮件发送
- **忽略地址**：`ignore_addr` 列表中的邮箱永远不收邮件（如内部测试号、机器人号）
- **发件人**：系统别名（System Alias）+ SMTP 账号，如 `Task <noreply@example.com>`

## 触发邮件的场景
具体场景见 [[email-notice.scenarios.concept]]。

## 与其他通知的关系
- **APP 推送**（友盟）：见 [[push-notice.concept]]，独立通道
- **桌面通知**（Electron）：本地系统通知，不走邮件
- **移动端通知**：iOS/Android 推送，不走邮件

邮件、APP 推送、桌面通知三个通道**并行触发**，互不影响。
