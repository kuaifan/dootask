---
id: dashboard.today.howto
title: 「今日到期」任务如何计算
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 今日到期
  - 今天要做的任务
  - 今日待办
  - 今天截止的任务
  - 今日任务怎么算
related_tools: [list_tasks, complete_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - 已经过截止时间的今日任务会进入「已超期」，不继续算今日到期
  - 开始时间晚于当前时间的任务归入「待开始」
  - 我协助但不负责的任务单独进入「我协助的」
last_verified: v1.8.69
---

# 「今日到期」任务如何计算

## 统计规则
个人视角的「今日到期」统计当前用户作为负责人、已经开始、未归档且未完成的任务，并同时满足：

- 截止时间在今天；
- 截止时间晚于当前时间；
- 当前用户是任务负责人 `owner=1`。

截止时间已经到达的任务会立即归入 [[dashboard.overdue.howto]]，不是等到第二天才算超期。开始时间还没到的任务进入 [[dashboard.upcoming.howto]]。

## 页面操作
- 点击顶部「今日到期」卡片，会展开对应分组并滚动定位。
- 点击任务行打开任务详情。
- 点击任务状态可以直接完成或调整状态。
- 卡片会显示今日任务中最近的截止时刻。

## 完成后的表现
在当前页面完成任务后，该行会暂时保留并显示划线效果，数量立即减少；后续数据稳定后，任务按完成时间进入 [[dashboard.completed.howto]]。
