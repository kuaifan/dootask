---
id: dashboard.upcoming.howto
title: 「待开始」任务如何计算
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 待开始任务
  - 还没开始的任务
  - 未来开始的任务
  - 为什么任务不在待完成
  - start_at 任务
related_tools: [list_tasks, update_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - 待开始只显示在个人视角的列表布局
  - 协助任务不按待开始规则拆分
  - 到达开始时间后任务会重新归入已超期、今日到期或待完成
last_verified: v1.8.69
---

# 「待开始」任务如何计算

## 统计规则
「待开始」包含当前用户负责、未归档、未完成，并且开始时间 `start_at` 晚于当前时间的任务。它用于把尚未进入执行期的任务与当前可处理任务分开。

任务到达开始时间后，会根据截止时间重新分类：

- 截止时间已到：进入 [[dashboard.overdue.howto]]。
- 今天稍后截止：进入 [[dashboard.today.howto]]。
- 没有截止时间或明天以后截止：进入 [[dashboard.todo.howto]]。

## 页面位置
- 待完成摘要卡右侧会显示待开始数量。
- 点击该数量会展开待开始分组并滚动定位。
- 列表按开始时间升序排列，较早开始的任务靠前。

「待开始」只存在于列表布局；四象限布局不单独展示该分类。
