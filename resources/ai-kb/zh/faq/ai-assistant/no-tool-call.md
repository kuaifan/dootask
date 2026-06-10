---
id: ai-assistant.no-tool-call.faq
title: AI 应该调工具但没调
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 不调工具
  - AI 只说不做
  - AI 没操作
  - AI 没建任务
  - AI 干说
  - 让 AI 做事它只回答
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 选了纯文本模型时无论怎么提问都不会调工具
  - 即使支持 tool call 的模型也可能误判为"无需工具"，需重提问引导
  - AI 不会暴露完整工具清单，让你逐个点选
last_verified: v1.7.90
---

# AI 应该调工具但没调

## 问题
明明让 AI 帮忙建任务 / 发消息 / 跳页面，但 AI 只口头回答"好的，我会…"或"建议你…"，并没有调用工具实际操作。

## 常见原因
- **模型不支持 function calling**：选了纯文本模型
- **mcp_server 插件未安装/挂掉**：工具列表为空
- **意图不明确**：模型判断为闲聊，没决策需要工具
- **管理员关闭了工具集成**
- **上下文超长**：会话累积过多，模型"忘了"工具能力

## 解决
1. **换模型**：浮窗顶部切到「支持工具」的模型
2. **明确指令**：换成动词命令"建任务"、"发消息"、"打开"
3. **加资源 ID**：给具体的项目名 / 任务 ID
4. **新开会话**：超长会话可能异常，重开试
5. **检查 mcp_server**：让管理员看插件运行状态

## 例子
- 不好："小王任务这事跟一下" → AI 回复"建议您…"
- 好："给小王在项目 X 新建任务：周五前回归测试" → AI 调 `create_task`

## 相关
- 工具机制：[[ai-assistant.tools.concept]]
- 工具列表：[[ai-assistant.tools-list.concept]]
- MCP 不可用：[[ai-assistant.mcp-down.faq]]
