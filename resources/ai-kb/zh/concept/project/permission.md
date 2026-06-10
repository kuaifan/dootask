---
id: project.permission.concept
title: 项目任务权限点（ProjectPermission）
type: concept
feature: project
scope: admin
locale: zh
aliases:
  - 项目权限
  - 任务权限点
  - TASK_ADD 权限
  - 权限规则配置
  - 谁能改任务
related_tools: [get_project]
related_pages: [project_settings, project_permission]
prerequisites:
  - 是项目拥有者（owner=1）或管理员（owner=2）
negative:
  - 权限规则只控制任务操作（add/update/remove/move 等），不控制项目设置
  - 拥有者 / 管理员的权限不可被 ProjectPermission 限制（默认全开）
  - 权限规则只能在「项目级」配置，不能按列、按标签细分
last_verified: v1.7.90
---

# 项目任务权限点（ProjectPermission）

## 定义
DooTask 用 `ProjectPermission` 表细化「谁能做什么任务级操作」。每条规则按 4 类主体 + 11 个权限点矩阵展开。规则只针对 owner=0 的普通成员；拥有者 / 管理员默认全开。

## 4 类权限主体
- `project_leader` — 项目拥有者（隐式全开）
- `project_member` — 项目内任意普通成员
- `task_leader` — 该任务的负责人（owner=1 in ProjectTaskUser）
- `task_assist` — 该任务的协作者（owner=0 in ProjectTaskUser）

## 11 个权限点
| 点 | 含义 |
|---|---|
| TASK_LIST_ADD | 加列 |
| TASK_LIST_UPDATE | 改列名 / 颜色 |
| TASK_LIST_REMOVE | 删列 |
| TASK_LIST_SORT | 列排序 |
| TASK_ADD | 加任务（[[task.create.howto.quick]]） |
| TASK_UPDATE | 改任务字段 |
| TASK_TIME | 改计划时间 |
| TASK_STATUS | 改工作流 / 完成 |
| TASK_REMOVE | 删任务 |
| TASK_ARCHIVED | 归档任务 |
| TASK_MOVE | 跨列 / 跨项目移动 |

## 在哪配
- 桌面端：项目设置 → 「权限规则」面板
- 用矩阵勾选：每个权限点 × 每类主体

## 默认值
新建项目时：
- project_member：TASK_ADD / TASK_UPDATE / TASK_STATUS / TASK_TIME / TASK_ARCHIVED / TASK_MOVE / TASK_LIST_SORT 全开
- task_leader / task_assist：全开（任务自己人改自己的任务）
- TASK_LIST_ADD / TASK_LIST_UPDATE / TASK_LIST_REMOVE：仅拥有者 / 管理员

## 不支持
- 不能按列、按标签做细粒度限制
- 不能限制拥有者 / 管理员（始终全开）
- 不支持「按部门角色」自动套用权限
