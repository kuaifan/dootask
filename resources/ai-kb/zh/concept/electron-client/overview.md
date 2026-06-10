---
id: electron-client.concept
title: 桌面端是什么
type: concept
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 桌面端
  - 桌面版
  - 桌面客户端
  - 客户端
  - 装到电脑上
  - Electron 端
  - Mac 版
  - Windows 版
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 桌面端不能改服务器地址,首次启动时录入,后续切换需在登录页注销重设
  - 桌面端没有「独立内核更新」,功能更新依赖服务器后端 + 客户端版本同步
  - 桌面端无法离线使用,所有数据走与网页端相同的 HTTP / WebSocket
last_verified: v1.7.90
---

# 桌面端是什么

## 定义
桌面端是 DooTask 的本地客户端,基于 Electron 38 打包,套壳一份 Chromium 内核 + 复用网页端代码,再补足系统集成能力(托盘、原生通知、全局快捷键、截图、下载管理、本地 MCP)。当前应用版本独立于服务器主程序版本,自带更新通道。

## 关键属性
- **跨平台**:macOS / Windows / Linux 三大桌面系统都有官方包
- **数据互通**:登录任意服务器后,数据与网页端、移动端完全一致
- **系统集成**:原生通知、托盘、全局快捷键、屏幕截图、下载管理器
- **多窗口 / 多 Tab**:支持把任务、文件、应用拖出独立窗口
- **本地 MCP**:内置 fastmcp 服务,供 AI 助手调本机能力

## 与网页端的差异
- 桌面端默认拦截外链,在内置浏览器打开;网页端走系统浏览器
- 桌面端有「关闭即托盘 / 退出」选项,网页端关掉就是关掉
- 桌面端能注册 `Cmd/Ctrl + I`(AI 助手)等系统级快捷键
- 桌面端有内置截图工具([[electron-client.shortcut.concept]])

## 何时选桌面端
- 长时间办公(挂在后台 / 收推送)
- 需要原生通知不丢消息
- 频繁用 AI 助手 / 快捷键
- 经常处理文件(下载管理更直观)

## 不支持
- 不支持移动端的触屏手势
- 桌面端窗口最小化默认不退出后端连接(`window-all-closed` 在 macOS 不退出)
