---
id: mobile-client.push-fail.faq
title: 移动端推送收不到怎么办
type: faq
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 收不到手机消息
  - 手机端没声音
  - 锁屏没通知
  - 后台收不到消息
  - 推送失败
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 自部署服务需要管理员配置与 App 版本匹配的推送通道
  - 推送送达率受手机系统和网络影响，无法完全保证
  - 离线推送失败不影响登录后同步站内消息
last_verified: v1.9.66
---

# 移动端推送收不到怎么办

## 问题
DooTask App 在后台或锁屏时收不到新消息。新版 App 使用 DooPush，旧版使用友盟，需检查对应链路：[[mobile-client.notify.concept]]。

## 排查步骤
1. 联系管理员检查「系统设置 → APP 推送」。新版需开启 DooPush 并填好 App ID、App Secret、服务地址；旧版需开启友盟并填好 iOS / Android 凭证。
2. 手机系统设置 → DooTask → 通知，打开允许通知、锁屏和横幅等选项。
3. 检查聊天是否免打扰；PC 最近活跃时手机会延迟 10 秒通知，期间已读则跳过。
4. Android 检查允许自启动、后台运行和电池不受限，网络需能访问对应推送服务。
5. 重新登录 App 刷新设备绑定。新版上报到 `api/push/device`，旧版上报到 `api/users/umeng/alias`。退出登录后设备解绑，不再接收该账号推送。
6. 让其他人发送一条新消息验证锁屏通知，不要用自己发送的消息测试。

## 仍未解决
管理员检查 DooPush 的 `[DooPush]` 应用日志及 DooPush 控制台投递结果，或友盟 `umeng_logs` 返回信息，详见 [[push-notice.troubleshoot.faq]]。

## 不支持
推送送达率受手机系统和网络影响，无法完全保证。离线推送失败不影响登录后同步站内消息。
