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
  - UMENG 推送
  - 友盟推送
  - 收不到手机通知
  - 锁屏推送
  - 后台推送
related_tools: []
related_pages: []
prerequisites:
  - 已登录过 App 且设备别名已注册到友盟
negative:
  - 推送通道走 UMENG(友盟),需服务端配置 AppKey / Master Secret,自部署默认未开启
  - 国内 Android 推送依赖各厂商通道(华为 / 小米 / OPPO / vivo / 魅族),后台杀进程后到达率与设备绑定
  - 推送内容受隐私设置影响:锁屏隐藏详情时只显示「您有一条新消息」
last_verified: v1.7.90
---

# 移动端推送通知

## 定义
移动端推送通知是 DooTask 服务端通过 UMENG(友盟)推送平台向已登录设备投递的消息提醒,即使 App 处于后台 / 锁屏 / 杀进程也能到达(取决于厂商通道)。区别于桌面端原生通知([[electron-client.notify.concept]]),属于完全不同的通道。

## 推送通道架构
- 用户登录后 App 调 `/api/users/umeng/alias` 把设备 token 与用户 ID 绑定
- 服务端在 `WebSocketDialogMsgTask` 中向离线 / 后台用户额外发起 UMENG 推送
- UMENG 把推送下发到 APNs(iOS)或各厂商通道(Android),最终弹到设备

## 触发推送的事件
- 新私聊 / 群聊消息
- @ 你的消息
- 任务相关:被分配、被 @、状态变更、任务即将超时 / 已超时定时提醒(由 task-alert「任务提醒」机器人私聊发送,详见 [[task.notify.concept]])
- 项目重要变更
- 系统公告 / 管理员推送

## 收不到推送的原因(排序)
1. **服务端未配置 UMENG**:自部署版本默认未填 AppKey,推送整体不可用
2. **App 未授权通知**:手机系统设置 → 「通知」→「DooTask」必须打开「允许通知」
3. **后台被系统杀死**:Android 厂商电池优化会把 App 列入限制,导致离线推送无法激活
4. **应用内免打扰**:DooTask 个人设置或单会话开了免打扰
5. **网络问题**:UMENG 服务器与手机的连接断开

## 提高到达率(Android)
- 「电池」→ 把 DooTask 加入「不限制 / 后台运行白名单」
- 「自启动管理」→ 允许 DooTask 自启动
- 「电池优化」→ 把 DooTask 设为「不优化」
- 部分品牌(华为)需在「应用启动管理」里手动启用所有开关

## 推送内容显示
- 通常显示「发送人 + 消息预览」
- 锁屏隐藏详情或群聊不显示发送人,可在系统通知设置调整
- 详情显示策略以服务端推送结构为准,App 不能单独配置

## 不支持
- 不支持脱离 UMENG 的 P2P 推送(架构上依赖)
- 不支持「按场景拆开关」(只能整体开 / 关)
- 见到推送但点开 App 没新消息,通常是 WebSocket 已经在线时推送延迟
- 详细收不到处理见 [[mobile-client.push-fail.faq]]
