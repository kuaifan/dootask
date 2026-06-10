---
id: task.recurring.howto
title: 重复 / 循环任务
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 重复任务
  - 循环任务
  - 每天的任务
  - 周报任务自动生成
  - 怎么让任务自动重复
related_tools: [create_task, update_task, complete_task]
related_pages: [task_detail]
prerequisites:
  - 任务已设置 end_at（截止时间），否则无法计算下一周期
negative:
  - 重复任务不能在子任务上独立设置（subtask 不支持 loop）
  - 重复任务的下一份在「上一份被标记完成时」生成，不是按日历预生成
  - 修改循环周期不会回填已生成的实例
last_verified: v1.7.90
---

# 重复 / 循环任务

## 是什么
DooTask 任务支持循环重复（字段 `loop` + `loop_at`），常用于「每日站会」「每周周报」类型任务。每次任务被标记完成时，系统按 `loop` 自动生成下一份新任务，复制原任务字段并按 `loop_at` 推下一次计划时间。

## 支持的周期
- `day` 每天
- `weekdays` 工作日（周一至周五）
- `week` 每周
- `twoweeks` 双周
- `month` 每月
- `year` 每年
- 自定义天数（输入数字，如 3 表示每 3 天）
- `never` 不重复（默认）

## 入口
- 桌面端：任务详情页 → 计划时间区 → 「循环」下拉
- 必须先填 end_at（截止时间）才能启用循环

## 工作原理
1. 标记 [[task.complete.howto]] 完成当前任务
2. 服务端基于 `loop` 与当前 `end_at` 计算下一个 `loop_at`
3. 自动创建新任务并复制：标题、描述、负责人、协作者、标签、优先级、可见性、子任务
4. 新任务的状态为未完成，按新计划时间出现在日历视图

## 不支持
- 不能为同一任务并行启用多个周期
- 循环不复制附件
- 子任务不能独立设置循环
