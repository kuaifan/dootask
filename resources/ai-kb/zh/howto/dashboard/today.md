---
id: dashboard.today.howto
title: 「今日到期」卡片用法
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 今日到期
  - 今天要做的任务
  - 今日待办
  - 今天截止的任务
  - 今日任务
related_tools: [list_tasks, complete_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - "「今日到期」只算今天 00:00-23:59 的 end_at，不含明天"
  - 协作任务（assist）不会出现在这里，单独走「协助的任务」
  - 完成今日任务不会立即从列表消失，需要刷新（仪表盘 600ms 后定时刷新）
last_verified: v1.7.90
---

# 「今日到期」卡片用法

## 是什么
仪表盘顶部「今日到期」数字 + 下方同名分组列表，展示我作为 [[task.field.owner-assist.concept]] 中 owner=1 的任务里，end_at 落在今天的、未完成、未归档的全部任务。

## 数据规则
- 时间范围：`今天 00:00:00 ≤ end_at ≤ 今天 23:59:59`（按用户本地时区）
- 状态：`complete_at IS NULL` 且 `archived_at IS NULL`
- 关系：`ProjectTaskUser.owner = 1` 命中当前用户
- 不含子任务的统计累加（每个任务独立判断）

## 入口
- 桌面端：仪表盘顶部第 1 张数字卡
- 点击卡片或分组标题 → 展开 / 折叠分组

## 卡片中能做什么
- 点任务行 → 进入任务详情（[[task.edit.howto.basic]]）
- 行尾复选框 → 直接标记完成（[[task.complete.howto]]）
- 优先级色块 / 标签可视化

## 完成后行为
- 标记完成后 `complete_at` 写入当前时间
- 600ms 后仪表盘自动刷新（防抖定时器）
- 完成项从「今日到期」列表移除，分组数字 -1

## 不支持
- 不能在仪表盘里直接改 end_at（必须进任务详情）
- 不能批量勾选完成多条任务
- 不能筛选「只看某项目的今日到期」
