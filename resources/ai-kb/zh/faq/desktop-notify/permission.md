---
id: desktop-notify.permission.faq
title: 桌面通知没权限或没弹窗
type: faq
feature: desktop-notify
scope: end-user
locale: zh
aliases:
  - 桌面通知不弹
  - 没有桌面通知
  - 通知没权限
  - macOS 不弹
  - Windows 不弹
  - DooTask 通知不显示
  - 通知权限授权
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 不会主动重新请求系统通知权限；用户拒绝过一次后只能去系统设置改
  - 系统专注模式 / 勿扰开启时即使 DooTask 有权限也不会弹通知
  - 通知未弹不一定是权限问题，也可能是会话被免打扰或消息被静默发送
last_verified: v1.7.90
---

# 桌面通知没权限或没弹窗

## 问题
新消息已到达 DooTask 桌面端 / Web 版，但系统通知栏完全没弹出提示。

## 原因
桌面通知依赖操作系统的通知中心，需要授予 DooTask 通知权限，且系统当前未处于勿扰状态。

## 解决（macOS）
1. 打开「系统设置 → 通知」
2. 在应用列表找「DooTask」
3. 打开「允许通知」
4. 通知风格选「横幅」或「提醒」
5. 检查右上角是否处于「专注模式」「勿扰」；如果是，先关掉
6. 在 DooTask 内随便发送一条消息测试

## 解决（Windows 10/11）
1. 「设置 → 系统 → 通知」
2. 顶部「通知」总开关需为「开」
3. 下面应用列表找到 DooTask，开关打开
4. 关闭「专注助手」（设置 → 系统 → 专注助手 → 关）
5. 重启 DooTask 客户端测试

## 解决（Linux）
1. GNOME：「设置 → 通知 → DooTask」→ 开启「通知横幅」
2. KDE：「系统设置 → 通知 → 应用程序通知」→ DooTask
3. 部分发行版需安装 libnotify

## 解决（Web 版 / 浏览器）
1. 地址栏左侧锁图标 → 站点设置
2. 「通知」改为「允许」
3. 刷新页面让 DooTask 重新申请权限

## 仍然不弹？
按下面顺序排查：
- 会话是否开了免打扰？详见 [[push-notice.silent.howto]]
- 消息是否被静默发送（silence=true）？正常聊天不会触发，常见于机器人/系统消息
- 关闭 DooTask 进程后用其他应用测试系统通知是否正常工作（排除系统通知中心被禁）
- 桌面端可在「关于」面板看版本号，旧版本可能有 bug，请升级

## 与 Dock / 托盘的区别
通知不弹但 Dock 角标 / 任务栏闪烁正常，说明消息已到达：详见 [[desktop-notify.tray.concept]]。
