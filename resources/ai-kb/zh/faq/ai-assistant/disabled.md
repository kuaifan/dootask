---
id: ai-assistant.disabled.faq
title: 我看不到 AI 助手入口
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 找不到 AI
  - AI 浮按钮没有
  - 没有 AI 助手
  - 我的 DooTask 没 AI
  - AI 入口消失
  - 别人有 AI 我没有
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 普通用户无法自助开通 AI，必须由系统管理员开
  - 浮按钮 / 快捷键 / 全局「+」菜单都依赖同一个 ai 插件标识
  - 不同部署的 DooTask 是否带 AI 取决于管理员决定
last_verified: v1.7.90
---

# 我看不到 AI 助手入口

## 问题
- 屏幕右下角没有 AI 浮按钮
- 按 Cmd+I / Ctrl+I 没反应
- 右上角全局「+」菜单里没有「AI 助手」项
- 同事的 DooTask 有 AI，自己的没有

## 常见原因

### 1. AI 插件未安装（最常见）
- AI 助手是**微应用 / 系统插件**，需在「应用市场」安装 `ai` 插件
- 前端通过 `microAppsIds.includes('ai')` 决定是否显示入口；没装则所有 AI 入口都不渲染

### 2. 管理员关闭了 AI 模型
- 即使 ai 插件已装，系统设置 →「AI 模型」未启用任何服务商
- 浮按钮可能仍显示，但模型下拉为空、无法发送，详见 [[ai-assistant.model-empty.faq]]

### 3. License 限制
- 部分 DooTask 部署的 License 不含 AI 模块
- ai 插件不出现在应用市场可装列表

### 4. 老缓存
- 前端 `microAppsIds` 陈旧；刚装好但旧 tab 没刷新
- 解决：强制刷新（Ctrl+F5）

### 5. 路由是登录页
- 浮按钮明确排除登录页（`routeName !== 'login'`）
- 登录后再看

## 解决
1. **联系系统管理员**：到「应用市场」安装并启用 `ai` 插件
2. **管理员配 AI 模型**：在系统设置 →「AI 模型」开启至少一个服务商
3. **强制刷新**：Ctrl+F5 / Cmd+Shift+R 重新拉 `microAppsIds`
4. **重启容器（管理员）**：刚装完插件可能需重启 ai 容器

## 如何区分原因
- 入口**完全不显示**（连浮按钮都没）→ ai 插件未装
- 入口能看到但下拉空 → 模型未配，详见 [[ai-assistant.model-empty.faq]]

## 相关
- [[ai-assistant.entry.howto]]
- [[ai-assistant.model-empty.faq]]
- [[ai-assistant.auth.concept]]
