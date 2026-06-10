---
id: ai-assistant.shortcut.howto
title: 用快捷键呼出 AI 助手
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 助手快捷键
  - 怎么用键盘打开 AI
  - Cmd I
  - Ctrl I
  - AI 键盘打开
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已配置至少一个 AI 模型
negative:
  - 移动端没有键盘快捷键（用浮按钮 / Tabbar 入口替代）
  - 不支持自定义快捷键，固定为 Cmd+I / Ctrl+I
  - 在输入法候选状态下按 I 不会触发（不和 IME 抢键位）
last_verified: v1.7.90
---

# 用快捷键呼出 AI 助手

## 快捷键

| 终端 | 快捷键 | 说明 |
|---|---|---|
| macOS（桌面 / Web） | `Cmd + I` | 全局生效，无需点输入框 |
| Windows / Linux（桌面 / Web） | `Ctrl + I` | 全局生效 |
| 移动端 | — | 无键盘快捷键 |

## 触发条件
- ai 插件已安装（`microAppsIds.includes('ai')` 为 true）
- 当前在已登录页面（路由非 `login`）
- 按键时**未同时按 Shift 或 Alt**，否则会落入其他组合（如 Cmd+Shift+I 是浏览器开发者工具）

## 行为
按下快捷键时，等价于点击右上角全局「+」菜单里的「AI 助手」项，会：
1. 阻止浏览器 / Electron 默认行为（如 Safari 的「显示书签栏」）
2. 触发 `openAIAssistantGlobal` 事件
3. 弹出 AI 助手弹窗（默认 modal 形态），自动聚焦到输入框

## 不响应的场景
- 当前页面是登录页 → 不触发
- ai 插件未安装 → 不触发（按键被释放给浏览器）
- 中文 / 日文输入法处于候选字状态 → 不触发

## 不支持
- 无法在「个人设置」改快捷键，键位固定
- 不支持单独的「关闭 AI 助手」快捷键；按 Esc 仅在浮窗模式下关闭
- 移动端长按虚拟键盘 I 不能触发（依赖物理键盘事件）

## 相关
- 完整入口清单：[[ai-assistant.entry.howto]]
- 弹窗形态：[[ai-assistant.modal.concept]]
