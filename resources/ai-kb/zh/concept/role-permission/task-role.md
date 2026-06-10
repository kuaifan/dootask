---
id: role-permission.task-role.concept
title: 任务角色（负责人 / 协助人 / 可见用户）
type: concept
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 任务角色
  - 任务负责人
  - 任务协助人
  - 任务可见用户
  - assist
  - visibility
  - 谁能改这条任务
  - 任务为什么看不到
related_tools: []
related_pages: [task_detail]
prerequisites: []
negative:
  - 任务负责人可以是多人（不是只能一个）
  - 协助人没有改状态 / 删任务的权限，只能改内容和时间
  - 设置了可见用户的任务，非名单内的项目成员看不到，连项目负责人不在名单也看不到
  - 子任务的可见用户继承父任务，不能单独配置
last_verified: v1.7.90
---

# 任务角色（负责人 / 协助人 / 可见用户）

## 定义
单个任务上的角色记录在两张表：

- **`project_task_users`**：记录任务负责人和协助人，`owner` 字段区分
  - `owner=1`：任务负责人（可多人）
  - `owner=0`：任务协助人（assist，可多人）
- **`project_task_visibility_users`**：记录任务可见用户名单，控制谁能看到这条任务

## 默认权限（项目权限策略可覆盖）

| 角色 | 改内容 / 改时间 | 改状态 / 完成 | 删除 / 归档 / 移动 |
|---|---|---|---|
| 项目负责人 | 可 | 可 | 可 |
| 任务负责人 | 可 | 可 | 可 |
| 任务协助人 | 可 | 不可 | 不可 |
| 其他项目成员 | 不可 | 不可 | 不可 |

## 可见用户（visibility）规则
- **未设置时**：所有项目成员都可见
- **设置后**：只有名单内的用户可见，名单外（包括项目负责人）都看不到
- 设了可见用户的任务才在搜索 / 列表 / 看板里对名单内用户出现
- 子任务的可见性继承父任务（不能单独设置）

## 与项目角色的关系
任务角色不依赖项目角色：
- 项目成员可以被任命为任务负责人，从而具备改状态 / 删任务的权限
- 项目负责人若不在任务可见用户名单内，也看不到这条任务

## 系统级身份的影响
系统管理员（admin identity）不会自动获得任意任务的权限。详见 [[role-permission.admin.concept]] 和 [[role-permission.project-role.concept]]。
