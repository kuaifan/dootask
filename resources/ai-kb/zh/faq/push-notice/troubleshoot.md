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
  - 友盟没声音
  - 后台推送不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 没有重发推送按钮
  - 退出登录后设备解绑，不再接收该账号推送
  - 已读、静默和自己发给自己的消息不推送
last_verified: v1.9.66
---

# APP 收不到推送怎么办

## 问题
DooTask 移动 App 在后台或锁屏时没有收到新消息通知。新版使用 DooPush，旧版使用友盟，需要先确认当前 App 对应的通道。

## 排查顺序
1. 管理员进入「系统设置 → APP 推送」：新版检查「DooPush 推送」已开启，App ID、App Secret 和服务地址正确；旧版检查友盟开关及 iOS / Android 凭证。两通道可同时开启。
2. 在手机系统设置中允许 DooTask 通知，检查会话是否免打扰。Android 可检查自启动、后台运行和电池限制。
3. PC 端 60 秒内有活动会延迟手机推送 10 秒；期间读过消息则跳过，这是正常行为。
4. 设备超过一个月未更新则不参与推送，重新登录 App 刷新绑定。退出登录会解绑设备，需要先登录才能接收账号消息。
5. 新版升级上报会清理同设备旧友盟别名；新旧账号切换后，设备归属应为当前账号。

## 管理员排查
- DooPush：检查服务器应用日志的 `[DooPush]` 记录，核对 App Secret 是否属于当前 App ID 且有 push:send 权限；每次发送失败最多重试 2 次。
- DooPush 接口受理成功后，实际投递在 DooPush 后台异步执行，应在 DooPush 控制台继续检查厂商投递结果。
- 友盟：检查 `umeng_logs` 的 request / response 及厂商凭证。

## 不支持
DooTask 没有重发推送按钮；已读、静默和自己发给自己的消息不推送。退出登录后设备解绑，不再接收该账号推送。
