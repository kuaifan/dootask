---
id: task.related.concept
title: 任务关联（消息提及 / 任务 ↔ 任务）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务关联
  - 关联任务
  - 任务引用
  - 任务 mention
  - 怎么把任务挂另一条任务
related_tools: [get_task, send_message]
related_pages: [task_detail, messenger]
prerequisites: []
negative:
  - 关联是平级的，不会形成父子层级（要拆层级用 [[task.subtask.concept]]）
  - 删除任意一方任务不会自动删除关联记录
  - 关联不带过去字段数据（不是软链接，只是关系记录）
last_verified: v1.7.90
---

# 任务关联（消息提及 / 任务 ↔ 任务）

## 定义
DooTask 用 `ProjectTaskRelation` 表记录两类关联：
- **消息 ↔ 任务**：在对话里 `@#` 任务卡片，会产生 `direction = mention`（消息提及任务）或 `mentioned_by`（任务被消息提及）的双向记录，绑定 `dialog_id` + `msg_id`
- **任务 ↔ 任务**：任务详情页「关联任务」区也走同一张表，但 dialog_id / msg_id 为空

每条记录保存 `task_id`、`related_task_id`、`direction`、`userid`。

## 与「子任务」的区别

| 维度 | 子任务（parent_id） | 关联任务（ProjectTaskRelation） |
|---|---|---|
| 层级 | 父子 | 平级 |
| 跨项目 | ✗ | ✓ |
| 删除联动 | 父删则子级联软删 | 两边互不影响 |
| 完成度统计 | 父任务显示「X/Y」 | 不统计 |
| 数量限制 | 父最多 50 子 | 无硬上限 |

## 入口
- 桌面端：任务详情页 → 「关联任务」字段 → 「+」搜索其他任务
- 在对话里输入 `@#` 选任务：详见 [[task.create.howto.via-mention]]

## 在哪里能看到
- 任务详情页底部「相关消息」区（来自 dialog mention）
- 任务详情页「关联任务」区（来自任务详情页直接关联）
- 关联任务双向显示：A 关联 B 后，B 那边也看到 A

## 不支持
- 关联不产生权限继承
- 关联不会发通知给被关联任务的负责人
- 不能给关联打标签 / 写说明（单纯的关系记录）
