---
id: task.move.howto.cross-project
title: 任务跨项目移动
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 任务转项目
  - 把任务移到别的项目
  - 任务换项目
  - 跨项目移动
  - 项目之间挪任务
related_tools: [update_task, get_project]
related_pages: [task_detail]
prerequisites:
  - 源任务可见，且你有 TASK_UPDATE 权限
  - 目标项目你已加入，且有 TASK_ADD 权限
negative:
  - 跨项目移动会清空项目级标签（[[task.field.tag.concept]]），按目标项目重新打
  - 跨项目移动不会迁移任务聊天室（dialog_id 保留但不再属于目标项目对话列表）
  - 子任务跟随父任务一起移动，不能单独移动子任务
last_verified: v1.7.90
---

# 任务跨项目移动

## 是什么
DooTask 任务可通过 `task__move` 接口在不同项目间移动，主要用于「任务被错放在了错的项目」或「把临时任务划归正式项目」的场景。移动会同时更新 `project_id`、`column_id`、`sort`、`flow_item_id` 字段。

## 入口
- 桌面端：任务操作菜单（任务卡右键 / 任务详情「⋯」）→「移动」，弹出「移动任务」弹窗

## 操作步骤
1. 在「移动任务」弹窗顶部的级联选择器选目标项目和目标列表
2. 弹窗左侧显示移动前的状态 / 负责人 / 协助人，右侧可同时重新设置：
   - 「状态」：按目标项目的工作流状态选择
   - 「负责人」：可改选（最多 10 人）
   - 「协助人」：可改选
3. 点确定提交，服务端验证权限并写入

## 字段变化
- `project_id`：改为目标项目
- `column_id`、`sort`：按目标列重新计算
- `flow_item_id`、`flow_item_name`：按目标项目工作流匹配，找不到匹配则清空
- `p_level`、`p_name`、`p_color`（优先级）：保留
- 任务名、描述、附件等主要字段：保留

## 不支持
- 不能批量跨项目移动
- 项目级标签 [[task.field.tag.concept]] 不迁移
- 移动时不能改任务名 / 描述
