---
id: calendar.concept
title: 日历是什么 / 数据源
type: concept
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历是什么
  - 日历显示什么
  - 日历数据从哪
  - 日历来自任务吗
  - 任务怎么变日历事件
related_tools: [list_tasks]
related_pages: [calendar]
prerequisites: []
negative:
  - 日历**没有独立的事件表**，全部从 ProjectTask 派生
  - 不显示会议（Meeting）、签到、报告，仅显示任务
  - 仅显示当前用户作为负责人（owner=1）的任务，不显示协作任务
last_verified: v1.7.90
---

# 日历是什么 / 数据源

## 定义
DooTask 日历是把 [[task.field.deadline.concept]] 有 end_at 的任务按时间轴铺到月 / 周 / 日格子里的展示视图。它**没有独立的 schedule / event 表**，所有事件完全来自 `ProjectTask` 表，按 `start_at` 和 `end_at` 字段绘制。

## 数据规则
- 数据源：ProjectTask 表
- 必备字段：`end_at` 非空（没 end_at 的任务不会出现在日历）
- 关系：`ProjectTaskUser.owner = 1` 命中当前用户
- 状态：`archived_at IS NULL`（已归档不显示）
- 范围：按当前可视时间范围 `rangeTime` 查询，切换月 / 周 / 日时自动调整

## 事件分类
| 类型 | 判断规则 | 显示样式 |
|---|---|---|
| 全天 | start_at <= 今天 00:01 且 end_at >= 今天 23:59 | 顶部横条 |
| 定时 | start_at / end_at 在同一天内的具体时段 | 时间段块 |

详见 [[calendar.allday.concept]]。

## 与其他模块的关系
- **仪表盘**（[[dashboard.concept]]）：列表形式，按今日 / 超期 / 待完成分类，不显示位置
- **任务列表**：按列表展示，可看已完成 / 已归档
- **日历**：按时间格展示，仅未归档 + 有 end_at + 我是负责人

## 不支持
- 没有独立日历事件（必须先建任务，详见 [[calendar.create.howto]]）
- 不能订阅他人 / 部门日历
- 不能 iCal 导出 / 外部日历同步
- 详见 [[calendar.ical.faq]]
