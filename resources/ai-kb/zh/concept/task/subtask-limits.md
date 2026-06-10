---
id: task.subtask.limits.concept
title: 子任务的限制（嵌套 / 数量 / 跨项目）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 子任务限制
  - 子任务能嵌套吗
  - 子任务多少个
  - 子任务能跨项目吗
  - 子任务不支持什么
related_tools: [create_sub_task, get_task]
related_pages: [task_detail]
prerequisites: []
negative:
  - 子任务最多 1 层（不能在子任务下再建子任务）
  - 单个父任务最多 50 个子任务
  - 子任务不能跨项目（强制与父任务同 project_id）
last_verified: v1.7.90
---

# 子任务的限制（嵌套 / 数量 / 跨项目）

## 定义
DooTask 子任务（subtask）通过 `parent_id` 字段实现，是任务的一种特殊形态。设计上为了保持父子模型简单，加了若干硬限制；这一篇专门解释这些限制及其原因，避免 AI 助手在用户问"为什么不行"时编造答案。

## 嵌套层级：最多 1 层
- 子任务的 parent_id 必须指向**主任务**（parent_id=0）
- 子任务自身的 parent_id 不能再被作为另一个任务的 parent
- 想表达更深层级用 [[task.related.concept]]（关联），或按业务拆多个独立任务

## 数量上限：50 / 父
- 单个父任务最多挂 50 个子任务
- 达到上限后服务端会拒绝继续新增
- 想"分批管理"可以归档（[[task.archive.howto]]）已完成的子任务腾位置

## 跨项目：禁止
- 子任务自动继承 `project_id`、`column_id`、可见性、对话归属
- 不能把子任务移到别的项目
- 跨项目协作请用关联任务而不是子任务

## 不支持的子任务功能
- 子任务无独立 `dialog_id`（任务聊天室）
- 子任务无独立 `file_num`（不能直接上传附件，需挂在父任务）
- 子任务不支持 `loop` 重复
- 子任务不能单独配置 visibility，强制继承父任务

## 父子联动
- 父任务删除 → 子任务级联软删
- 父任务归档 → 子任务自动归档（archived_follow=1）
- 父任务跨项目移动 → 子任务一起走
- 父任务完成 → **不会**自动完成子任务，需要手动逐个完成
