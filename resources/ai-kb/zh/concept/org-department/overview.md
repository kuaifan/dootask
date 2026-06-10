---
id: org-department.concept
title: 部门是什么
type: concept
feature: org-department
scope: end-user
locale: zh
aliases:
  - 部门
  - 组织架构
  - 公司部门
  - 部门是什么
  - 部门结构
  - 部门树
  - 部门和子部门
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 部门层级最多 3 级（顶级 + 2 层子部门）
  - 每个部门最多 20 个直属子部门
  - 全系统最多 200 个部门
  - 同一用户最多负责（owner_userid）10 个部门
  - 部门不能跨实例迁移
last_verified: v1.7.90
---

# 部门是什么

## 定义
部门（department）是 DooTask 用于描述公司组织结构的树形单位。每个部门有名称、上级部门（parent_id）、唯一的部门负责人（owner_userid）和可选的部门管理员（deputy）。部门之间通过 parent_id 形成树形结构，最多 3 级。

## 关键属性
- **name**：部门名称，2-20 字，不能含特殊符号或字符串 `(M)`
- **parent_id**：上级部门 ID；为 0 表示顶级部门
- **owner_userid**：部门负责人 userid，必填且唯一
- **deputy_userids**：部门管理员列表，存于 `user_department_owners` 表，可多人
- **dialog_id**：每个部门自动绑定一个部门群（group_type=`department`），成员关系与部门成员对齐

## 与其他概念的关系
- **用户与部门**：用户的 `department` 字段保存其所在部门 ID 列表，用逗号包围（如 `,1,3,`）；同一用户可属于多个部门
- **部门与群聊**：每个部门有一个部门群，部门负责人是群主、部门管理员在群里 role=2
- **负责人视角**：开启系统设置「部门负责人视角」后，负责人/管理员可只读查看本部门及所有下级部门成员参与的项目和任务，见 [[org-department.deputy.concept]]

## 入口
管理入口仅限系统管理员：[[org-department.entry.menu-map]]
