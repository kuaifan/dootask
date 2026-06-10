---
id: mobile-notify.toggle.howto
title: 移动端通知开关
type: howto
feature: mobile-notify
scope: end-user
locale: zh
aliases:
  - 关移动端通知
  - 不要手机通知
  - 关闭 APP 通知
  - 手机静音
  - 关掉提醒
  - 开通知
related_tools: []
related_pages: []
prerequisites:
  - 已下载并登录 DooTask 移动端 APP
negative:
  - DooTask APP 内没有「全局通知总开关」按钮，要靠系统通知权限或会话级免打扰
  - 卸载重装会重新申请通知权限；以前的免打扰会话状态仍保留在云端
  - 关闭通知权限不会取消 UmengAlias 注册，重新打开权限即刻恢复
last_verified: v1.7.90
---

# 移动端通知开关

## 整体策略
DooTask 不在 APP 内单独提供「通知开关」。要控制移动端是否提醒，按需要的范围选下面三种方式之一。

## 方式 1：系统级关闭整个 APP 通知（强）
彻底不收 DooTask 通知：
- **iOS**：「设置 → DooTask → 通知」→ 关闭「允许通知」
- **Android（原生）**：「设置 → 应用 → DooTask → 通知」→ 关闭
- **Android（国产 ROM）**：通常在「设置 → 应用管理 → DooTask → 通知管理」
- 关闭后系统通知栏推送和前台浮层都不显示

## 方式 2：按时段免打扰（推荐用户日常）
DooTask 支持「指定时段不再振动 / 不再推送」：
- 详见 [[mobile-notify.silent.howto]]
- 适合工作休息时段（如 22:00-08:00）静默

## 方式 3：单个会话免打扰（推荐针对吵闹群）
仅对某个聊天关闭推送：
- 进入会话 → 顶部「···」→「消息免打扰」
- 详见 [[push-notice.silent.howto]]
- 该方式同时影响桌面端和未读邮件

## 重新打开
- 系统通知关闭后：回到系统设置中重新允许
- 时段免打扰：在 DooTask 内修改时间段或关闭功能
- 会话免打扰：进入会话 → 关闭「消息免打扰」

## 重启 APP 不需要
所有开关都即时生效，无需手动重启 APP；如果改完仍未生效，下拉刷新或退出登录重进。
