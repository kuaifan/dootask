---
id: role-permission.concept
title: DooTask 权限体系总览
type: concept
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 权限体系
  - 权限有几种
  - 角色都有什么
  - DooTask 权限怎么分
  - 权限级别
  - 谁能做什么
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 没有「全局解锁所有权限」的开关，权限按场景细分
  - 不支持自定义新角色（角色集合是内置的）
  - 角色不直接绑权限列表，而是按场景判断
last_verified: v1.7.90
---

# DooTask 权限体系总览

## 定义
DooTask 把权限拆成四个相对独立的层级，按操作场景分别判断。每次校验都看「当前用户在当前对象上的角色」，没有跨层级的全局通行证。

## 四个层级
- **系统级**：超级管理员（id=1 的第一个注册用户）+ 系统管理员（identity 含 `admin`）。控制团队管理、LDAP、License、系统设置等。详见 [[role-permission.super-admin.concept]] 和 [[role-permission.admin.concept]]
- **项目级**：项目负责人（`owner=1`） / 项目管理员（`owner=2`） / 项目成员（`owner=0`）。控制项目内的列、工作流、成员邀请、任务权限策略。详见 [[role-permission.project-role.concept]]
- **任务级**：任务负责人（owner） / 任务协助人（assist） / 可见用户（visibility）。控制单个任务的修改权限与可见范围。详见 [[role-permission.task-role.concept]]
- **部门级**：部门负责人（`owner_userid`） / 部门管理员（`user_department_owners` 表）。看「负责人视角」、管理部门成员。详见 [[role-permission.department-role.concept]]

## 关键性质
- **不会自动继承**：系统管理员不自动成为某项目负责人；项目负责人不自动是任务负责人
- **按场景判断**：同一个用户在 A 项目是负责人、在 B 项目可能只是成员
- **可见性是独立维度**：任务可见用户（visibility）即使是项目成员也可能看不见某任务
- **超级管理员有兜底特权**：少数功能（如修改超管设置、强制删群）专属 id=1 用户

## 遇到权限不足
查 [[role-permission.permission-denied.faq]]。
