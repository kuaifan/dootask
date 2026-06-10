---
id: ai-assistant.model.concept
title: AI 助手可用模型
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 模型
  - 可以选哪些 AI
  - DooTask AI 模型
  - GPT Claude DeepSeek
  - AI 服务商
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已在系统设置「AI 模型」中配置至少一个模型的 API Key
negative:
  - 终端用户不能自己加模型 / 改 API Key，必须由管理员配置
  - 没有「免费内置模型」，DooTask 不自带 API Key
  - 模型下拉为空 = 管理员未配 / 未启用任何模型
last_verified: v1.7.90
---

# AI 助手可用模型

## 定义
AI 助手在浮窗底部下拉框展示的「可选模型」，全部来自管理员在系统设置 →「AI 模型」中配置并启用的服务商。前端通过 `GET api/assistant/models` 拉当前生效配置，按服务商分组渲染。

## 支持的服务商分组
按 `AIBotMap` 顺序展示（每组最多前 5 个模型 + 默认模型）：

| key | 显示名 |
|---|---|
| `openai` | ChatGPT |
| `claude` | Claude |
| `deepseek` | DeepSeek |
| `gemini` | Gemini |
| `grok` | Grok |
| `ollama` | Ollama（本地） |
| `zhipu` | 智谱清言 |
| `qianwen` | 通义千问 |
| `wenxin` | 文心一言 |

## 模型 ID
前端模型选项内部 id 是 `{type}:{value}`，如 `openai:gpt-4o`。`type` 路由到对应服务商 SDK，`value` 是模型名（透传上游）。

## 默认模型
每个分组带 `defaultModel`（管理员设置）。首次打开按此顺序选定：
1. 用户上次选过的模型（IndexedDB `aiAssistant.model`）
2. 第一个有 `defaultModel` 的分组的默认模型
3. 第一个分组的第一个模型
4. 否则空

## 模型能力差异
- **多模态**：仅部分模型支持，如 `gpt-4o`、`claude-3-5-sonnet`、`gemini-1.5-pro`
- **长文本**：默认 context 取最近 10 轮，超出截断；与模型最大 token 无关

## 不支持
- 不支持同一问题同时发多个模型对比
- 不支持终端用户自定义模型 / 改 API Key / base_url
- 切换模型不会清空历史会话，会用新模型续接

## 相关
- [[ai-assistant.model-switch.howto]]
- [[ai-assistant.model-empty.faq]]
- [[ai-assistant.multimodal.concept]]
