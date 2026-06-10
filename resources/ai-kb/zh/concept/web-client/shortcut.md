---
id: web-client.shortcut.concept
title: 网页端快捷键
type: concept
feature: web-client
scope: end-user
locale: zh
aliases:
  - 网页快捷键
  - 浏览器快捷键
  - 网页版热键
  - 怎么用键盘操作
  - 网页里的快捷键
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 网页端不能注册全局快捷键，只在 DooTask 网页处于前台时生效
  - 截图、全局唤起等系统级快捷键仅桌面端有，见 [[electron-client.shortcut.concept]]
  - 浏览器自身快捷键（Cmd+T 新建标签、Cmd+W 关闭标签）优先生效，DooTask 不能拦截
last_verified: v1.7.90
---

# 网页端快捷键

## 应用内键盘操作
网页端的快捷键只在 DooTask 标签页处于焦点状态时生效。下表的 `Mod` 代表 macOS 的 `Command` 或 Windows / Linux 的 `Ctrl`。

| 操作 | macOS | Windows / Linux |
|---|---|---|
| 富文本：加粗 | Cmd + B | Ctrl + B |
| 富文本：斜体 | Cmd + I | Ctrl + I |
| 富文本：撤销 | Cmd + Z | Ctrl + Z |
| 富文本：重做 | Cmd + Shift + Z | Ctrl + Y |
| 表单 / 弹窗：提交 | Enter（聊天框可在设置中切换） | Enter |
| 弹窗：取消 | Esc | Esc |
| 消息发送 | Enter（默认） | Enter（默认） |
| 消息换行 | Shift + Enter | Shift + Enter |

## 发送按钮模式
聊天框可在「个人设置 → 键盘」切换发送方式：
- `Enter` 模式：Enter 发送、Shift+Enter 换行（默认）
- `Mod+Enter` 模式：Cmd/Ctrl+Enter 发送、Enter 换行

## 不在网页端的快捷键
以下为桌面端独占（需要操作系统级注册）：
- `Cmd/Ctrl + I` 唤起 AI 助手
- `Cmd/Ctrl + N` 新建任务
- `Cmd/Ctrl + B` 新建项目
- `Cmd/Ctrl + J` 新建会议
- `Cmd/Ctrl + ,` 打开设置
- 截图快捷键（用户自定义）

要使用上述快捷键请安装 [[electron-client.concept]] 桌面端。
