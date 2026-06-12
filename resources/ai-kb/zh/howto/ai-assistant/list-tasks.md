---
id: ai-assistant.list-tasks.howto
title: 让 AI 列我的任务
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 我今天有什么任务
  - 帮我看下任务
  - 今天的待办
  - 这周要交什么
  - 列一下任务
  - 给我看待办
  - 我有哪些任务
related_tools: [list_tasks]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 只能列出当前登录用户有权访问的任务（项目成员或负责人/协作者/可见用户）
  - 一次最多返回 100 条，超出请追加筛选条件
  - 不能列别人的私有任务（即便是同部门）
last_verified: v1.7.90
---

# 让 AI 列我的任务

## 这是什么
在 AI 浮窗用自然语言问任务列表，AI 会调 `list_tasks` 工具检索后端，按你说的条件筛选并返回结果摘要。结果通常含任务名、负责人、截止时间、所属项目。

## 怎么问
按你想筛的条件直接说：

- "我今天到期的任务"
- "本周要交的任务"
- "项目 X 里我负责的进行中任务"
- "已逾期未完成的任务"
- "上个月完成的任务"
- "标记了高优先级且没人负责的任务"

## AI 通常会问回的信息
模糊提问时，AI 可能反问以缩小范围：

- 时间范围（今天/本周/本月/自定义）
- 状态（未开始/进行中/已完成/已逾期）
- 项目限定
- 负责人是不是你

## 后续动作
列出任务后可以接续操作，无需重复说"在 X 项目"：

- "把第 2 条标完成" → 调 `complete_task`
- "打开第一条" → AI 在你的页面上跳到任务详情
- "给小王再加一条子任务" → 调 `create_sub_task`

## 不支持
- 不支持跨用户查询别人的任务列表
- 模糊筛选「重要的」「紧急的」不保证命中：依赖任务已设置对应优先级/标签字段
- 不返回已删除任务（除非显式说"被删的任务"）

## 相关
- 创建任务：[[ai-assistant.create-task.howto]]
- 子任务建议：[[ai-assistant.subtask-suggest.howto]]
- 打开任务详情：[[ai-assistant.page-action.howto]]
