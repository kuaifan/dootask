---
id: dashboard.concept
title: 仪表盘是什么 / 显示哪些卡片
type: concept
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘是什么
  - 仪表盘有什么
  - 仪表盘卡片
  - 仪表盘有哪些卡片
  - 仪表盘显示什么
  - 仪表盘显示哪些卡片
  - 个人工作台
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 仪表盘只显示「我作为负责人 owner=1」的任务，协作任务在专门的分组
  - 仪表盘不显示项目进度 / 团队整体数据，仅个人视角
  - 仪表盘不显示已完成任务（默认）
last_verified: v1.7.90
---

# 仪表盘是什么 / 显示哪些卡片

## 定义
DooTask 仪表盘（Dashboard）是当前用户的个人工作台，回答「今天我要做什么」。仪表盘由顶部 3 张数字卡片 + 下方 4 个分组列表卡片组成，从前端 store 的 `dashboardTask` 与 `assistTask` getter 实时计算，按时间分类显示当前用户名下的未完成任务。

## 顶部 3 张数字卡片
| 卡片 | 含义 |
|---|---|
| 今日到期 | end_at 在今天范围内、未完成、未归档的任务数 |
| 超期任务 | end_at 已过今天 23:59:59 但未完成的任务数 |
| 待完成 | 没设 end_at 或 end_at 还未到的未完成任务数 |

## 下方 4 张分组卡片
1. **今日到期** 卡片 — 今天必须完成的任务
2. **超期任务** 卡片 — 需要立刻处理或调整截止时间的任务
3. **待完成** 卡片 — 后续要做的任务（含无截止时间）
4. **协助的任务** 卡片 — 我作为 [[task.field.owner-assist.concept]] 中协作者（assist）的任务，独立于上面三组

## 数据来源
- 全部从 ProjectTask 表派生（[[task.field.deadline.concept]] 的 end_at 字段）
- 仅算 owner=1 命中当前用户的（前 3 组），assist 命中的（第 4 组）
- 已归档（[[task.archive.howto]]）/ 已完成（[[task.complete.howto]]）的任务不显示

## 与「项目统计」的区别
- 仪表盘：个人视角，全部项目
- [[project.statistics.concept]]：单项目视角，整项目

## 不支持
- 不显示团队 / 部门维度的汇总
- 不能切换看别人的仪表盘
- 不能增加自定义卡片
