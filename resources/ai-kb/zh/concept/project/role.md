---
id: project.role.concept
title: 项目角色（拥有者 / 管理员 / 成员）
type: concept
feature: project
scope: end-user
locale: zh
aliases:
  - 项目角色
  - 项目拥有者
  - 项目负责人
  - 项目管理员
  - 项目普通成员
related_tools: [get_project]
related_pages: [project_settings, project_member]
prerequisites: []
negative:
  - 一个项目只能有 1 个拥有者（owner=1）
  - 项目管理员不能罢免其他管理员、不能转让拥有者
  - 项目角色与系统角色（站点管理员）独立，互不继承
last_verified: v1.7.90
---

# 项目角色（拥有者 / 管理员 / 成员）

## 定义
DooTask 项目角色用 `ProjectUser.owner` 字段 3 个值表达：
- `0` — **普通成员**（OWNER_MEMBER）
- `1` — **项目拥有者** / **项目负责人**（OWNER_PRIMARY），每项目唯一
- `2` — **项目管理员** / **项目副手**（OWNER_DEPUTY）

加上群聊里同步的 `WebSocketDialogUser.role` 字段，保持与 ProjectUser.owner 一致。

## 权限差异

| 行为 | 拥有者 1 | 管理员 2 | 成员 0 |
|---|---|---|---|
| 改项目设置（名称 / 自动归档 / AI 分析等） | ✓ | ✓ | ✗ |
| 加 / 删成员 | ✓ | ✓ | ✗ |
| 任命 / 罢免管理员 | ✓ | ✗ | ✗ |
| 转让项目（[[project.transfer.howto]]） | ✓ | ✗ | ✗ |
| 归档 / 删除项目 | ✓ | ✗ | ✗ |
| 改工作流 / 列 / 标签 | ✓ | ✓ | ✗ |
| 改自己创建的任务 | ✓ | ✓ | ✓ |
| 改别人创建的任务 | ✓ | ✓ | 仅当是负责人 / 协作者 / 有 TASK_UPDATE 权限 |
| 任务级权限（TASK_ADD / UPDATE 等） | 详见 [[project.permission.concept]] |

## 转换规则
- 拥有者通过 `transfer` 接口把角色让给另一个成员，自己变回成员（owner=0）
- 个人项目（personal=1）的拥有者转让时，原拥有者名字会自动加到项目前缀

## 不支持
- 不支持多人共拥有者（最多 1 个 owner=1）
- 用户离开项目后他名下任务不会保留原负责人：任务 owner 会被转给操作人或拥有者
- 项目角色不能继承自部门负责人 / 站点管理员
