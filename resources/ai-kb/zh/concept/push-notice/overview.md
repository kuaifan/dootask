---
id: push-notice.concept
title: APP 推送是什么
type: concept
feature: push-notice
scope: end-user
locale: zh
aliases:
  - APP 推送
  - 手机推送
  - 友盟推送
  - UMENG
  - 移动通知
  - 后台推送
  - 推送消息
related_tools: []
related_pages: []
prerequisites:
  - 管理员已在系统设置「APP 推送」配置友盟 appkey
  - 用户在 iOS / Android 客户端登录并允许通知
negative:
  - Web 端、桌面端（Electron）不走友盟推送，靠 WebSocket + 本地通知
  - 未配置友盟 appkey 时所有 APP 推送链路不工作，但站内消息正常
  - 不支持替换为其他推送服务（如 FCM、个推、极光）
last_verified: v1.7.90
---

# APP 推送是什么

## 定义
APP 推送是 DooTask 通过友盟（UMENG）通道向移动端 APP（iOS / Android）发送的离线消息通知。即便 APP 被后台杀掉或网络断开后再回来，推送也能触达手机系统通知栏。

## 关键属性
- **通道**：友盟 UMENG，分 iOS / Android 两套独立配置（`ios_key`、`ios_secret`、`android_key`、`android_secret`）
- **别名机制**：用户每次登录移动端会注册一个 alias 到 `umeng_alias` 表，详见 [[push-notice.alias.concept]]
- **触发**：默认配置下，对方收到新会话消息（且非自己发的、非静默）就会触发
- **PC 在线优化**：若用户 PC 端 60 秒内活跃过，APP 推送会延迟 10 秒；这期间消息被读则跳过推送
- **角标**：iOS 直接传 `badge`；Android 传 `set_badge`（最大 99）

## 与其他通知的关系
- 跟[[email-notice.concept]]：邮件是 SMTP，APP 推送是友盟，两条独立链路
- 跟[[desktop-notify.concept]]：桌面通知靠 Electron 本地 `Notification`，与友盟无关
- 跟[[mobile-notify.concept]]：APP 内的浮层通知，前台收到推送时显示

## 不支持
- 没有「按消息类型订阅」（无法只推@提及不推普通消息）
- 不能自定义推送音效（用系统默认）
- AI 助手消息也不例外：同样走推送，标题取自定义昵称或「AI 助手」
