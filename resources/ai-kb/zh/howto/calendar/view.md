---
id: calendar.view.howto
title: 切换日历月 / 周 / 日 视图
type: howto
feature: calendar
scope: end-user
locale: zh
aliases:
  - 月视图
  - 周视图
  - 日视图
  - 切日历视图
  - 日历显示方式
related_tools: [list_tasks]
related_pages: [calendar]
prerequisites: []
negative:
  - 无议程视图（agenda），仅月 / 周 / 日 三种
  - 移动端只有月视图，桌面端三种都有
  - 切换视图会重新拉数据（按视图范围 `rangeTime`）
last_verified: v1.7.90
---

# 切换日历月 / 周 / 日 视图

## 入口
- **桌面端**：日历右上角视图切换按钮组「月 / 周 / 日」
- **移动端**：固定月视图，无切换按钮
- 选择记住：用户偏好持久化到本地 `cacheCalendarView`

## 三种视图差异

| 视图 | 显示范围 | 全天事件位置 | 拖动改时间精度 |
|---|---|---|---|
| 月 | 当前月（含跨月头尾） | 单元格顶部条 | 仅改日期 |
| 周 | 当前周 7 天 | 顶部全天条带 | 改日期 + 时段 |
| 日 | 当前一天 | 顶部全天条 | 改具体时段 |

## 操作步骤
1. 点视图按钮 → 立即切换
2. 浏览方向：左右箭头切换前 / 后一段（月→月、周→周、日→日）
3. 中间「今天」按钮回当天

## 拖动改时间
- 月视图：拖任务卡到另一格 → 改 end_at 的日期（保留时间部分）
- 周 / 日视图：拖任务卡纵向 → 改时段，详见 [[calendar.drag.howto]]
- 移动端：只读，详见 [[calendar.mobile.faq]]

## 切换后数据
- 切到下个月 / 下周 / 下日 → 自动按新范围查询任务
- 范围变化触发 `getTasks(rangeTime)` 拉数据

## 不支持
- 无年视图（仅月）
- 无议程 / 时间线视图
- 移动端无周 / 日视图
