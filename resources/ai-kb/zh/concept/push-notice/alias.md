---
id: push-notice.alias.concept
title: APP 推送设备绑定
type: concept
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 友盟别名
  - 设备绑定
  - 推送目标
  - DooPush 设备上报
  - 升级后重复推送
related_tools: []
related_pages: []
prerequisites:
  - 用户在 iOS / Android App 登录
negative:
  - Web 端和桌面端不注册 APP 推送设备
  - 超过一个月未更新的设备记录不再参与推送
  - remove 不能删除其他登录 token 绑定的 DooPush 设备
last_verified: v1.9.66
---

# APP 推送设备绑定

## 定义
DooTask 按用户绑定移动设备：新版 App 上报到 `app_push_devices` 的 DooPush 通道；旧版 App 继续使用 `umeng_alias` 的友盟别名。Web 端和桌面端不注册这两类设备。

## 新版 App 上报
`api/push/device` 同时支持 GET 和 POST，返回标准的 ret、msg、data。
1. action 默认 update；需要有效 token，平台必须是 ios / android。
2. device_id 必须是 1 至 64 位数字字符串；vendor 为 apns、fcm、hms、honor、xiaomi、oppo、vivo、meizu 之一。
3. 同 channel + device_id 唯一；设备换账号登录时，归属更新为当前用户。
4. 保存设备型号、版本 name (code)、UA、通知权限和登录 token 的 MD5。is_notified 为 true、字符串 true、1、字符串 1 时记为已授权。
5. 清理相同登录 token 哈希的旧友盟记录；unique_id 至少 5 个字符时，还会清理以该标识为后缀的旧别名，避免升级后重复推送。

## 旧版 App 上报
调用 `api/users/umeng/alias` 更新 alias，格式为 platform-userid-uniqueId；调试模式不注册友盟别名。

## 活跃设备与解绑
- 推送只选择一个月内更新的记录；DooPush 按用户取最新 5 台设备，每个用户一次请求。
- 角标取该用户未读且非静默的会话消息数。
- 新版退出登录调用 action=remove，传 device_id 和原请求头 token；token 可以已失效，但只能移除与其 MD5 匹配的 DooPush 记录。
- 服务端忘记登录设备时，也同步清理相同 token 哈希的两类推送绑定。

## 不支持
超过一个月未更新的记录不会自动物理删除；remove 不能删除其他登录 token 绑定的 DooPush 设备。
