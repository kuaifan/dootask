---
id: calendar.edit.concept
title: 在日历事件上能做什么操作
type: concept
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历事件点击
  - 点日历任务怎么办
  - 日历任务详情
  - 日历改任务
  - 日历事件编辑
related_tools: [update_task, complete_task, delete_task]
related_pages: [calendar, task_detail]
prerequisites: []
negative:
  - 日历上无独立的"事件编辑"窗口，所有改动走任务详情页
  - 无法在日历直接添加协作者、改可见性等非时间字段
  - 移动端日历仅查看（[[calendar.mobile.faq]]）
last_verified: v1.7.90
---

# 在日历事件上能做什么操作

## 是什么
日历显示的"事件"本质是 ProjectTask 任务的时间投影（[[calendar.concept]]）。点击事件 / 拖动 / 右键的所有操作最终都改 ProjectTask 字段。

## 桌面端操作矩阵

| 操作 | 做法 | 改的字段 |
|---|---|---|
| 看任务详情 | 单击事件块 | 无（跳转任务详情页） |
| 改 end_at 时间 | 拖动事件块（[[calendar.drag.howto]]） | end_at / start_at |
| 改 duration | 拖事件边缘 | start_at 或 end_at |
| 完成任务 | 进任务详情勾选完成（[[task.complete.howto]]） | complete_at |
| 删除任务 | 进任务详情删除（[[task.delete-restore.howto]]） | deleted_at |
| 改其他字段（描述 / 优先级 / 标签） | 进任务详情编辑（[[task.edit.howto.basic]]） | 各字段 |

## 移动端操作矩阵
- 单击事件 → 跳转任务详情
- 所有编辑都在任务详情里做
- 拖动 / 右键 / 改 duration 都不支持

## 创建新事件
- 在日历空白处单击 / 拖框 → 详见 [[calendar.create.howto]]
- 创建的是带时间的任务，不是独立事件

## 不支持
- 日历事件无独立的"右键菜单"提供任务字段一键编辑
- 不能在日历"复制事件"为下一周（用 [[task.recurring.howto]] 循环代替）
- 不能给日历事件单独打 calendar 标签（任务标签是 [[task.field.tag.concept]]）
