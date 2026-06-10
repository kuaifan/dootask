---
id: calendar.timezone.concept
title: 日历的时区处理
type: concept
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历时区
  - 跨时区任务
  - 不同地区看任务时间
  - 时区不一样任务时间
  - 国际团队日历
related_tools: [list_tasks]
related_pages: [calendar, user_settings]
prerequisites: []
negative:
  - 任务数据库无时区字段，时间按 UTC 存储
  - 日历显示按用户当前浏览器的本地时区
  - 同一任务在不同时区用户看到的时段会差几小时
last_verified: v1.7.90
---

# 日历的时区处理

## 定义
DooTask 任务的 `start_at` / `end_at` 在数据库**统一按 UTC 存储**，但前端展示时按浏览器的本地时区呈现。日历组件也按这个规则渲染。所以**跨时区的团队成员看到的同一任务时段可能不一样**，但 UTC 真值一致。

## 显示规则
- 浏览器自动识别本地时区
- 日历事件块按 `(start_at UTC) → 本地时区` 的转换显示
- 日 / 周视图的时段标尺也按本地时区
- 用户切换电脑时区 / 出差 → 日历事件位置相应平移

## 时区选择器
- 日历组件支持显示时区切换器（`timezonesCollapsed=false`）
- 在桌面端日历视图右上角可展开切换显示时区
- 切换显示时区只影响**前端展示**，不修改任务的 UTC 字段

## 跨时区团队的注意事项
1. 北京同事建一个"今天 09:00"的任务（UTC 01:00）
2. 巴黎同事（UTC+1）看到"今天 02:00"
3. 旧金山同事（UTC-7）看到"今天前一天 18:00"

如果团队都用同一约定时区（如 UTC+8），任务时间会一致。

## 不支持
- 任务无 timezone 字段（不能为单个任务指定时区）
- 不能给"全公司"设固定时区
- 不能给日历事件标注"按某时区显示"
- 不支持时区随项目 / 用户偏好设置自动切换
