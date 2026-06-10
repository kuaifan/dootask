---
id: calendar.mobile.faq
title: 移动端日历不能拖动 / 编辑
type: faq
feature: calendar
scope: end-user
locale: zh
aliases:
  - 手机日历改不了
  - 移动端拖不了任务
  - 日历手机版只读
  - 手机改任务时间
  - 移动端日历限制
related_tools: [update_task]
related_pages: [calendar]
prerequisites: []
negative:
  - 移动端日历是设计成只读，不是 BUG
  - 想在手机上改时间，必须进任务详情页
  - 移动端日历仅月视图，无周 / 日视图
last_verified: v1.7.90
---

# 移动端日历不能拖动 / 编辑

## 问题
在手机版 DooTask 打开日历，看不到拖动手柄，也无法长按事件改时间。

## 原因
DooTask 在移动端（`windowTouch=true`）把日历组件设置为 `is-read-only=true`，禁用所有拖动 / 编辑操作。原因：
- 手机屏幕小，拖动精度低，容易误操作
- 触摸滑动手势与日历滚动冲突
- 移动端任务详情页编辑更可靠

## 解决
1. **改 end_at 的解决方案**：点开任务事件 → 进任务详情 → 编辑 [[task.field.deadline.concept]] 字段 → 保存
2. **新建任务的解决方案**：移动端在「项目」Tab 进项目 → 「+ 新建任务」 → 填时间字段
3. **想用拖动**：换桌面端（[[calendar.drag.howto]]）

## 移动端日历能做什么
- 看月视图（无周 / 日切换）
- 点事件查看详情
- 跳转到任务详情页
- 上下滑动切换月份

## 不支持
- 不能拖动改时间
- 不能选区建任务（[[calendar.create.howto]]）
- 不能切换周 / 日视图
- 不支持时区切换
