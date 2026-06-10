---
id: view.flow.howto
title: 按工作流状态分栏查看任务
type: howto
feature: view
scope: end-user
locale: zh
aliases:
  - 工作流视图
  - 按状态看任务
  - 评审中的任务
  - 按 flow 分栏
  - 工作流筛选
  - 任务流程视图
related_tools: [list_tasks, get_project]
related_pages: [project_detail]
prerequisites:
  - 项目已配置工作流（至少一条 flow）
negative:
  - 工作流视图不是独立视图按钮，是通过顶部「工作流」Cascader 在看板视图上叠加的筛选
  - 一个项目可以有多条工作流，但筛选 Cascader 一次只能选一个工作流的某个状态
  - 项目未配置工作流时筛选下拉只显示「全部」与「未计划」
  - 工作流状态（flow_item）不等于看板列（column），两者独立
last_verified: v1.7.90
---

# 按工作流状态分栏查看任务

## 入口
- 桌面端 Web：项目详情页顶部「工作流」Cascader（默认显示「全部 (n)」）
- 点开下拉 → 选某个工作流的某个状态（如「评审 → 评审中」）

## 操作步骤
1. 确保项目已配置工作流（项目设置 → 工作流设置；详见 [[project.flow.howto.create]]）
2. 在顶部「工作流」Cascader 中选择目标状态
3. 当前视图（看板 / 列表 / 甘特图）立即过滤，只展示 flow_item_id 命中的任务
4. 选择「全部」回到无筛选状态

## 可选筛选维度
工作流 Cascader 下拉里除了工作流状态，还混合了：

- **全部 (n)**：取消筛选，显示所有任务
- **未计划 (n)**：start_at 为空且未完成的任务
- **某工作流的某状态**：按 `flow_item_id` 过滤
- **某标签**：按任务标签筛选（`tag:xxx`）
- **某负责人**：按 owner 过滤（`user:userid`）

## 状态着色
工作流状态有三档着色（status 字段）：

- `start`（起步色）：常用作「待办」「待评审」
- 中间状态：自定义颜色
- `end`（结束色）：常用作「已完成」「已通过」；选这种状态时若未开启「显示已完成」会自动打开

## 不支持
- 不支持同时选择多个状态进行联合筛选
- 不支持跨工作流的状态合并
- 不支持把工作流分栏当成视图独立保存（每次进入仍是 column / table / gantt 视图）
