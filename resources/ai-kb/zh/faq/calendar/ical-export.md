---
id: calendar.ical.faq
title: 不支持 iCal 导出 / 外部日历订阅
type: faq
feature: calendar
scope: end-user
locale: zh
aliases:
  - iCal 订阅
  - 苹果日历同步
  - Google Calendar 同步
  - 日历导出 ics
  - webcal 订阅
related_tools: []
related_pages: [calendar]
prerequisites: []
negative:
  - DooTask 当前版本不支持 iCal / webcal / Google Calendar 同步
  - 不能把 DooTask 日历嵌入 Outlook / Apple 日历
  - 没有 .ics 文件导出按钮
last_verified: v1.7.90
---

# 不支持 iCal 导出 / 外部日历订阅

## 问题
想把 DooTask 日历的任务同步到 Apple Calendar / Google Calendar / Outlook / Thunderbird 等外部日历应用。

## 原因
DooTask v1.7.90 还没有实现 iCal / webcal / .ics 文件相关接口：
- 无 `Calendar` HTTP endpoint 输出 .ics
- 无 webcal:// 订阅链接
- 无 Google Calendar / Outlook OAuth 同步
- 任务数据库结构也没有 `uid` 字段对应 iCal 标准

## 替代方案
1. **看任务列表**：用 [[task.field.deadline.concept]] 字段在 DooTask 内的视图里看
2. **任务导出 Excel**：项目级 [[project.export.howto]]，得到 .xlsx 表，再自行转格式
3. **桌面端通知**：用 DooTask 自己的桌面提醒（[[task.notify.concept]]）替代外部日历提醒
4. **移动端 APP 推送**：UMENG 推送同样替代日历提醒

## 未来支持？
这块在 P1 / P2 路线图（issues 可能跟踪），但 v1.7.90 主线版本无计划。

## 不支持
- 不支持 iCal 导出 / 订阅
- 不支持 webcal:// 链接
- 不支持与 Google / Outlook / Apple Calendar 双向同步
- 不支持 Office 365 / Exchange 集成
