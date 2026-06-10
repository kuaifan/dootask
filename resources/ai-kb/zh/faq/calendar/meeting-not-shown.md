---
id: calendar.meeting-not-shown.faq
title: 为什么日历不显示会议 / 签到 / 报告
type: faq
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历没会议
  - 会议不在日历
  - 日历看不到签到
  - 日历没工作报告
  - 日历事件不全
related_tools: [list_tasks]
related_pages: [calendar, meeting]
prerequisites: []
negative:
  - 日历**仅显示任务**（ProjectTask），不显示会议、签到、报告
  - 这是设计行为，不是 BUG
  - 没有"统一日历"功能合并所有时间线类内容
last_verified: v1.7.90
---

# 为什么日历不显示会议 / 签到 / 报告

## 问题
打开 DooTask 日历，只看到自己负责的任务，没看到会议、签到打卡、工作报告。

## 原因
DooTask 日历的数据源**只有 ProjectTask 一张表**：
- 会议保存在 `Meeting` 表（独立模块）
- 签到保存在 `Checkin` 表（独立模块）
- 工作报告保存在 `Report` 表（独立模块）

这些表都不参与日历视图。日历视图聚焦"我自己时间安排"语义，会议 / 签到 / 报告各有自己的入口。

## 怎么看会议时间
- 桌面端：左侧栏 → 「会议」 → 会议列表
- 会议详情页能看到 start_at / end_at
- 未来想关注「我有什么会议」用 [[dashboard.concept]] 是不够的（仪表盘也不显示会议）

## 怎么看签到打卡时间
- 桌面端：左侧栏 → 「签到」（如启用了 checkin 应用）
- 签到记录按日列，与日历独立

## 怎么看工作报告
- 桌面端：左侧栏 → 「工作报告」
- 报告按发送 / 接收时间显示，与日历独立

## 不支持
- 日历无法合并显示多个模块（任务 + 会议 + 签到 + 报告）
- 没有"统一时间线"视图
- 不能在日历里直接建会议 / 签到 / 报告（详见 [[calendar.create.howto]] 只能建任务）
