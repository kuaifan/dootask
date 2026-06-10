---
id: common-faq.notify-desktop-fail.faq
title: 桌面通知不弹
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 桌面不弹
  - Mac 不弹通知
  - Windows 不响
  - 浏览器没通知
  - 没弹横幅
  - Web 通知没权限
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 不会反复请求系统通知权限；拒绝过一次后只能去系统设置改
  - 浏览器在「专注模式」「勿扰」「全屏播放」时会自动抑制通知
  - 标签页被浏览器丢进后台冻结后通知也会暂停
last_verified: v1.7.90
---

# 桌面通知不弹

## 问题
有新消息时桌面端 / Web 端的应用内红点正常更新，但操作系统通知栏 / 通知中心一片空白，听不到声音也没横幅。

## 原因
桌面通知依赖三层：

- **操作系统通知权限**：macOS / Windows / Linux 各自的通知中心需把 DooTask 设为允许
- **浏览器层权限**（Web 版）：站点通知需「允许」
- **系统未处于勿扰 / 专注模式**：开启时无论谁都不弹
- **DooTask 个人通知设置**：「桌面通知」需开启，且没设「仅 @ 时」误关

## 解决（macOS）
1. 系统设置 → 通知 → DooTask → 允许通知 + 风格选「横幅 / 提醒」
2. 关掉右上角「专注 / 勿扰」
3. DooTask 内：个人头像 → 个人设置 → 通知 → 桌面通知打开

## 解决（Windows）
1. 设置 → 系统 → 通知 → 总开关「开」
2. 应用列表里 DooTask → 通知开
3. 关掉「专注助手」

## 解决（Linux）
- GNOME：设置 → 通知 → DooTask → 通知横幅开
- KDE：系统设置 → 通知 → 应用程序通知 → DooTask
- 部分发行版需 `libnotify`

## 解决（Web 浏览器）
1. 地址栏锁图标 → 站点设置 → 通知 → 允许
2. 刷新页面让 DooTask 重新申请权限
3. 浏览器没静音 / 没全屏

## 不支持
- 关浏览器或锁屏后浏览器进程被挂起 → 通知暂停（不算 DooTask 故障）
- Electron 客户端最小化到托盘时若系统勿扰，仍不弹
- 多桌面 / 多屏环境下通知只在主屏弹

详细排查参考 [[desktop-notify.permission.faq]] 各 OS 步骤。
