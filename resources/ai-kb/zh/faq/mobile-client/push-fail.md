---
id: mobile-client.push-fail.faq
title: 移动端推送收不到怎么办
type: faq
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 收不到推送
  - 收不到消息
  - 手机端没声音
  - 锁屏没通知
  - 后台收不到消息
  - 推送失败
  - 通知不响
  - 收不到通知
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 自部署的 DooTask 默认未必配置 UMENG,这种情况下移动端只能靠 WebSocket 在线时收消息
  - 厂商通道(华为 / 小米 / OPPO)的到达率受系统电池策略影响很大,无法 100% 保证
  - 推送收不到不影响数据,所有消息打开 App 后都会同步出现
last_verified: v1.7.90
---

# 移动端推送收不到怎么办

## 问题
DooTask 移动端 App 关闭 / 锁屏 / 后台时,新消息没有弹通知或没声音。

## 原因
移动端推送依赖一条链路:**DooTask 服务端 → UMENG(友盟)→ APNs(iOS)或厂商通道(Android)→ 你的设备**。任一环节失败都收不到。详细架构见 [[mobile-client.notify.concept]]。

## 排查清单(从易到难)

### 1. 服务端是否配置了 UMENG
最常见原因。自部署版本默认未填 UMENG 的 AppKey / Master Secret,导致**整个推送通道不可用**。表现:任何用户在后台都收不到推送。
- 联系部署管理员,确认服务端是否配置 UMENG 推送
- 未配置则推送整体不可用,只有 App 前台在线时通过 WebSocket 收消息

### 2. 系统通知权限
- iOS:设置 → 「通知」→「DooTask」→ 打开「允许通知」+「锁定屏幕」+「通知中心」+「横幅」
- Android:设置 → 「应用」→「DooTask」→「通知」→ 打开

### 3. App 内免打扰
- DooTask 「我的」→「设置」→「通知」→ 检查总开关是否关
- 单个会话的免打扰开关也要检查(头像右上角铃铛图标)

### 4. Android 后台被系统杀
Android 厂商对后台 App 限制非常严格:
- **华为**:「电池」→ DooTask 设为「不限制」+「应用启动管理」全开
- **小米**:「省电与电池」→ DooTask 设「无限制」+「自启动管理」开
- **OPPO / vivo / Realme**:类似设置,核心是「省电策略不限制」+「允许后台」+「自启动」
- 所有 Android:把 DooTask 锁到「最近任务」防止滑掉(各品牌实现不同)

### 5. iOS 后台问题
- iOS 设置 → 「通用」→「后台 App 刷新」→「DooTask」打开
- 「设置 → 通知 → DooTask → 即时推送」打开(部分版本叫法)

### 6. 网络问题
- 锁屏时手机省电关闭数据连接,推送通道不可达
- 设置 → WLAN → 高级 → 「在休眠状态下保持 WLAN 连接」

### 7. 设备别名注册失败
登录时 App 调 `/api/users/umeng/alias` 注册设备到友盟,若网络异常此次注册可能失败。解决:
- 重新登录 App(退出再登)
- 重启 App

## 检验是否解决
- 让同事发条消息,观察是否在锁屏弹出
- 看应用内通知历史是否到达

## 仍收不到
联系运维 / 部署管理员:
1. 服务端 UMENG 配置是否生效
2. 服务端日志中是否有该用户的推送投递记录
3. 设备友盟 token 是否正确绑定
