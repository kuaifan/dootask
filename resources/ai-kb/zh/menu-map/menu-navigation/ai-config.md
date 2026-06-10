---
id: menu-navigation.ai-config.menu-map
title: AI 模型配置入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - AI 配置在哪
  - 怎么配置 AI 模型
  - AI 设置入口
  - 加 Claude 的 key 在哪
  - 模型配置
related_tools: []
related_pages: []
prerequisites:
  - 已安装 ai 插件
  - 需要系统管理员权限
negative:
  - AI 模型配置不在「系统设置」菜单里，入口在「AI 助手」应用内的设置面板
  - 普通成员打开 AI 助手看不到设置入口（提示仅管理员可操作）
  - 未安装 ai 插件时「AI 助手」应用不出现
last_verified: v1.7.90
---

# AI 模型配置入口在哪

## 路径
- 桌面端：左侧栏「应用」→「AI 助手」应用 → 各 AI 机器人（供应商）卡片上的设置按钮 → 打开设置面板（仅管理员可见）
- 移动端：底部 Tabbar「应用」→「AI 助手」→ 同上
- 快捷键：无
- 注意：不在「系统设置」菜单里，旧版「系统设置 → AI 设置」入口已废弃（v1.4.35 起）

## 能配置
- 各供应商（ChatGPT/OpenAI、Claude、DeepSeek、Gemini、Grok、Ollama、智谱、通义千问、文心一言等）的 API Key、Base URL
- 模型列表（一行一个，可点「使用默认模型列表」一键填入）与默认模型
- 代理、Temperature、默认提示词
- 另有 MCP 工具配置和视觉（识图）模型配置卡片

## 权限要求
- 系统管理员（`userIsAdmin`）才能打开设置面板，配置全局生效
- 普通成员只能使用 AI 对话和切换可用模型

## 相关
- AI 模型配置步骤：[[system-setting.ai-model.howto]]
- AI 机器人配置：[[system-setting.ai-bot.howto]]
- 用户侧 AI 助手：[[menu-navigation.ai-assistant.menu-map]]
