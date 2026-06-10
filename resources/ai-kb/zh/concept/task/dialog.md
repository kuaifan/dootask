---
id: task.dialog.concept
title: 任务聊天室（dialog_id）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务对话
  - 任务聊天
  - 任务讨论区
  - 任务群聊
  - 任务的消息
related_tools: [send_message, get_message_list]
related_pages: [task_detail, messenger]
prerequisites: []
negative:
  - 子任务不能独立开聊天室，强制使用父任务的 dialog_id
  - 任务删除不会自动删除聊天室
  - 复制任务会重置 dialog_id，新任务有自己的全新聊天室
last_verified: v1.7.90
---

# 任务聊天室（dialog_id）

## 定义
DooTask 每个**主任务**可以拥有一个独立聊天室（`dialog_id` 字段，对应 `WebSocketDialog` 表），用于负责人、协作者、可见用户在任务上下文中讨论。第一次打开任务对话区时按需创建，未启用前 dialog_id 为 0。

## 入口
- 桌面端：任务详情页 → 顶部「💬 任务对话」按钮 → 唤起侧边对话面板
- 桌面端：左侧 [[messenger.group.howto.create]] 创建的群外，任务对话作为独立类型显示在「最近消息」列表
- 移动端：任务详情页底部「💬」入口

## 谁能参与
- 默认：负责人、协作者、可见用户均可读写
- 不在 [[task.field.visibility.concept]] 命中的用户看不到聊天室
- 项目管理员若不在任务可见范围内，也看不到对话

## 与「关联」的关系
- 在任务聊天室里输入 `@#` 关联其他任务，会产生 [[task.related.concept]] 记录
- 在群聊里 `@#` 关联到这个任务，产生反向关联（任务详情页能看到「相关消息」）

## 删除联动
- 任务被 [[task.delete-restore.howto]] 删除后，dialog_id 保留不清理
- 恢复任务时，聊天室可继续使用，历史消息完整保留

## 不支持
- 子任务（parent_id > 0）不能开独立聊天室
- 任务对话不能与外部用户共享（必须是任务可见用户）
- 任务对话不能匿名 / 不能开私聊模式（始终多人）
