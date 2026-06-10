---
id: org-department.deputy.concept
title: 部门负责人与部门管理员
type: concept
feature: org-department
scope: end-user
locale: zh
aliases:
  - 部门负责人
  - 部门管理员
  - deputy
  - 部门负责人是谁
  - 部门负责人能干嘛
  - 主管
  - 部门主管
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 一个部门只能有 1 个部门负责人（owner_userid），不能空
  - 部门负责人不能同时是自己部门的部门管理员（接口会拦截）
  - 部门管理员不能罢免自己（只有系统管理员能罢免）
  - 部门负责人/管理员不等于系统管理员（userIsAdmin），不能进系统设置
  - 罢免部门管理员会同步把他从该部门移除并退出部门群
last_verified: v1.7.90
---

# 部门负责人与部门管理员

## 定义
部门里有两种"领导"角色，权限近似但有差别：

- **部门负责人（owner）**：每个部门唯一，存在部门表的 `owner_userid` 字段；自动成为部门群群主（role=1）
- **部门管理员（deputy）**：每个部门可多人，存在 `user_department_owners` 表；在部门群里 role=2

两者在前端常合称"部门负责人"，在 RAG 检索中"deputy = 部门管理员"。

## 关键属性
- 任命/罢免接口分别为 `department__adddeputy` / `department__deldeputy`，仅系统管理员可调用
- 任命部门管理员会自动把该用户加入部门（写入 `users.department`）并加入部门群（important=true 不可被普通群操作打散）
- 部门负责人是 owner_userid 字段的唯一持有人；变更走 `department__add`（传 id + 新 owner_userid 即编辑）

## 能做什么
开启系统设置「部门负责人视角」后：
- 只读查看本部门及所有下级部门成员参与的**项目**与**任务**
- 不能修改项目设置、任务、成员
- 通过右上角头像菜单切换到「负责人视角」选择部门，见 [[org-department.deputy-view.howto]]
- 「项目级开关」：每个项目可单独关闭"部门负责人视角可见"（`projects.department_owner_view`）

## 与其他概念的关系
- 与系统管理员：互不重叠；系统管理员有进入团队管理的权限，部门负责人仅有查看下属数据的只读视角
- 与项目负责人：不冲突；同一用户可在 A 部门做负责人 + 在 B 项目做负责人
