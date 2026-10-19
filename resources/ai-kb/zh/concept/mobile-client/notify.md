---
id: mobile-client.notify.concept
title: 移动端推送通知
type: concept
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 移动端推送
  - 手机推送
  - App 通知
  - DooPush 推送
  - DooPush 推送
  - 锁屏推送
related_tools: []
related_pages: []
prerequisites:
  - 已登录移动 App 并完成设备绑定
negative:
  - 服务端需配置与 App 版本匹配的推送通道
  - 到达率受厂商通道、网络和手机电池策略影响
  - 不支持脱离推送服务的点对点离线通知
last_verified: v1.9.66
---

# 移动端推送通知

## 定义
移动端推送通知是 DooTask 向已登录手机发送的系统提醒。App 通过 DooPush 下发到 APNs 或 Android 厂商通道。桌面端原生通知使用另一套链路。

## 推送链路
1. 登录后，App 调用 `api/push/device` 上报推送设备（系统配置模式同时上报推送 Token 用于绑定）。旧版 App 的友盟推送已移除。
2. 收到他人发送的非静默会话消息时，后端向用户的活跃设备发送通知。
3. 私聊、群聊、任务分配及任务超时等提醒通过对应会话消息进入推送，详见 [[task.notify.concept]]。
4. PC 最近 60 秒活跃会延迟手机通知 10 秒，延迟期间读过则跳过。

## 收不到推送
- 检查服务器推送模式（系统配置 / 自定义 / 关闭）：[[push-notice.config.howto]]。
- 检查手机通知权限、会话免打扰、Android 自启动和电池限制。
- 退出登录会解绑设备；超过一个月未更新的绑定不再用于推送。
- 重新登录可刷新设备绑定；排查步骤见 [[mobile-client.push-fail.faq]]。

## 不支持
不支持脱离推送服务的点对点离线通知；到达率受厂商通道、网络和手机电池策略影响，不能保证所有设备即时送达。
