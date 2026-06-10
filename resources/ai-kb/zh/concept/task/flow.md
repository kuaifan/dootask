---
id: task.flow.concept
title: 任务工作流（自定义流程状态）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务工作流
  - 自定义状态
  - 流程
  - 待办进行中完成
  - 工作流视图
related_tools: [update_task, get_project]
related_pages: [task_detail, project_settings]
prerequisites:
  - 项目开启了工作流（在项目设置启用）
negative:
  - 工作流最多 10 个状态 / 流程
  - 工作流状态绑定 column_id 时，拖列联动改 flow_item_id；不绑定时各自独立
  - 工作流的 status=end 节点会自动标记 complete_at，无法关闭这一联动
last_verified: v1.7.90
---

# 任务工作流（自定义流程状态）

## 定义
DooTask 工作流由 `ProjectFlow`（一个项目可有多套）+ `ProjectFlowItem`（每个流程节点）实现。常见用法是把任务从「待办 → 设计 → 开发 → 测试 → 上线」自定义为多个流转节点，比"完成 / 未完成"二值粒度更细。

## 关键字段（ProjectFlowItem）
- `name`：节点名（如「待开发」）
- `status`：节点类型，`start` 起点 / `end` 终点 / 其他普通节点
- `turns`：可流转到的节点 id 列表（流转规则）
- `userids`：节点负责人（限制只有这些人可以操作进入此节点）
- `usertype`：流转模式（如限制只能由当前负责人流转）
- `color`：节点色块（看板视图色条）
- `columnid`：可绑定到具体项目列，拖列时联动改 flow_item_id

## 任务上的字段
任务存 `flow_item_id` + `flow_item_name`，前端用此显示当前所处节点。

## 与「列」（column_id）的关系
- 列只是看板的物理分栏，与状态不一定一一对应
- 启用工作流后，看板视图按 `column_id` 分组，工作流视图按 `flow_item_id` 分组
- 节点配 `columnid` 时，拖列与改状态联动；不配则独立

## status=end 的特殊行为
- 拖任务到 status=end 的节点 → `complete_at` 自动写入 → 任务被标记完成
- 拖回非 end 节点 → complete_at 不会自动清空（除非显式取消完成）

## 不支持
- 单流程不能超过 10 个节点（最多 10 节点）
- 不能跨项目共享工作流
- 工作流配置改名 / 改色不会回填历史任务的 flow_item_name 快照
