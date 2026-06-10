---
id: desktop-notify.concept
title: 桌面通知是什么
type: concept
feature: desktop-notify
scope: end-user
locale: zh
aliases:
  - 桌面通知
  - 桌面端通知
  - 系统通知栏
  - 弹窗通知
  - 电脑右下角通知
  - 任务栏闪烁
related_tools: []
related_pages: []
prerequisites:
  - 使用 DooTask 桌面端（Electron 客户端）或允许了浏览器通知的 Web 版
negative:
  - Web 版需用户在浏览器对话框中点「允许」才能弹出通知，否则只能在 APP 内提示
  - 桌面通知不走友盟，与 APP 推送是完全独立的通道
  - 关闭 DooTask 进程后不会有通知（与移动端不同，没有后台守护服务）
last_verified: v1.7.90
---

# 桌面通知是什么

## 定义
桌面通知是 DooTask 桌面端（Electron 客户端）和 Web 版在新消息到达时，通过操作系统原生通知 API 弹出的提示框。桌面端调用 `new Notification()`（Node 端），Web 版用浏览器 Notification API。

## 关键属性
- **触发**：新消息到达时由前端 `pages/manage.vue` 调用 `openNotification` 走 IPC 给主进程
- **内容**：标题（单聊=昵称 / 群聊=群名）、正文（消息预览）、图标（发送者头像）
- **快捷回复**：桌面端通知支持 hasReply=true 直接在通知框输入回复，回填到 DooTask
- **点击行为**：点通知会把主窗口拉前并打开对应会话
- **Dock 角标 / 任务栏**：macOS 显示 Dock badge 数字，Windows 任务栏闪烁，托盘可显示未读数

## 平台差异
| 系统 | 通知风格 | Dock/Tray |
|---|---|---|
| macOS | 通知中心 | Dock badge + 托盘 Title 文字 |
| Windows | 操作中心 | 任务栏闪烁（窗口失焦时） |
| Linux | libnotify | 仅通知 |

## 与其他通知的关系
- [[push-notice.concept]] 友盟推送只走移动 APP，桌面端不参与
- [[email-notice.concept]] 邮件只用于汇总未读或系统验证，与桌面通知并行
- 浏览器 Web 版桌面通知由浏览器实现，关闭浏览器即失效

## 不支持
- 不支持自定义通知音效（用系统默认）
- 不支持自定义通知时长（受系统通知中心控制）
- 不能按会话单独配置桌面通知开关（要总开关或会话级免打扰 [[push-notice.silent.howto]]）
