---
id: user-settings.shortcut.concept
title: 键盘 / 快捷键设置
type: concept
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 快捷键
  - 键盘设置
  - 改快捷键
  - 截图快捷键
  - 发送消息回车
  - Enter 发送
  - Cmd+Enter
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 「键盘设置」子页只在 Electron 桌面端与 EEUI 移动端显示，Web 端没有
  - 大部分快捷键固定不可改（仅截图键、发送行为可调）
  - Web 浏览器 Cmd+S / Cmd+P 等会被浏览器吞掉，DooTask 不接管
last_verified: v1.7.90
---

# 键盘 / 快捷键设置

「键盘设置」是个人设置里的子页，用来查看 / 调整桌面端与移动端的快捷键、消息发送按钮行为。

## 入口

- 桌面端 Electron：右上角头像 →「设置」→「键盘设置」
- 移动端 EEUI：「我的」→「设置」→「键盘设置」
- Web 浏览器：无此入口

## 可调项

| 项 | 说明 |
|---|---|
| 截图快捷键 | Electron 全局快捷键最后一位字母可改：`Cmd/Ctrl + Shift + <key>` |
| 桌面端发送按钮 | `Enter 发送` 或 `Cmd/Ctrl + Enter 发送`（影响消息输入框换行行为） |
| 移动端发送按钮 | `button` 开启时键盘上发送键变换行，按 APP 内浮动发送按钮发消息 |

## 固定快捷键（不可改）

桌面端 Electron 下：

| 操作 | 快捷键 |
|---|---|
| 下载内容 | Cmd/Ctrl + Alt/Option + L |
| AI 助手 | Cmd/Ctrl + I（需安装 AI 插件） |
| 新建任务 | Cmd/Ctrl + N |
| 新建项目 | Cmd/Ctrl + B |
| 新会议 | Cmd/Ctrl + J |
| 打开设置 | Cmd/Ctrl + , |

## 字段默认值

- 截图键默认 `A` → 实际触发 `Cmd/Ctrl + Shift + A`
- 桌面端发送：默认 Enter 发送
- 移动端发送：默认按浮动按钮（`button`）

## 与其他模块的关系

- 快捷键改动只影响当前用户当前终端，不上云
- 修改后立刻生效，无需重启 Electron
