---
id: push-notice.config.howto
title: 配置 APP 推送（DooPush）
type: howto
feature: push-notice
scope: admin
locale: zh
aliases:
  - APP 推送配置
  - 怎么开推送
  - 推送服务选哪个
  - 系统配置推送
  - DooPush 怎么配置
  - DooPush 服务地址怎么填
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 选择「自定义」时需已有自己的 DooPush 应用及自行打包的 App
negative:
  - 当前环境禁止修改系统设置时无法保存推送配置
  - App Secret 是服务端发送凭证，不是客户端 App Key
  - 页面没有测试推送或事件订阅按钮
  - 已不再支持友盟（UMENG）推送
last_verified: v1.9.66
---

# 配置 APP 推送（DooPush）

## 入口
桌面端：左上角头像 →「系统设置」→ 左侧「APP 推送」。也可通过管理员应用「APP 推送」打开配置。

## 操作步骤
1. 在「推送服务」选择模式：
   - **系统配置**（默认）：使用官方推送服务，无需填写任何凭证，适用于官方发布的 App。
   - **自定义**：使用自己的 DooPush 应用，需搭配自行打包的 App；填写 App ID、App Secret，服务地址留空时使用 `https://doopush.com/api/v1`，自部署 DooPush 可填写自己的 API 地址。
   - **关闭**：不发送 APP 推送，App 登录时也不记录推送设备。
2. 底部点击「提交」保存，点击「重置」恢复本次修改前的数据。
3. App 登录后自动注册推送设备，无需管理员手工绑定用户。切换模式后，用户需重新打开或登录 App 才会按新模式注册设备。

## 字段说明
| 字段 | 默认 | 说明 |
|---|---|---|
| doopush | system | 推送模式：system 系统配置 / custom 自定义 / close 关闭 |
| doopush_app_id | 空 | 自定义模式的 DooPush 应用 ID |
| doopush_app_secret | 空 | 自定义模式的服务端 App Secret，密码输入框显示，至少需要 push:send 权限 |
| doopush_base_url | 空 | 自定义模式的 DooPush API 服务地址，默认 https://doopush.com/api/v1 |

## 不支持
- 系统配置模式没有可填写的凭证，服务器首次有设备登录时自动接入官方推送服务。
- App Secret 是服务端凭证，不应填成客户端 App Key，也不要放进客户端或版本库。
- 已不再支持友盟（UMENG）推送，旧版 App 需升级到新版才能接收推送。
- 页面没有测试推送或事件订阅按钮；排查见 [[push-notice.troubleshoot.faq]]。
