---
id: role-permission.admin.concept
title: 系统管理员（admin identity）
type: concept
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 系统管理员
  - admin
  - 管理员
  - 谁是管理员
  - 怎么算管理员
  - userIsAdmin
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 系统管理员不能修改超级管理员的资料和设置
  - 系统管理员不会自动成为项目负责人或任务负责人，仍按项目/任务级权限判断
  - identity 是数组，可能同时含 admin、temp、disable 等多个标记
last_verified: v1.7.90
---

# 系统管理员（admin identity）

## 定义
系统管理员是 `users.identity` 字段数组中包含字符串 `'admin'` 的用户。后端通过 `User::auth('admin')` 和 `$user->isAdmin()` 判断，前端通过 `store.state.userIsAdmin` 判断。允许同时存在多个系统管理员。

## 关键属性
- **identity 字段是字符串数组**：可能值包括 `admin` / `temp`（临时账号） / `disable`（已离职） / `ldap`（LDAP 用户）等
- **可多人持有**：任意系统管理员（含超管）都可授予或取消他人的 admin 身份
- **由超管或现任 admin 授予**：通过 `POST api/users/operation` 的 `setadmin` / `clearadmin` 类型设置
- **不与项目/任务权限挂钩**：管理员身份只对系统级功能生效；进项目/任务仍按项目级权限判断

## 主要权限
- 团队管理：创建、编辑、删除、禁用、设为临时、设为离职会员
- 系统设置、LDAP、邮件、推送、举报、合规设置
- 数据导出（6 种）、应用市场管理、部门管理（增删改、任命部门负责人/管理员）
- 创建用户（`POST api/users/createuser`）、批量导入

## 不包括
- 不能修改超级管理员的资料（被 `checkSystem(1)` 拦截）
- 不能直接看任意项目内的任务（仍需是项目成员或被任务可见用户名单包含）
- 不能解散别人是群主的群（除非是超管兜底）

## 怎么变成管理员
要别人授权，见 [[role-permission.grant-admin.howto]]。
