---
id: project.column.howto.add
title: 给项目添加列（看板分栏）
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 加一列
  - 添加看板列
  - 项目加列
  - 加一个分栏
  - 看板新增列
related_tools: [get_project]
related_pages: [project_detail, project_kanban]
prerequisites:
  - 有 TASK_LIST_ADD 权限（默认拥有者 / 管理员）
negative:
  - 单项目最多 50 列
  - 初次创建项目时模板默认 ≤ 30 列
  - 列名重复不被自动拦截（允许同名）
last_verified: v1.7.90
---

# 给项目添加列（看板分栏）

## 是什么
DooTask 的「列」（ProjectColumn）是看板视图的物理分栏，按 `column_id` 把任务分组展示。每项目最多 50 列。常见列名：待办、进行中、已完成、待审核、待发布。

## 入口
- 桌面端：项目看板视图最右侧 「+ 添加列」按钮
- 桌面端：项目设置 → 「列管理」 → 「+ 新列」

## 操作步骤
1. 点 + 按钮
2. 输入列名（≤30 字符）
3. 选颜色（可选）
4. 保存，新列追加在最右侧（sort 排到末尾）
5. 服务端写入 ProjectColumn 并 WebSocket 推送给项目成员

## 字段
- `name`：列名
- `color`：色块（可选，显示在列头标题前）
- `sort`：排序值（拖动调整）

## 与「工作流节点」的关系
- 列只是物理分栏；状态语义靠工作流（[[project.flow.howto.create]]）表达
- 不绑定工作流时：每列独立，拖任务只改 column_id
- 绑定 columnid 到工作流节点时：拖列联动改 flow_item_id

## 操作权限
- 默认仅项目拥有者 / 管理员可加列
- 普通成员可通过 ProjectPermission（[[project.permission.concept]]）的 `TASK_LIST_ADD` 权限点放开

## 不支持
- 单项目不能超过 50 列（最多 50 列）
- 不能跨项目共享列模板
- 列没有"折叠"功能（视觉收起需要前端临时操作）
