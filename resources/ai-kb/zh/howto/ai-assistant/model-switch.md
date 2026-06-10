---
id: ai-assistant.model-switch.howto
title: 切换 AI 助手使用的模型
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 怎么换 AI 模型
  - 切换模型
  - 用 GPT 还是 Claude
  - 改用别的 AI
  - 模型下拉
related_tools: []
related_pages: []
prerequisites:
  - AI 助手浮窗已打开
  - 管理员配置了 ≥ 1 个模型
negative:
  - 切换模型不会重置当前会话历史，会带着已有 context 直接换模型续聊
  - 切模型不会回放之前的回答（不重生成已有的 message）
  - 不支持把同一个问题同时发到两个模型做对比
last_verified: v1.7.90
---

# 切换 AI 助手使用的模型

## 入口
- 打开 AI 助手浮窗（点浮按钮 / Cmd+I / Ctrl+I）
- 浮窗**底部输入区**有一个「模型下拉框」，左侧位置
- 下拉按服务商分组，如 ChatGPT / Claude / DeepSeek 等

## 操作步骤
1. 在浮窗底部点击模型选择下拉框
2. 在分组列表中选目标模型（如 `Claude → claude-3.5-sonnet`）
3. 选中后立即生效，下一次发送就用新模型

## 选项排序逻辑
- 服务商按 `openai` → `claude` → `deepseek` → `gemini` → `grok` → `ollama` → `zhipu` → `qianwen` → `wenxin` 固定顺序
- 每个服务商分组最多显示 5 个候选 + 1 个管理员设置的默认模型（如不在前 5 内会追加）
- 不在 `AIBotMap` 的自定义服务商按字母序排在最后

## 自动记忆
- 切换后的选择会写入 IndexedDB key `aiAssistant.model`
- 下次打开 AI 助手时自动恢复到上次用过的模型

## 切换后的会话影响
- 历史消息（用户问 + AI 回）保留，会作为 context 发给新模型
- AI 不会重新回答历史问题；只对**下一次新提问**生效
- 多模态：如果切到不支持图片的模型，未来发图会失败（但已有图片预览仍可看）

## 不支持
- 不支持切换后清空历史（要清空需手动新建会话：[[ai-assistant.new-chat.howto]]）
- 不支持「这条回答用模型 A，下一条用模型 B」的单轮指定
- 不支持下拉为空时自动 fallback（下拉空时直接禁用发送，详见 [[ai-assistant.model-empty.faq]]）

## 相关
- 模型概念与服务商列表：[[ai-assistant.model.concept]]
- 下拉为空：[[ai-assistant.model-empty.faq]]
