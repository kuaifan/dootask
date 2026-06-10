---
id: task.archive.howto
title: 归档任务 / 取消归档
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 归档任务
  - 任务收起
  - 隐藏任务但不删除
  - 取消归档
  - 把任务藏起来
related_tools: [update_task]
related_pages: [task_detail, project_detail]
prerequisites:
  - 任务可见
  - 是负责人或有 TASK_UPDATE 权限
negative:
  - 归档不等于完成，归档任务可以是未完成状态
  - 归档不等于删除，归档任务保留全部字段、关联、附件
  - 归档任务不会出现在默认列表，但仍能搜索到
last_verified: v1.7.90
---

# 归档任务 / 取消归档

## 是什么
归档（archived_at）是把任务从默认视图中"折叠收起"，但保留所有数据的状态。归档任务不会污染看板与列表，但能在「已归档」筛选下查回。常见于"做完很久但暂时不想删"、"过期但要留作记录"的任务。

## 字段
- `archived_at`：归档时间（非空表示已归档）
- `archived_userid`：归档操作人
- `archived_follow`：跟随父任务归档的子任务标记

## 入口
- 桌面端：任务详情页 → 右上角「⋯」 → 「归档」
- 桌面端：项目设置 → 「批量归档已完成任务」（一次性把所有完成任务归档）
- 列表视图右键任务行 → 「归档」

## 操作步骤
1. 单任务归档：详情页选「归档」，确认后 `archived_at` 写入当前时间
2. 子任务跟随：归档父任务时，所有子任务自动跟着归档（archived_follow=1）
3. 取消归档：进入「已归档」筛选 → 选目标任务 → 「⋯」 → 「取消归档」

## 与「完成」「删除」的区别
| 状态 | 字段 | 默认视图是否显示 | 可恢复 |
|---|---|---|---|
| 完成 | complete_at | 显示（划线） | 取消完成 |
| 归档 | archived_at | 不显示 | 取消归档 |
| 删除 | deleted_at | 不显示 | 30 天内 [[task.delete-restore.howto]] |

## 不支持
- 归档不会发任务变更通知
- 归档任务不能被拖动 / 直接编辑（须先取消归档）
