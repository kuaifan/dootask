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
  - DooPush 推送
  - 后台推送消息
  - 友盟推送还能用吗
related_tools: []
related_pages: []
prerequisites:
  - 管理员未关闭 APP 推送（默认使用系统配置）
  - 用户在 iOS / Android 客户端登录并允许通知
negative:
  - Web 端、桌面端不走 APP 推送，使用 WebSocket 和本地通知
  - 推送模式为「关闭」或自定义凭证缺失时无法发送离线推送
  - 没有按消息类型订阅推送的开关
  - 已不再支持友盟（UMENG）推送
last_verified: v1.9.66
---

# APP 推送是什么

## 定义
APP 推送是 DooTask 向 iOS / Android 移动端发送的系统通知，统一通过 DooPush 下发到 APNs 或 Android 厂商通道。旧版 App 使用的友盟推送已移除。

## 关键属性
- **推送模式**：系统配置（默认，使用官方推送服务，无需配置）、自定义（自己的 DooPush 应用与自行打包的 App）、关闭，详见 [[push-notice.config.howto]]。
- **设备绑定**：App 登录后上报推送设备，退出登录时解绑，详见 [[push-notice.alias.concept]]。
- **触发条件**：收到他人发送的非静默会话消息；任务提醒通过会话消息进入相同链路。
- **PC 在线优化**：PC 端 60 秒内活跃时，APP 推送延迟 10 秒，延迟期间已读则跳过。
- **角标**：取该用户未读且非静默的会话消息数。

## 与其他通知的关系
邮件通知 [[email-notice.concept]]、桌面通知 [[desktop-notify.concept]] 和 APP 前台浮层 [[mobile-notify.concept]] 各自有独立的提醒链路。

## 不支持
- Web 端和桌面端不注册 APP 推送设备。
- 没有按消息类型订阅推送的开关。
- 推送模式为「关闭」或自定义凭证缺失时无法发送离线推送，站内消息仍正常。
- 旧版 App（友盟推送）不再接收推送，需升级到新版。
