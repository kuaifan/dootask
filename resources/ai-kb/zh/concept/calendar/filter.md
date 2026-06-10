---
id: calendar.filter.concept
title: 日历的过滤维度（按项目分色）
type: concept
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历筛选
  - 日历按项目
  - 日历分组
  - 项目颜色日历
  - 怎么筛日历事件
related_tools: [list_tasks, list_projects]
related_pages: [calendar]
prerequisites: []
negative:
  - 日历无显式"项目筛选"按钮，仅按 calendarId（项目 id）分组着色
  - 不能"只看 X 项目，隐藏其他"
  - 不能按负责人、优先级、标签过滤事件
last_verified: v1.7.90
---

# 日历的过滤维度（按项目分色）

## 定义
DooTask 日历前端把每个任务的 `project_id` 作为 `calendarId`，按项目分组并赋不同颜色，让用户视觉上区分不同项目的任务。这是日历能做的"过滤 / 分组"上限。

## 颜色规则
- 每个项目自动分配一个颜色（按 project_id 散列）
- 同项目所有任务共用一个颜色
- 颜色不直接用 [[task.field.priority.concept]] 的 p_color 或 [[task.field.color.concept]] 的 color
- 仅在日历视图独立设色，不影响列表 / 看板

## 显式筛选（不支持）
| 维度 | 是否支持 | 替代 |
|---|---|---|
| 按项目 | 部分（仅着色） | 进项目详情看 |
| 按负责人 | ✗ | 仪表盘 / 项目页 |
| 按优先级 | ✗ | 项目列表 |
| 按标签 | ✗ | 项目列表 |
| 按是否完成 | ✗（默认隐藏完成） | 任务列表 |
| 按工作流状态 | ✗ | 项目工作流视图 |

## 想做"只看 X 项目的日历"
- 进项目详情 → 切到日历视图（每个项目有自己的日历视图）
- 比全局日历的筛选粒度更细
- 但项目级日历也仅显示该项目的任务

## 不支持
- 日历无显式筛选 UI
- 不能保存"筛选预设"（如"只看紧急任务"）
- 不能按用户标签 / 部门做日历视图
