---
id: ai-assistant.float-button.concept
title: AI 助手浮按钮
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 浮窗按钮
  - AI 浮窗
  - AI 浮窗怎么用
  - 屏幕角落的 AI 球
  - AI 小球
  - 那个小气泡
  - 浮动按钮
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已在系统设置「AI 模型」中配置至少一个模型
negative:
  - 浮按钮位置只保留水平/垂直两个距离，不存绝对坐标，换屏分辨率不会跑出屏外
  - 收起状态下点击直接打开 AI 助手，不会再触发拖动
  - 拖动 ≥ 5px 或按下 ≥ 200ms 算拖动；小于该阈值才视为点击
last_verified: v1.7.90
---

# AI 助手浮按钮

## 定义
浮按钮是 DooTask 桌面端 / 移动端常驻在屏幕角落的圆形 AI 入口，44px 直径，点击呼出 AI 助手浮窗或弹窗。它独立挂在 `<body>` 上，与当前路由解耦，**任何登录后页面都能看到**（登录页除外）。

## 关键属性
- **位置存储**：只保存两个距离（距左 / 距右 + 距上 / 距下）和「贴边收起」状态，写入 IndexedDB key `aiAssistant.floatButtonPosition`
- **默认位置**：桌面端右下角（距右 24px、距底 100px）；移动端竖屏靠右、距底约 1/4 屏高
- **可拖动**：桌面端鼠标按住拖动；移动端单指触摸拖动
- **贴边收起**：靠近左 / 右屏幕边缘 ≤ 12px 时延迟自动收起为竖条（桌面端 1 秒、移动端 5 秒）
- **收起态再点开**：收起状态下点按钮直接打开 AI 助手，不会再拖动

## 显示条件
- 已安装 ai 插件（`microAppsIds` 包含 `ai`）
- 已登录（`userId > 0`）
- 当前不在登录页
- AI 助手弹窗未打开时

## 与浮窗的关系
浮按钮只是「入口」，点击触发 `openAIAssistantGlobal` 事件。真正的对话窗口由 [[ai-assistant.modal.concept]] 渲染。

## 不支持
- 不支持双击 / 长按弹出菜单（除了移动端长按用于拖动）
- 不支持把浮按钮完全隐藏后再用快捷键恢复——隐藏需在「个人设置」关闭（详见 [[ai-assistant.float-button.howto]]）
