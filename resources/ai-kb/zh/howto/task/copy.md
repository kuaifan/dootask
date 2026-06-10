---
id: task.copy.howto
title: 复制任务（含子任务、附件、内容）
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 复制任务
  - 克隆任务
  - 拷贝任务到别的项目
  - 任务原样新建一份
  - 复制带子任务
related_tools: [create_task, get_task]
related_pages: [task_detail, project_detail]
prerequisites:
  - 对源任务有可见权限，对目标项目有 TASK_ADD 权限
negative:
  - 复制不会带走 dialog_id（任务聊天室）和完成时间，目标任务是全新一份
  - 复制不会保留归档状态、删除时间
  - 公开访问链接、订阅记录不复制
last_verified: v1.7.90
---

# 复制任务（含子任务、附件、内容）

## 是什么
DooTask 提供「复制任务」功能（API `task__copy`），可在同项目或跨项目快速创建相同结构的任务。复制会带走任务名、描述、子任务、附件、负责人、协作者、可见性、优先级、计划时间、标签等核心字段。

## 入口
- 桌面端：任务详情页 → 右上角「⋯」菜单 → 「复制任务」
- 桌面端：项目看板视图，任务卡片右键 → 「复制」
- 列表视图同样支持任务行的「⋯」操作

## 操作步骤
1. 选择「复制任务」，弹出对话框
2. 选目标项目、目标列、目标流程状态（可选）
3. 可选调整负责人、协作者、计划时间
4. 确认后服务端通过 `copyTask()` + `copySubTasks()` 递归生成

## 不带过去的字段
- `dialog_id`（新任务会重新建聊天室，详见 [[task.related.concept]]）
- `complete_at`、`archived_at`、`deleted_at`
- 评论历史、动态日志

## 不支持
- 不能批量复制多任务
- 跨项目复制时不保留「项目级标签」归属：标签会变成新项目下的临时标签，颜色保留
