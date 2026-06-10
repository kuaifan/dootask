---
id: role-permission.department-role.concept
title: 部门角色（部门负责人 / 部门管理员）
type: concept
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 部门负责人
  - 部门管理员
  - 负责人视角
  - 部门里谁能管
  - owner_userid
  - 部门主管
  - 部门 deputy
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 一个部门只有一个部门负责人（owner_userid 字段，单值）
  - 部门管理员可以多人（user_department_owners 表）
  - 部门负责人不会自动获得部门成员名下任务的修改权限
  - 调任 / 离职部门，要由系统管理员通过部门操作完成，用户自己不能改
last_verified: v1.7.90
---

# 部门角色（部门负责人 / 部门管理员）

## 定义
部门成员关系记在 `user_departments`（部门本身） 和 `users.department`（用户所属部门列表）。部门角色分两种：

- **部门负责人**：每个部门唯一，存在 `user_departments.owner_userid` 字段
- **部门管理员**（deputy）：可多人，存在独立表 `user_department_owners`，记录 `department_id + userid`

## 关键属性
- **每用户最多加入 10 个部门**（后端硬限制）
- **每用户最多任 10 个部门的部门负责人**
- **部门负责人会同时是该部门聊天群（`dialog`）的群主**，更换部门负责人会同步更换群主
- **判定方法**（PHP）：
  - 是否部门负责人：`$dept->isPrimaryOwner($userid)`
  - 是否部门管理员：`$dept->isDeputyOwner($userid)`
  - 是否任一种角色：`$dept->isOwner($userid)`

## 「负责人视角」能看到什么
- 部门成员名单
- 团队管理界面会高亮自己负责的部门
- 部门 OKR：只有顶级部门第一级负责人才能添加部门 OKR（`department_owner` 字段）

## 不会自动带来什么
- **不自动看部门成员的任务**：部门负责人想看下属任务，仍需被加入对应项目或被加入任务可见用户
- **不是系统管理员**：部门负责人没有团队管理、LDAP 等系统级权限

## 谁能改部门角色
只有系统管理员（admin identity）能任命 / 罢免部门负责人和部门管理员。详见 [[role-permission.entry.menu-map]]。
