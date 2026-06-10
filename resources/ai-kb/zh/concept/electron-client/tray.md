---
id: electron-client.tray.concept
title: 桌面端系统托盘
type: concept
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 系统托盘
  - 最小化到托盘
  - 托盘图标
  - 后台运行
  - 关闭后还在
  - 任务栏图标
  - 状态栏图标
related_tools: []
related_pages: []
prerequisites: []
negative:
  - Linux 端没有托盘(仅 macOS / Windows 实现)
  - 托盘菜单只在 Windows 上提供「显示 / 退出」右键菜单;macOS 点击直接呼出窗口
  - 托盘红点 / 角标数量来自服务器未读计数,需在线才会更新
last_verified: v1.7.90
---

# 桌面端系统托盘

## 定义
系统托盘是 DooTask 桌面端在 macOS 菜单栏 / Windows 任务栏右下角驻留的小图标,用于让 App 在窗口关闭后仍在后台运行,持续接收消息、显示未读数。

## 平台支持
- **macOS**:在屏幕顶部菜单栏出现 DooTask 图标(模板图,跟随系统深浅色)
- **Windows**:在右下角任务栏托盘区出现 DooTask 图标
- **Linux**:不创建托盘

## 行为

### 点击托盘图标
- macOS / Windows 单击:呼出 / 隐藏主窗口

### Windows 右键托盘
- 「显示」:把主窗口拉到前台
- 「退出」:完全退出 App(不再后台保留)

### macOS 关闭主窗口
- 直接点窗口左上角红色 × 仅隐藏窗口,App 继续在 Dock 和菜单栏运行
- 真正退出要用菜单 → 「DooTask」→「退出 DooTask」(Cmd + Q)

### Windows 关闭主窗口
- 关闭按钮的具体行为可由用户在设置里选(隐藏到托盘 / 直接退出),取决于客户端版本
- 完全退出请用托盘右键「退出」或菜单「文件」→「退出」

## 托盘提示与角标
- 鼠标悬停托盘图标:显示「DooTask」名称
- macOS:有未读消息时托盘文字会显示数字(如「3」)
- Dock 角标(macOS)/ 任务栏角标(Windows):由系统决定显示方式

## 不支持
- Linux 桌面环境(GNOME / KDE)无托盘集成
- 托盘图标不可自定义颜色 / 形状
- 不支持自定义右键菜单项(目前仅「显示 / 退出」)
