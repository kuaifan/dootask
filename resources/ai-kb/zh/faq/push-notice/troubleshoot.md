---
id: push-notice.troubleshoot.faq
title: APP 收不到推送怎么办
type: faq
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 收不到推送
  - 手机没消息
  - DooPush 推送失败
  - 推送设备绑定失败
  - 后台推送不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 没有重发推送按钮
  - 退出登录后设备解绑，不再接收该账号推送
  - 已读、静默和自己发给自己的消息不推送
  - 旧版 App（友盟推送）不再接收推送
last_verified: v1.9.66
---

# APP 收不到推送怎么办

## 问题
DooTask 移动 App 在后台或锁屏时没有收到新消息通知。

## 排查顺序
1. 确认使用的是新版 App；旧版 App 依赖的友盟推送已移除，需升级。
2. 管理员进入「系统设置 → APP 推送」：推送服务不能是「关闭」。官方发布的 App 应选「系统配置」；「自定义」只适用于自行打包的 App，且 App ID、App Secret 和服务地址需正确。
3. 在手机系统设置中允许 DooTask 通知，检查会话是否免打扰。Android 可检查自启动、后台运行和电池限制。
4. PC 端 60 秒内有活动会延迟手机推送 10 秒；期间读过消息则跳过，这是正常行为。
5. 设备超过一个月未更新、切换过推送模式，或同一手机登录过另一台 DooTask 服务器，都需要重新打开或登录 App 刷新绑定。

## 管理员排查
- 检查服务器应用日志的 `[DooPush]` 记录；每次发送失败最多重试 2 次。
- 系统配置模式：需要服务器能访问 `https://doopush.com`。登录时提示「推送服务注册失败」「推送设备绑定失败」多为网络问题，可稍后重试；提示「推送服务已被停用」需联系官方。
- 自定义模式：核对 App Secret 是否属于当前 App ID 且有 push:send 权限；接口受理成功后，应在 DooPush 控制台继续检查厂商投递结果。

## 不支持
DooTask 没有重发推送按钮；已读、静默和自己发给自己的消息不推送。退出登录后设备解绑，不再接收该账号推送。
