---
id: ai-assistant.tools.concept
title: AI 助手的工具调用机制
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 工具
  - AI 调工具
  - 怎么让 AI 调工具
  - 让 AI 调用工具
  - MCP 工具
  - AI 怎么操作系统
  - function calling
  - AI 能做什么
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 工具是否可用取决于所选模型是否支持 tool/function calling，部分模型仅能纯文本回答
  - 工具调用结果会展示给用户，不会静默执行
  - 用户没权限的操作（如读不到的任务）即使 AI 调工具也会被后端拒绝
last_verified: v1.7.90
---

# AI 助手的工具调用机制

## 定义
DooTask AI 助手通过 MCP（Model Context Protocol）协议调用工具，把"问 AI"扩展为"让 AI 帮我做事"。AI 收到用户请求后判断是否需要调工具，例如查任务、发消息、跳页面，由插件 `mcp_server` 把请求路由到对应的后端 API 或前端动作执行器，再把结果回灌给模型，模型基于结果继续回答。

## 工具来源
- **dootask-mcp 内置工具**：33 个，覆盖任务/项目/消息/文件/报告/搜索/页面操作
- **AI 助手内置工具**：`search_help_docs`（检索本知识库）、`get_session_image`（取多模态图片）
- 工具清单维护在仓库 `resources/ai-kb/_meta/tool-binding.yaml`

## 工具分两类
- **数据工具**：直接调后端 API（如 `create_task`、`send_message`），不依赖前端 UI
- **页面工具**：通过 WebSocket 命令前端浏览器（如 `execute_action` 打开任务、`execute_element_action` 点按钮）

## 触发条件
- AI 模型本身必须支持 tool calling（OpenAI / Claude / DeepSeek / Qwen 等主流模型均支持）
- 管理员在系统设置「AI 模型」中开启该模型
- `mcp_server` 插件已安装并运行

## 相关
- 工具完整清单：[[ai-assistant.tools-list.concept]]
- 工具调用流式事件结构：[[ai-assistant.tool-call.concept]]
- 工具调用失败处理：[[ai-assistant.tool-failed.faq]]
