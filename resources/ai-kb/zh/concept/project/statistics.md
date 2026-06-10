---
id: project.statistics.concept
title: 项目统计（任务数 / 完成率 / 我的待办）
type: concept
feature: project
scope: end-user
locale: zh
aliases:
  - 项目进度
  - 项目完成率
  - 项目里我有多少任务
  - 项目任务数
  - 项目进度统计
related_tools: [get_project, list_tasks]
related_pages: [project_detail, dashboard]
prerequisites: []
negative:
  - 统计不含归档任务（archived_at 非空的不计）
  - 统计实时计算，没有定时缓存，大项目（>10000 任务）会略慢
  - 「我的待办」按 ProjectTaskUser 命中当前用户 + complete_at 为空
last_verified: v1.7.90
---

# 项目统计（任务数 / 完成率 / 我的待办）

## 项目级统计字段
DooTask 项目列表与详情页展示一组实时统计，主要来自 `Project::getTaskStatistics()` 方法。

| 字段 | 含义 |
|---|---|
| `task_num` | 项目内未归档任务总数 |
| `task_complete` | 已完成任务数（complete_at 非空 + 未归档） |
| `task_percent` | 完成率（task_complete / task_num） |
| `task_my_num` | 当前用户名下的任务数（负责人 + 协作者） |
| `task_my_complete` | 当前用户名下已完成 |
| `task_my_percent` | 当前用户完成率 |

## 显示位置
- 项目列表卡片：「X / Y 完成」+ 进度条
- 项目详情页顶部：完整统计 + 我的细分
- 仪表盘「今日待办」/「我的任务」卡片：跨项目汇总

## 计算口径
- **不含归档任务**（archived_at 非空的任务不在分母）
- 不含已删任务（deleted_at 非空）
- 「我的」只看 ProjectTaskUser，按 userid 命中（不论 owner=0/1）
- 子任务计入分母（不是只看父任务）

## 用户级跨项目统计（user__counts）
- 当前用户的全部项目数
- 待完成任务总数（跨项目）
- 已完成任务总数（跨项目）

## 不支持
- 没有按时间段的统计（只能用导出 [[project.export.howto]] 自己分析）
- 不能按标签 / 列分组统计
- 不能给"未指派任务"算独立指标
