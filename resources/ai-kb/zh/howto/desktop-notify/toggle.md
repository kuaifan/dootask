---
id: desktop-notify.toggle.howto
title: 开启或关闭桌面通知
type: howto
feature: desktop-notify
scope: end-user
locale: zh
aliases:
  - 关闭桌面通知
  - 不要桌面通知
  - 桌面通知开关
  - 怎么关弹窗
  - 通知太多
  - 静音桌面通知
related_tools: []
related_pages: []
prerequisites:
  - 使用 DooTask 桌面端（Electron）或 Web 版
negative:
  - DooTask 内部没有「一键关闭全部桌面通知」开关；要靠系统级勿扰或会话级免打扰
  - Web 版关闭浏览器通知后无法在 APP 内重新开启，需到浏览器设置中改
  - 关闭后 Dock 角标和任务栏闪烁仍会更新（属未读状态展示，不是通知）
last_verified: v1.7.90
---

# 开启或关闭桌面通知

## 整体策略
DooTask 没有 APP 内的「桌面通知总开关」。要控制弹窗，从三个层级选合适的方式：

## 方式 1：系统级勿扰模式（推荐临时关闭）
- **macOS**：点击右上角时钟 → 启用「专注模式」/「勿扰」
- **Windows 10/11**：操作中心 → 「专注助手」/「勿扰」
- 系统勿扰时所有应用通知都被抑制，包括 DooTask；解除后恢复

## 方式 2：操作系统应用级关闭
彻底不再收到 DooTask 桌面端通知：
- **macOS**：「系统设置 → 通知 → DooTask」→ 关闭「允许通知」
- **Windows**：「设置 → 系统 → 通知和操作」→ 找到 DooTask → 关闭
- **Linux**：依发行版而定，一般在 GNOME / KDE 通知设置
- 详见 [[desktop-notify.permission.faq]]

## 方式 3：DooTask 会话级免打扰（推荐针对个别群）
只对某个会话不弹通知：
- 桌面端：消息列表 → 右键目标会话 →「免打扰」
- 详见 [[push-notice.silent.howto]]
- 该方式同时影响移动端推送和未读邮件

## 重新开启
- 系统勿扰：在系统通知设置中关闭勿扰开关
- 系统应用通知：在系统设置中重新允许 DooTask 通知
- 会话免打扰：消息列表右键再次取消免打扰

## Web 版补充
浏览器需在 DooTask 首次询问通知权限时点「允许」。点过「禁止」后只能在浏览器地址栏左侧锁图标 → 站点设置 → 通知 改回「允许」。
