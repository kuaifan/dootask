---
id: task.move.howto.column
title: 拖拽任务换列 / 修改工作流状态
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 拖动任务
  - 换列
  - 拖到已完成
  - 看板拖任务
  - 把任务改状态
related_tools: [update_task]
related_pages: [project_detail, task_kanban]
prerequisites:
  - 看板视图或工作流视图下
  - 有 TASK_UPDATE 权限
negative:
  - 拖动只改 column_id（所属列），不直接改 complete_at（完成时间）
  - 跨项目拖动不支持，跨项目要用 [[task.move.howto.cross-project]]
  - 移动端不支持拖动，需进详情改字段
last_verified: v1.7.90
---

# 拖拽任务换列 / 修改工作流状态

## 是什么
DooTask 看板（Kanban）视图按 `column_id`（项目列）分栏；工作流视图按 `flow_item_id`（自定义状态）分栏。拖动任务卡片会触发 `task__update` 改对应字段并同时更新 `sort` 排序值。

## 入口
- 桌面端：项目详情页右上角视图切换 → 「看板」或「工作流」
- 进入后所有任务按列显示

## 操作步骤
1. 鼠标按住任意任务卡片
2. 拖到目标列，松开
3. 服务端写入新的 `column_id`（或 `flow_item_id`）与 `sort`
4. WebSocket 推送给项目所有在线成员，他们的看板同步刷新

## 自动联动
- 项目工作流配置中，每个 `ProjectFlowItem` 可绑定一个 `columnid` 与 `status=end` 标记
- 拖到 `status=end` 的工作流状态会自动把 `complete_at` 写入当前时间（标记完成）
- 拖到其他状态则只改 `flow_item_id`，不改 `complete_at`

## 不支持
- 拖动不能批量
- 跨项目不能直接拖
- 工作流视图下，拖到无权限的负责人状态会被服务端拒绝
