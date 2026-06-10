---
id: system-setting.todo-permission.howto
title: 待办设置权限
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 谁能设置待办
  - todo 权限
  - todoSetPermission
  - 待办权限
  - 关闭他人设待办
  - 别人能不能给我设 todo
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 这是消息→待办（聊天里把某条消息变成 todo）权限，与项目任务无关
  - 自己给自己设 / 取消 todo 不受开关限制
  - 关闭后任何人都不能"批量给一群人设 todo"，包括非自己的成员
last_verified: v1.7.90
---

# 待办设置权限

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「消息相关」→「待办设置权限」。

字段名：`todo_set_permission`，枚举 `open` / `close`，默认 `open`。

## 谁能操作

| 取值 | UI 文案 | 含义 |
|---|---|---|
| `open` | 允许 | 群里**所有成员**都能把某条消息设 / 取消为他人待办 |
| `close` | 禁止 | 只有以下角色能给**他人**设 / 取消待办：本人、系统管理员、群主或群管理员、项目负责人或项目管理员、任务负责人 |

无论开关如何，"给自己"设 / 取消 todo 永远允许。

## 影响范围
本开关只影响 **聊天里的消息待办**（即把一条聊天消息标记为某些用户的 todo 项），路径包括：
- 群聊 / 私聊里长按消息 →「设为待办」
- 群聊菜单 →「指派待办」

后端校验点：`WebSocketDialogMsg::setTodoRemind` 与 `DialogController::msg__todo_indicate` 两处都会读 `todo_set_permission`，关闭时校验 `checkTodoOwnerPermission`。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「消息相关」
2. 「待办设置权限」选「允许」或「禁止」
3. 「提交」保存，立即生效

## 不支持
- 不能按群 / 项目差异化（全局生效）
- 不能限制"自己给自己设 todo"
- 与「项目任务的负责人 / 协助人」权限是两套体系，本开关不影响项目任务的指派
