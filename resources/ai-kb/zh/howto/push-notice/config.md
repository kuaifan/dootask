---
id: push-notice.config.howto
title: 配置友盟 APP 推送
type: howto
feature: push-notice
scope: admin
locale: zh
aliases:
  - 配置友盟
  - UMENG 配置
  - APP 推送配置
  - 配置 appkey
  - 怎么开推送
  - 友盟密钥
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 已在 https://www.umeng.com 注册友盟账号并创建对应的 iOS / Android 应用
  - 已拿到 App Key 和 App Master Secret
negative:
  - 当环境变量 SYSTEM_SETTING=disabled 时禁止从界面修改推送设置（密钥会脱敏显示）
  - 必须同时填 iOS 和 Android 的 key/secret 才能两端都收到；只填一端则只推一端
  - 默认 production_mode=true，意味必须用正式签名的 APP 包，开发版 APP 收不到
last_verified: v1.7.90
---

# 配置友盟 APP 推送

## 入口
桌面端：左侧栏「管理后台」→「系统设置」→「APP 推送」选项卡

## 操作步骤
1. 在「开启推送」选择「开启」
2. 「iOS 参数配置」区填写：
   - Appkey
   - App Master Secret
3. 「Android 参数配置」区填写：
   - Appkey
   - App Master Secret
4. 底部「提交」保存
5. 保存后即时生效，无需重启服务

## 字段说明
| 字段 | 默认 | 说明 |
|---|---|---|
| push | close | 总开关；close 时所有 APP 推送任务不入队 |
| ios_key | 空 | 友盟 iOS App Key |
| ios_secret | 空 | 友盟 iOS App Master Secret，前端 password 显示 |
| android_key | 空 | 友盟 Android App Key |
| android_secret | 空 | 友盟 Android App Master Secret |

## 推送配置生效后做什么
- 接 alias 注册：APP 启动登录后自动调 `api/users/umeng/alias` 注册别名（详见 [[push-notice.alias.concept]]）
- 推送触发：新消息到达 `WebSocketDialogMsgTask` 会调用 `PushUmengMsg` 入队推送，参考 [[push-notice.scenarios.concept]]
- 推送结果落 `umeng_logs` 表：可用于排查（详见 [[push-notice.troubleshoot.faq]]）

## 不支持
- 国内厂商通道（OPPO/华为/小米/VIVO）不开箱即用：需走友盟厂商通道下发，并在友盟控制台单独配置厂商证书
- 不能用替代推送服务，DooTask 推送层与友盟 SDK 强绑定
