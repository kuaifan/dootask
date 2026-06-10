---
id: calendar.task.concept
title: 日历只显示「我作为负责人的任务」
type: concept
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历看不到协作任务
  - 日历只有自己负责的
  - 日历不显示别人安排
  - 协作任务在哪
  - 日历过滤范围
related_tools: [list_tasks]
related_pages: [calendar]
prerequisites: []
negative:
  - 日历不显示 ProjectTaskUser.owner=0（协作 / assist）的任务
  - 想看协作任务请到 [[dashboard.assist.howto]]
  - 不能切换"显示所有项目任务包括别人的"
last_verified: v1.7.90
---

# 日历只显示「我作为负责人的任务」

## 定义
DooTask 日历的过滤规则相当窄：仅展示 `ProjectTaskUser.owner = 1` 命中当前用户的、有 end_at 的、未归档的任务。所以**协作的任务不会出现在日历上**。这是设计：日历是"我自己要安排时间做什么"的视图，避免被他人的任务挤满。

## 不显示的内容
| 类型 | 为什么不显示 |
|---|---|
| 我是协作者（assist）的任务 | owner=1 过滤掉 |
| 我没设 end_at 的任务 | end_at 是日历定位依据 |
| 已归档的任务 | archived_at 过滤 |
| 已完成的任务 | 大部分场景仅显示未完成（视前端配置） |
| 别人的任务 | 没在 ProjectTaskUser 命中我 |
| 会议（Meeting） | 不是 ProjectTask |
| 签到 / 报告 | 不是 ProjectTask |

详见 [[calendar.meeting-not-shown.faq]]。

## 怎么看协作任务
- 仪表盘「[[dashboard.assist.howto]]」分组
- 任务列表设过滤器「协作的任务」
- 项目详情页所有视图都能看到（不分负责人 / 协作）

## 怎么让任务出现在日历
1. 你必须是该任务负责人（[[task.field.owner-assist.concept]] owner=1）
2. 任务必须有 end_at 字段（[[task.field.deadline.concept]]）
3. 任务未归档、未完成（默认前端配置）

## 不支持
- 不能切换"也显示协作任务"
- 不能给团队成员的日历做合并视图
- 不能看别人日历
