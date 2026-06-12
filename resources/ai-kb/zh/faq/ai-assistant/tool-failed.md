---
id: ai-assistant.tool-failed.faq
title: AI 工具调用失败怎么办
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 调工具报错
  - tool_call failed
  - AI 操作失败
  - AI 工具不响应
  - AI 卡在执行中
  - AI 调用超时
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 工具失败不会自动二次重试，需用户重新触发
  - 失败不会回滚已成功的工具（如已建任务再发消息失败，任务保留）
  - 隐藏错误细节属正常，敏感字段不会暴露给用户
last_verified: v1.7.90
---

# AI 工具调用失败怎么办

## 问题
浮窗里 AI 调工具的气泡显示「失败」「执行超时」「无权限」「找不到资源」等，回复也提示"没能完成"。

## 常见原因
- **后端短时不可用**：`mcp_server` 容器重启 / 网络抖动
- **权限不足**：操作了你没权限访问的资源（如别人的任务、非成员的项目）
- **参数错误**：AI 推断的 ID 不存在（如任务已被删）
- **超时**：单次工具调用默认 30 秒超时，长操作（大列表/复杂搜索）会断
- **页面不在线**：页面操作（打开任务/跳页面/操作元素）需要前端 socket 在线，关浏览器后立刻让 AI 操作会失败
- **模型不支持 tool call**：选了纯文本模型，根本不会调

## 解决
1. **重试**：浮窗里点工具气泡上的「重试」按钮，或回复"再试一次"
2. **换措辞**：原句太模糊就给更具体的 ID / 关键词
3. **检查权限**：项目级问负责人加成员；任务级问任务负责人加可见用户
4. **换模型**：选标注「支持工具」的模型，浮窗顶部切换
5. **刷新页面**：页面动作失败时刷新一次后再让 AI 重试
6. **联系管理员**：连续失败请通知管理员看 `mcp_server` 插件日志

## 相关
- 工具机制：[[ai-assistant.tools.concept]]
- 权限不足：[[ai-assistant.tool-permission.faq]]
- MCP 不可用：[[ai-assistant.mcp-down.faq]]
