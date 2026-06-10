---
id: web-client.concept
title: 网页端是什么
type: concept
feature: web-client
scope: end-user
locale: zh
aliases:
  - 网页端
  - 网页版
  - web 版
  - 浏览器打开
  - 在网页上用
  - 不装客户端怎么用
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 网页端不能弹原生桌面通知，需要浏览器自身的「通知权限」授权
  - 网页端不支持系统托盘、开机自启、全局快捷键（这些是桌面端能力）
  - 浏览器关闭标签页后会断开 WebSocket，需要重新打开
last_verified: v1.7.90
---

# 网页端是什么

## 定义
网页端是 DooTask 的浏览器版本，直接通过浏览器访问部署的服务地址（如 `https://你的域名/`）即可使用，无需安装任何客户端。它和桌面端、移动端共用同一套后端 API 和数据。

## 关键属性
- **无需安装**：打开浏览器访问域名即可登录
- **跨平台**：Windows / macOS / Linux / Chromebook 任意系统的现代浏览器都能用
- **实时同步**：通过 WebSocket 推送消息、任务变更
- **功能最全**：所有产品功能（消息、任务、项目、视图、应用、AI 等）网页端都有

## 与其他终端的关系
- **桌面端**：基于 Electron 套壳网页端，额外提供托盘、原生通知、全局快捷键，详见 [[electron-client.concept]]
- **移动端**：独立的 iOS / Android App（基于 EEUI），交互重做以适配触屏，详见 [[mobile-client.concept]]
- 三端数据完全互通，登录哪一端都看到一样的内容

## 何时选网页端
- 临时机器上用 / 不方便装客户端
- 内嵌到其他系统的 iframe 里
- 仅做轻量查看，不需要后台常驻

## 不支持
- 不支持原生桌面通知（必须用浏览器自带的通知 API，且需用户授权）
- 不支持系统托盘 / 最小化到托盘
- 不支持全局快捷键（Cmd+I、Cmd+N 等仅在桌面端生效）
- 截图（桌面端有内置截图工具，网页端没有）
