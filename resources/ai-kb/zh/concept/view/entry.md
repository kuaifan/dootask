---
id: view.entry.concept
title: 项目视图概览（4 种）
type: concept
feature: view
scope: end-user
locale: zh
aliases:
  - 项目有几种视图
  - 看板和列表有什么区别
  - 任务怎么换种方式看
  - 视图类型
  - 任务列表换样式
related_tools: [list_tasks, get_project]
related_pages: [project_detail]
prerequisites: []
negative:
  - 视图选择记在 cacheParameter.menuType，按项目维度持久化（每个项目可不同）
  - 移动端 / 触屏端不支持拖拽（看板、甘特图的拖动改状态在 touch 设备被禁用）
  - 没有「日历视图」「卡片墙视图」等，4 种为全集
  - 工作流视图（workflow）不是顶部切换的项目视图，是设置项目工作流时的可视化弹窗
last_verified: v1.7.90
---

# 项目视图概览（4 种）

## 定义
DooTask 的项目详情页提供 4 种任务视图，用同一份任务数据按不同维度展现。视图切换不影响数据，只影响展示。当前视图持久化在 `cacheParameter.menuType`，每个项目独立记录。

## 4 种视图
1. **看板视图（column / kanban）**：默认。任务按「列」（column_id）水平分栏展示，每张任务卡纵向排列；最像 Trello
2. **列表视图（table）**：按「我的 / 协助 / 未完成 / 已完成」分段，每段是表格行，列含名称、列表、优先级、负责人、到期时间
3. **甘特图视图（gantt）**：横向时间轴展示任务的 start_at → end_at；用于查看排期与重叠
4. **工作流视图（flow）**：按 `flow_item_id` 分栏，列对应工作流步骤（如「待评审 / 评审中 / 已通过」），通过顶部「工作流」筛选切入

## 桌面端视图切换器
项目详情页右上角有 3 个图标按钮的切换条：列（看板）/ 表格（列表）/ 时间轴（甘特图）。

## 与"工作流分栏"的区别
- 看板视图分栏 = 项目列（column，永久存在的列）
- 工作流分栏 = 任务的工作流步骤（flow_item，可单独切换显示）

## 与"筛选"的关系
- 视图：决定数据怎么排版
- 筛选（顶部 Cascader）：决定显示哪些任务
- 两者正交：任意视图都能叠加筛选
