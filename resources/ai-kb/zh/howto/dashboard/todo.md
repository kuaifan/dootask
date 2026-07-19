---
id: dashboard.todo.howto
title: 「待完成」任务如何计算
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 待完成任务
  - 我的待办
  - 没截止时间的任务
  - 后续要做的
  - 接下来要做什么
related_tools: [list_tasks, update_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - 「待完成」不包含今天剩余时间内到期的任务
  - 开始时间晚于当前时间的任务单独归入「待开始」
  - 协助任务单独归入「我协助的」
last_verified: v1.8.69
---

# 「待完成」任务如何计算

## 统计规则
个人视角「待完成」包含当前用户负责、已经开始、未归档、未完成，并符合以下任一条件的任务：

- 没有设置截止时间；
- 截止时间晚于今天，即明天或更晚。

今天尚未到截止时刻的任务进入 [[dashboard.today.howto]]；已经超过截止时刻的进入 [[dashboard.overdue.howto]]；开始时间还没到的进入 [[dashboard.upcoming.howto]]。

## 页面操作
- 点击顶部「待完成」卡片会滚动到待完成分组。
- 如果存在待开始任务，卡片右侧会同时显示「待开始」数量，点击该区域定位到待开始分组。
- 列表按截止时间升序排列，没有截止时间的任务排在后面。
- 点击任务行打开任务详情，可补充开始时间、截止时间、负责人或优先级。

仪表盘不提供按项目或优先级重新排序待完成任务的功能。
