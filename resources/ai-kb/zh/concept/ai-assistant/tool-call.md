---
id: ai-assistant.tool-call.concept
title: 工具调用的流式事件结构
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - tool_call 是什么
  - AI 工具调用的过程
  - 工具调用气泡
  - AI 调用了工具显示什么
  - tool_call 事件
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 单次回复可包含多次工具调用（并行或串行），不限于一次
  - 工具调用结果不计入用户上下文 token，但会消耗会话 token
  - 用户不能在前端取消已发出的工具调用，只能整体中断本轮回复
last_verified: v1.7.90
---

# 工具调用的流式事件结构

## 定义
AI 助手回复以流式 SSE 推送给前端，事件中除了文本增量，还会出现工具调用片段。每次工具调用在浮窗里渲染为一个独立气泡，展示"工具名 + 参数 + 状态"，让用户能看到 AI 在背后做什么。

## 一次完整工具调用的事件序列
1. `tool_call_start`：模型决定调工具，前端插入气泡，状态置「执行中」
2. `tool_call_arguments`（可多次）：参数 JSON 增量流式拼接
3. `tool_call_result`：后端/前端返回结果，状态变「完成」或「失败」
4. 模型基于结果继续生成 `message` 文本

## 气泡可见信息
- 工具名（如 `list_tasks`）
- 入参 JSON（折叠/展开）
- 出参摘要（成功）或错误码（失败）
- 执行耗时

## 与普通文本的关系
- 工具结果不直接回给用户，而是回灌给模型
- 模型读结果后再生成下一段自然语言回答（"我找到 3 条任务……"）
- 用户体感是"AI 一边查一边说"

## 不支持
- 工具调用进行中无法手动改参数；要重来需点重试或重新提问
- 失败的工具调用不会自动二次重试（模型可能换工具或道歉）

## 相关
- 工具机制总览：[[ai-assistant.tools.concept]]
- 工具清单：[[ai-assistant.tools-list.concept]]
- 失败处理：[[ai-assistant.tool-failed.faq]]
