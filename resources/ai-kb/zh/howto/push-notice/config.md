---
id: push-notice.config.howto
title: 配置 APP 推送（DooPush / 友盟）
type: howto
feature: push-notice
scope: admin
locale: zh
aliases:
  - 配置友盟
  - UMENG 配置
  - APP 推送配置
  - 怎么开推送
  - DooPush 怎么配置
  - DooPush 服务地址怎么填
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 已获得对应推送服务的应用凭证
negative:
  - 当前环境禁止修改系统设置时无法保存推送配置
  - App Secret 是服务端发送凭证，不是客户端 App Key
  - 页面没有测试推送或事件订阅按钮
last_verified: v1.9.66
---

# 配置 APP 推送（DooPush / 友盟）

## 入口
桌面端：左上角头像 →「系统设置」→ 左侧「APP 推送」。也可通过管理员应用「APP 推送」打开配置。

## 操作步骤
1. 新版 App：在「DooPush 推送」区开启推送，填写 App ID、App Secret；服务地址留空时使用 `https://doopush.com/api/v1`，自部署 DooPush 可填写自己的 API 地址。
2. 旧版 App：在「友盟推送」区开启推送，按需填写 iOS / Android 各自的 Appkey 和 App Master Secret。
3. 两个通道可以同时开启；底部点击「提交」保存，点击「重置」恢复本次修改前的数据。
4. App 登录后自动注册对应通道的设备，无需管理员手工绑定用户。旧版升级到新版上报时会清理同设备的旧友盟别名。

## 字段说明
| 字段 | 默认 | 说明 |
|---|---|---|
| push | close | 友盟通道开关 |
| ios_key / ios_secret | 空 | 友盟 iOS 凭证 |
| android_key / android_secret | 空 | 友盟 Android 凭证 |
| doopush | close | DooPush 通道开关 |
| doopush_app_id | 空 | DooPush 应用 ID |
| doopush_app_secret | 空 | 服务端 App Secret，密码输入框显示，至少需要 push:send 权限 |
| doopush_base_url | 空 | DooPush API 服务地址，默认 https://doopush.com/api/v1 |

## 不支持
- 关闭某个通道只停止该通道发送，另一个已开启通道仍可工作。
- App Secret 是服务端凭证，不应填成客户端 App Key，也不要放进客户端或版本库。
- 页面没有测试推送或事件订阅按钮；排查见 [[push-notice.troubleshoot.faq]]。
