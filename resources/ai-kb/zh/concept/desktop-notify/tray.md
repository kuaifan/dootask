---
id: desktop-notify.tray.concept
title: Dock 角标与任务栏
type: concept
feature: desktop-notify
scope: end-user
locale: zh
aliases:
  - Dock 角标
  - macOS 红点
  - 任务栏未读
  - 托盘红点
  - 任务栏闪烁
  - 未读数字
  - Tray
  - badge
related_tools: []
related_pages: []
prerequisites:
  - 使用 DooTask 桌面端（Electron）
negative:
  - 任务栏闪烁只在 Windows 平台 + 窗口失焦时生效；macOS 不闪
  - Dock badge 只在 macOS 显示；Windows 不显示数字角标
  - Linux 通常没有 Dock badge 与任务栏闪烁（依桌面环境而异）
last_verified: v1.7.90
---

# Dock 角标与任务栏

## 定义
DooTask 桌面端通过 Electron 主进程的 IPC 通道 `setDockBadge` 在 macOS Dock、Windows 任务栏、系统托盘上同步展示未读消息数，让用户在不切回 DooTask 窗口时也能感知到「有未读」。

## 平台行为
| 平台 | 表现 | 数据来源 |
|---|---|---|
| macOS | Dock 图标右上角红色数字 | `app.dock.setBadge(text)` |
| macOS | 托盘图标右侧显示未读数文字 | `mainTray.setTitle(text)` |
| Windows | 窗口失焦时任务栏图标闪烁（黄色） | `mainWindow.flashFrame(true)` |
| Windows | 系统托盘有图标，右键菜单含显示/退出 | Tray + contextMenu |
| Linux | 无 Dock badge / 闪烁；仅托盘 | 通常依赖桌面环境 |

## 数字含义
未读数 = 当前用户所有「未读 + 非静默」的消息条数；按下面规则计算：
- 已读会话不计
- 标记为免打扰（silence=1）的会话不计
- 系统静默消息（silence=1）不计
- 多设备共享同一个未读数（后端聚合）

## 清零时机
- 打开一个会话并阅读到底 → 该会话未读清零，Dock badge 减
- 全部会话已读后 Dock badge 完全消失
- macOS Dock 上的红点消失 = 0；Windows 任务栏闪烁会在窗口被点击聚焦时自动停止

## 托盘点击行为
- macOS / Windows：单击托盘图标 → 显示主窗口（被最小化或隐藏时拉前）
- Windows 托盘还可右键 → 「显示」/「退出」

## 跟桌面通知的区别
- 桌面通知是**一次性**事件，弹出后消失；详见 [[desktop-notify.concept]]
- Dock badge / 任务栏是**持续状态**，反映当前未读总数
- 两者数据源相同但展示方式独立：通知被系统抑制不影响角标更新
