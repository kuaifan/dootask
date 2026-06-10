---
id: project.permission-to-create.faq
title: 为什么不能创建项目（创建权限受限）
type: faq
feature: project
scope: end-user
locale: zh
aliases:
  - 不能建项目
  - 创建项目按钮没了
  - 创建项目权限
  - 谁能创建项目
  - 项目创建权限受限
related_tools: [create_project]
related_pages: [project_list]
prerequisites: []
negative:
  - 创建权限是站点级开关，不能按部门 / 角色细粒度配
  - 管理员（userIsAdmin）始终可创建团队项目
  - 个人项目（personal=1）不受创建权限限制，所有人都能建
last_verified: v1.7.90
---

# 为什么不能创建项目（创建权限受限）

## 问题
点击「+ 新建项目」时按钮置灰，或者按下后报「无权限」错误。

## 原因
系统设置中 `project_add_permission` 控制谁能创建**团队项目**，可能值：
- `all` — 所有人都能创建（默认）
- `departmentOwner` — 仅部门负责人能创建
- `appoint` — 仅站点指定的白名单用户能创建

当前用户不在允许范围内就拦掉。

## 谁始终可以创建
- 站点管理员（`userIsAdmin`）
- 任何用户创建**个人项目**（[[project.personal.concept]]），不受限制

## 解决
1. **临时**：先建一个个人项目，把任务先放进来
2. **找管理员调整**：让管理员把 `project_add_permission` 改成 `all`，或把你加入 appoint 白名单 / 设为部门负责人
3. **走部门负责人路径**：如果设置是 `departmentOwner`，让管理员把你设为部门负责人

## 在哪改设置
- 桌面端：管理后台 → 系统设置 → 项目设置 → 「项目创建权限」

## 不支持
- 不能为某个具体用户绕过设置直接放行（除非加 appoint）
- 不能按项目分组单独配创建权限
