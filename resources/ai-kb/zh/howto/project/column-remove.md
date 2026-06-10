---
id: project.column.howto.remove
title: 删除项目列（级联删除任务）
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 删除列
  - 删一列
  - 删看板列
  - 列里任务怎么办
  - 删除列任务会丢吗
related_tools: [get_project]
related_pages: [project_kanban, project_settings]
prerequisites:
  - 有 TASK_LIST_REMOVE 权限
negative:
  - 删列**会级联软删该列下所有任务**（含子任务）
  - 删列前不会自动迁移任务到其他列，需手动 [[task.move.howto.column]]
  - 删除后任务可在回收站恢复，列本身也可恢复
last_verified: v1.7.90
---

# 删除项目列（级联删除任务）

## 重要提醒
**删列会同时软删该列下所有任务。** 这是 DooTask 的设计行为（不是 BUG），原因：列是任务的物理归属，孤儿任务没有合理去处。

## 入口
- 桌面端：看板视图列头右键 → 「删除列」
- 桌面端：项目设置 → 「列管理」 → 单列「删除」

## 操作步骤
1. 确认弹窗会显示「将一并删除 X 个任务」
2. 二次确认
3. 服务端：
   - 软删 ProjectColumn（deleted_at）
   - 级联软删所有 column_id 命中此列的 ProjectTask
   - 级联软删所有这些任务的子任务、附件、聊天室
4. ProjectLog 记一条「X 删了列 Y 和 N 个任务」

## 避免误删的建议
- **删列前先迁任务**：拖任务到别的列（[[task.move.howto.column]]）或归档（[[task.archive.howto]]）
- 想把列彻底废弃但保留任务记录：建一个「已归档」列，把任务全拖过去

## 恢复
- 回收站找到项目 → 「恢复任务」可单独恢复任务，但 column_id 仍指向已删的列
- 整列恢复：管理后台数据恢复，会一并恢复列 + 任务

## 不支持
- 不能"软删列保留任务"（只有迁任务 + 删列两步法）
- 列内任务过多时（>100）没有分批删除：服务端依然会一次性级联软删，操作可能慢几秒
- 删列后绑定的工作流节点不会自动解绑 columnid，需手动改
