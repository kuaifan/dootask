---
id: project.flow.concept.default
title: 默认工作流（待处理 / 进行中 / 待测试 / 已完成 / 已取消）
type: concept
feature: project
scope: end-user
locale: zh
aliases:
  - 默认工作流
  - 5 个流程节点
  - 待处理 进行中
  - 已完成 已取消
  - DooTask 默认流程
related_tools: [get_project]
related_pages: [project_settings, project_flow]
prerequisites: []
negative:
  - 默认 5 节点可改名 / 改色 / 加节点 / 删节点
  - 已取消（status=end）也会触发 complete_at 自动写入（与「已完成」同等待遇）
  - 重命名节点不会回填已存在任务的 flow_item_name 快照
last_verified: v1.7.90
---

# 默认工作流（待处理 / 进行中 / 待测试 / 已完成 / 已取消）

## 定义
DooTask 创建项目时若勾选「启用工作流」，会自动套用一套预置的 5 节点流程，覆盖最常见的「待办 → 工作 → 检查 → 收尾」工序：

| 节点 | 状态 | 默认颜色 | 默认 turns（可流转到） |
|---|---|---|---|
| 待处理 | start | 灰 | 进行中 |
| 进行中 | progress | 蓝 | 待测试、已完成 |
| 待测试 | test | 黄 | 进行中、已完成 |
| 已完成 | end | 绿 | （终点） |
| 已取消 | end | 红 | （终点） |

## 特点
- 「已完成」和「已取消」都是 `status=end`，拖任务到这两个节点都会触发 `complete_at` 自动写入
- 「已取消」用于"做了但不再继续"的语义，与「已完成」字段值无差异（仅节点名 / 色不同）
- 「待测试」适合产品 / 开发流程
- 节点之间的流转规则按上表，可在项目设置改

## 与「列」（column）的关系
- 默认工作流的 5 节点不强制绑定列；你可手动给每个节点配 columnid，让看板列与工作流状态联动
- 不绑定列时：看板视图按列分栏，工作流视图按 flow_item 分栏

## 自定义修改
- 进 [[project.flow.howto.create]] 编辑节点
- 加节点、改名、改色、改 turns、绑定列均支持
- 拖动调整节点顺序（sort）

## 不支持
- 默认 5 节点不能"恢复初始"
- 修改默认节点后不会同步给其他项目（每项目独立）
- 重命名节点不影响已存在任务的 flow_item_name 快照（这是设计：避免历史任务突然显示新名）
