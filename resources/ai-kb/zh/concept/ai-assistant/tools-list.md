---
id: ai-assistant.tools-list.concept
title: AI 助手可用工具清单
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 有哪些工具
  - AI 能调用什么
  - AI 工具列表
  - MCP 工具有哪些
  - AI 都能做什么
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 工具集合由系统维护，普通用户无法自定义增减
  - 列表项名是后端工具名（snake_case），用户不需要记
  - 不在清单中的能力 AI 无法直接执行（例如改系统设置、装插件）
last_verified: v1.7.90
---

# AI 助手可用工具清单

## 用户与组织
- `get_users_basic`：批量取用户昵称/邮箱/头像
- `search_users`：按关键词找人，支持按项目/对话筛选

## 任务
- `list_tasks`：列任务（状态/项目/时间筛选）
- `get_task`：取任务完整详情
- `create_task`、`update_task`、`complete_task`、`delete_task`
- `create_sub_task`：建子任务
- `get_task_files`：取任务附件

## 项目
- `list_projects`、`get_project`、`create_project`、`update_project`

## 消息与对话
- `search_dialogs`：搜对话
- `send_message`：发消息
- `send_task_ai_message`：作为 AI 向任务讨论区发消息
- `get_message_list`：取历史消息

## 文件
- `list_files`、`search_files`、`get_file_detail`、`fetch_file_content`

## 工作报告
- `list_received_reports`、`list_my_reports`、`get_report_detail`、`create_report`、`mark_reports_read`
- `generate_report_template`：基于已完成任务生成报告草稿

## 搜索
- `intelligent_search`：跨任务/项目/文件/联系人/消息统一语义搜索

## 页面操作（非 MCP 工具）
AI 助手还能在你当前的浏览器/桌面端页面上帮你打开任务/项目/对话、跳功能页、点击/输入/选择/滚动页面元素。这类页面操作不在 MCP 工具清单内，由 AI 助手在你的页面上直接执行。

## 图片理解（非 MCP 工具）
把图片交给 AI 助手后，AI 可直接识别图中内容（多模态），无需单独的图片文字提取工具。

## 知识库
- `search_help_docs`：检索本知识库（DooTask 功能说明）
- `get_session_image`：取用户上传到会话的图片
