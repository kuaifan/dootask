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
  - DooPush 推送
  - 后台推送消息
related_tools: []
related_pages: []
prerequisites:
  - 管理员已开启并配置与 App 版本匹配的推送通道
  - 用户在 iOS / Android 客户端登录并允许通知
negative:
  - Web 端、桌面端不走 APP 推送，使用 WebSocket 和本地通知
  - 两个通道都关闭或对应凭证缺失时无法发送离线推送
  - 没有按消息类型订阅推送的开关
last_verified: v1.9.66
---

# APP 推送是什么

## 定义
APP 推送是 DooTask 向 iOS / Android 移动端发送的系统通知。新版 App 使用 DooPush，旧版 App 使用友盟 UMENG；管理员可以同时开启两个通道，让不同版本的设备继续接收消息。

## 关键属性
- **通道配置**：友盟使用 iOS / Android 两组 Appkey、App Master Secret；DooPush 使用 App ID、App Secret 和服务地址，详见 [[push-notice.config.howto]]。
- **设备绑定**：新版 App 登录后上报 DooPush 设备 ID，旧版上报友盟 alias；升级上报会清理同设备的旧别名，详见 [[push-notice.alias.concept]]。
- **触发条件**：收到他人发送的非静默会话消息；任务提醒通过会话消息进入相同链路。
- **PC 在线优化**：PC 端 60 秒内活跃时，APP 推送延迟 10 秒，延迟期间已读则跳过。
- **角标**：取该用户未读且非静默的会话消息数。

## 与其他通知的关系
邮件通知 [[email-notice.concept]]、桌面通知 [[desktop-notify.concept]] 和 APP 前台浮层 [[mobile-notify.concept]] 各自有独立的提醒链路。

## 不支持
- Web 端和桌面端不注册 APP 推送设备。
- 没有按消息类型订阅推送的开关。
- 两个通道都关闭或对应凭证缺失时无法发送离线推送，站内消息仍正常。
