---
id: calendar.entry.menu-map
title: 日历入口在哪
type: menu-map
feature: calendar
scope: end-user
locale: zh
aliases:
  - 日历在哪
  - 怎么进日历
  - 时间表入口
  - 我的日程
  - calendar 在哪
related_tools: [list_tasks]
related_pages: [calendar]
prerequisites: []
negative:
  - 日历只显示有 end_at 的任务，不显示会议、签到等其他事件
  - 移动端日历只读，不能拖动改时间
  - 日历不支持其他人的视图
last_verified: v1.7.90
---

# 日历入口在哪

## 路径
- **桌面端**：左侧栏「日历」菜单项（路由 `/manage/calendar`）
- **桌面端 URL**：`https://<域名>/manage/calendar`
- **移动端**：底部 Tabbar 「日历」Tab
- **桌面快捷键**：无

## 谁能看到
- 所有登录用户都能进入日历
- 内容是当前用户私有：显示我作为负责人（owner=1）的有 end_at 的任务
- 详见 [[calendar.concept]]

## 主要内容
- 月 / 周 / 日 视图切换（详见 [[calendar.view.howto]]）
- 任务按 start_at / end_at 显示为日历格中的事件
- 拖动任务改时间（仅桌面端，详见 [[calendar.drag.howto]]）
- 移动端仅查看，不可拖

## 与其他终端的差异
| 终端 | 视图 | 编辑 |
|---|---|---|
| 桌面端 | 月 / 周 / 日 | ✓ 拖拽 |
| 移动端 | 月 | 只读 |

## 不支持
- 不显示会议（B2 模块）、签到、报告
- 不支持订阅他人日历
- 详见 [[calendar.meeting-not-shown.faq]]
