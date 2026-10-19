---
id: push-notice.alias.concept
title: APP 推送设备绑定
type: concept
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 设备绑定
  - 推送目标
  - DooPush 设备上报
  - 换服务器后还收到推送
  - 退出登录还收到推送
related_tools: []
related_pages: []
prerequisites:
  - 用户在 iOS / Android App 登录
negative:
  - Web 端和桌面端不注册 APP 推送设备
  - 超过一个月未更新的设备记录不再参与推送
  - remove 不能删除其他登录 token 绑定的推送设备
  - 推送模式为「关闭」时不记录设备
last_verified: v1.9.66
---

# APP 推送设备绑定

## 定义
DooTask 按用户绑定移动设备，记录在 `app_push_devices`。系统配置与自定义模式的设备分开记录，切换模式后只向当前模式登记的设备推送。Web 端和桌面端不注册推送设备。

## App 上报
`api/push/device` 同时支持 GET 和 POST，返回标准的 ret、msg、data。
1. action 默认 update；需要有效 token，平台必须是 ios / android；推送模式为「关闭」时直接返回成功，不记录设备。
2. device_id 必须是 1 至 64 位数字字符串；vendor 为 apns、fcm、hms、honor、xiaomi、oppo、vivo、meizu 之一。
3. 系统配置模式还需上报 push_token（设备推送 Token）：服务器首次使用时自动在官方推送服务注册为租户，再按 Token 绑定设备，以推送服务返回的设备 ID 为准。
4. 同模式 + 设备 ID 唯一；设备换账号登录时，归属更新为当前用户。
5. 保存设备型号、版本 name (code)、UA、通知权限和登录 token 的 MD5。is_notified 为 true、字符串 true、1、字符串 1 时记为已授权。

## 活跃设备与解绑
- 推送只选择一个月内更新的记录，按用户取最新 5 台设备，每个用户一次请求。
- 角标取该用户未读且非静默的会话消息数。
- 退出登录调用 action=remove，传 device_id 和原请求头 token；token 可以已失效，但只能移除与其 MD5 匹配的记录。系统配置模式同时解除设备与本站租户的绑定。
- 系统配置模式下，同一台手机登录到另一台 DooTask 服务器后，设备改绑到新服务器，原服务器不再向它推送并自动清理该记录。
- 服务端忘记登录设备时，也同步清理相同 token 哈希的推送绑定。

## 不支持
超过一个月未更新的记录不会自动物理删除；remove 不能删除其他登录 token 绑定的推送设备。
