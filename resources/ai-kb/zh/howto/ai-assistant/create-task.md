---
id: ai-assistant.create-task.howto
title: 让 AI 帮我创建任务
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 建任务
  - AI 加 todo
  - AI 新建待办
  - 让 AI 创建一个任务
  - AI 帮我下任务
  - 给小王分个任务
related_tools: [create_task]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 当前用户在目标项目中是成员或负责人
negative:
  - 不能在不是自己项目的位置建任务（会提示无权限）
  - AI 不会未经确认直接建复杂任务，长字段一般会先复述再创建
  - 创建后任务的 ID/URL 会返回，可让 AI 继续打开它
last_verified: v1.7.90
---

# 让 AI 帮我创建任务

## 这是什么
在 AI 浮窗自然语言描述要建的任务，AI 会调 `create_task` 工具在目标项目下新建一条任务，自动解析负责人、截止时间、优先级、所属列。

## 怎么问
一次性把关键信息说全，AI 解析准确率最高：

- "在『官网改版』项目建一个任务：周五前完成首页banner切图，分给小王"
- "建个明天 18 点截止的紧急任务：上线前回归测试，我负责"
- "项目X里加一条任务『写 PRD』，无负责人无截止时间"

## AI 通常会确认
- 模糊的项目名 → 先调 `list_projects` 让你选
- 模糊的"小王" → 调 `search_users` 列同名用户让你确认
- 没说截止时间 → 直接不设置（不会乱猜）
- 没说负责人 → 默认建未分配，可后续追加

## 创建后能继续做什么
- "打开它" → 调页面工具跳详情
- "再加 3 个子任务：前端、后端、测试" → 调 `create_sub_task`
- "把这条同步到 X 群" → 调 `send_message`

## 不支持
- 不能建跨项目任务（任务必须归属一个项目）
- 不能在 AI 这步同时设置自定义字段（需打开任务详情手动改）
- AI 不会主动给任务套已有模板，需明示「按 X 模板创建」

## 相关
- 列任务：[[ai-assistant.list-tasks.howto]]
- 创建项目结构：[[ai-assistant.project-init.howto]]
- 子任务建议：[[ai-assistant.subtask-suggest.howto]]
