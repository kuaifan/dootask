---
id: electron-client.shortcut.concept
title: 桌面端全局快捷键
type: concept
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 桌面端快捷键
  - 全局快捷键
  - 桌面热键
  - 截图快捷键
  - AI 助手快捷键
  - 怎么截图
  - 新建任务快捷键
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 全局快捷键仅在 DooTask 进程运行中(含后台 / 托盘)生效,App 退出后不可用
  - 快捷键冲突时(被其他软件占用)DooTask 注册会失败,无错误提示
  - 部分快捷键(如截图)用户可自定义按键,默认未绑定
last_verified: v1.7.90
---

# 桌面端全局快捷键

## 快捷键总览
桌面端注册了一组系统级全局快捷键,在 App 处于后台 / 最小化 / 托盘时也能触发。下表 `Mod` 在 macOS 是 `Command`、Windows / Linux 是 `Ctrl`。

| 操作 | macOS | Windows / Linux | 备注 |
|---|---|---|---|
| AI 助手 | Cmd + I | Ctrl + I | 需安装 AI 微应用 |
| 新建任务 | Cmd + N | Ctrl + N | 弹出快速创建 |
| 新建项目 | Cmd + B | Ctrl + B | |
| 新会议 | Cmd + J | Ctrl + J | |
| 打开设置 | Cmd + , | Ctrl + , | |
| 下载内容 | Cmd + Option + L | Ctrl + Alt + L | 打开下载管理器 |
| 截图 | Cmd + Shift + (自定义字母) | Ctrl + Shift + (自定义字母) | 用户在设置里指定字母 |

## 应用内导航
| 操作 | macOS | Windows |
|---|---|---|
| 后退 | Cmd + ← | Alt + ← |
| 前进 | Cmd + → | Alt + → |
| 刷新 | Cmd + R | Ctrl + R / F5 |
| 强制刷新 | Cmd + Shift + R | Ctrl + Shift + R |
| 关闭窗口 | Cmd + W | Ctrl + W |
| 退出 App | Cmd + Q | Alt + F4 |

## 自定义截图快捷键
1. 个人设置 → 「键盘」→「截图快捷键」
2. 输入框只接受一个字母 / 数字(自动大写)
3. 实际组合键为:`Mod + Shift + 你填的字母`
4. 留空表示不绑定

## 注册原理
桌面端通过 Electron `globalShortcut.register` 在 App 启动时注册,App 退出时 `unregisterAll`。冲突时(被其他 App 抢先注册)会静默失败。

## 与网页端的对比
网页端只有应用内键盘(必须 DooTask 标签页激活才生效),没有系统级全局快捷键。详见 [[web-client.shortcut.concept]]。

## 不支持
- 不支持把全部快捷键改成自定义(仅截图键可自定义)
- 不支持「按键组合录制」(只能输入字母)
- 不支持移动端手势(那是 [[mobile-client.gesture.concept]] 的能力)
