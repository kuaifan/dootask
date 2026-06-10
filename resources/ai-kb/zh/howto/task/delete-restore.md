---
id: task.delete-restore.howto
title: 删除任务与回收站恢复
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 删除任务
  - 恢复任务
  - 回收站
  - 任务删错了怎么办
  - 找回任务
related_tools: [delete_task]
related_pages: [task_detail, recycle_bin]
prerequisites:
  - 是任务负责人，或有项目 TASK_REMOVE 权限
negative:
  - 删除后默认仅在回收站近期记录中保留，超过保留窗口会被定时清理
  - 父任务删除会级联软删所有子任务
  - 已删除任务不能编辑、不能完成、不能加子任务，必须先恢复
last_verified: v1.7.90
---

# 删除任务与回收站恢复

## 是什么
DooTask 任务使用软删除（`deleted_at` + `deleted_userid`），删除并非立即清理数据。回收站（Deleted 表）记录删除历史，可在一定时间内恢复。

## 入口
- 桌面端：任务详情页 → 「⋯」 → 「删除」
- 桌面端列表视图：右键任务行 → 「删除」
- 恢复入口：项目设置 → 「回收站」或全局「我删除的任务」

## 删除操作步骤
1. 选「删除」并在弹窗确认
2. 服务端 `deleteTask()` 写入 `deleted_at`，子任务被级联软删
3. 任务从所有视图消失，已归档的也一并隐藏

## 恢复操作步骤
1. 进入回收站，按任务名或项目搜索
2. 点击「恢复」，调用 `restoreTask()` 把 `deleted_at` 置空
3. 任务回到原项目原列，子任务一并恢复
4. 回收站默认展示最近 50 条；带筛选时最多 500 条

## 不支持
- 任务关联的对话（dialog_id）不在删除时清理
- 已超出回收站保留窗口的任务无法恢复
- 普通用户只能恢复自己删除的任务，看不到他人删除的记录
