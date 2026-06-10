---
id: task.field.deadline.concept
title: 任务计划时间（start_at / end_at）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 截止时间
  - deadline
  - 任务到期时间
  - 开始时间
  - 任务计划时间
related_tools: [update_task]
related_pages: [task_detail, calendar]
prerequisites: []
negative:
  - DooTask 任务没有 time_estimate / time_log 字段，不能记录工时
  - 不支持只设开始时间不设结束时间（end_at 是计算到期与循环周期的依赖）
  - 跨时区显示按用户所在时区，但服务端统一存 UTC
last_verified: v1.7.90
---

# 任务计划时间（start_at / end_at）

## 定义
DooTask 任务用两个字段描述时间：
- `start_at`：计划开始时间
- `end_at`：计划截止时间（俗称 deadline）

两者都可空。**填入 end_at 才能启用循环重复、到期提醒、日历展示。**

## 在哪里能看到
- 任务详情页 → 「计划时间」字段
- 任务卡片右下角倒计时 / 日期标签
- 日历视图：按 start_at→end_at 跨日条显示
- 甘特图视图：按 start_at→end_at 长度条显示

## 时间格式
- 桌面端可单选日期或日期+时分
- 默认是「日期」级别（无时分），含时分时存秒级精度
- 全天任务则 end_at 自动设为当天 23:59:59

## 与其他字段联动
- **重复循环**（[[task.recurring.howto]]）依赖 end_at 计算下一次 loop_at
- **到期提醒**（ProjectTaskPushLog）按 end_at 触发推送（默认到期日推送、超期再推送）
- **日历视图** 只显示有 start_at 或 end_at 的任务
- **完成时间**（complete_at）与计划时间无关，是否超期由 complete_at vs end_at 比较

## 不支持
- 不能只设 start_at 不设 end_at 来启用循环
- 不能录工时（time_estimate / time_log 字段不存在）
- 修改 end_at 不会回填已生成的循环任务实例
