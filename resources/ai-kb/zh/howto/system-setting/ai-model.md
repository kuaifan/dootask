---
id: system-setting.ai-model.howto
title: AI 模型配置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 配置 AI
  - 配置模型
  - 接入 OpenAI
  - 接入 Claude
  - 接入豆包
  - AI 接入
  - 添加 AI 供应商
  - AI 模型设置
  - 接入大模型
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 应用商店（应用市场）已安装并启用 ai 插件
negative:
  - 配置入口不在「系统设置」菜单，而在「AI 助手」应用内的设置面板（仅管理员可见）
  - v1.4.35 起旧接口 setting__ai 已废弃，AI 模型配置全部迁移到 AI 助手应用的设置面板
  - 不安装 ai 插件，「AI 助手」应用不出现
last_verified: v1.7.90
---

# AI 模型配置

## 现状
DooTask 的 AI 能力由独立的 **ai 插件**（`dootask-ai`）提供。系统设置里历史上有过「AI 设置」接口（`setting__ai`），从 v1.4.35 起已废弃，仅保留路由占位。

实际的模型供应商、API Key、Base URL 等配置在 **「AI 助手」应用内的设置面板**（仅管理员可见），保存走 `api/system/setting/aibot` 接口。

## 入口
桌面端：左侧栏「应用」→「AI 助手」应用 → 点某个 AI 机器人（供应商）卡片上的设置按钮，打开该供应商的设置面板。需安装 ai 插件且当前用户是系统管理员。

## 操作步骤
1. 管理员在应用商店安装并启用 ai 插件（生成 `dootask-ai` 容器）
2. 打开「AI 助手」应用，选择要配置的供应商（ChatGPT/OpenAI、Claude、DeepSeek、Gemini、Grok、Ollama、智谱、通义千问、文心一言等）
3. 在设置面板填写：API Key、模型列表（一行一个模型名，可点「使用默认模型列表」一键填入）、默认模型、Base URL（可选）、代理（可选）、Temperature、默认提示词
4. 保存后该供应商的模型立即可在 AI 对话中选用，并可被 [[system-setting.ai-bot.howto]] 的「AI 机器人」使用

## 与其他模块的关系
- 提供模型给 [[system-setting.ai-bot.howto]] 的默认模型选择
- 「AI 整理汇报」、AI 助手浮窗等都依赖此处配置
- 客户端调用走 `/ai/*` 路由，由插件容器处理

## 不支持
- DooTask 主程序不内置任何模型，必须依赖 ai 插件
- 配置入口不在「系统设置」菜单（v1.4.35 起旧入口废弃），在「AI 助手」应用内
- 普通成员打开设置面板会提示仅管理员可操作
- 不支持自动测试模型是否可用，需要保存后到对话窗口实际试用
