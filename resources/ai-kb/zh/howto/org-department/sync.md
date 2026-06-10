---
id: org-department.sync.howto
title: 同步部门成员
type: howto
feature: org-department
scope: admin
locale: zh
aliases:
  - 同步部门
  - 同步部门成员
  - 部门成员同步
  - 把子部门成员同步到上级
  - 父部门看到子部门成员
  - 部门成员归集
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
  - 目标部门下有子部门
negative:
  - 同步是"子部门成员合并到当前部门"，**不是**反向（不会把当前部门成员推到子部门）
  - 同步是单向、追加：只新增 `users.department` 里没有该部门 ID 的用户，已在的不重复加
  - 已 disable_at 的用户被跳过，不会同步进部门或部门群
  - 没有子部门时返回成功但不做事，提示「同步完成，子部门中没有成员需要同步」
  - 同步与 LDAP 拉取无关：DooTask 内部不直接通过此接口对接 LDAP
last_verified: v1.7.90
---

# 同步部门成员

## 是什么
"同步部门成员"是把所有**子部门**（递归）的成员合并到**当前部门**的一键操作。常用于：父部门负责人希望看到/管理下属部门的全部成员，但不想手动逐个调整 `users.department`。

## 入口
- 桌面端：管理后台 → 团队管理 → 某部门行尾「⋯」→ 「同步部门成员」
- 接口：`GET api/users/department/sync`，参数 `id`（要同步的目标部门 id）

## 操作步骤
1. 选中目标父部门
2. 点击「⋯」→「同步部门成员」
3. 提交，弹出结果提示：
   - `同步完成，共同步 N 个成员`
   - 若部分人员已在当前部门：`其中 M 个成员已在当前部门`

## 返回字段
| 字段 | 含义 |
|---|---|
| `synced_count` | 本次新加入该部门的人数 |
| `already_in_dept_count` | 已在该部门、跳过的人数 |
| `sub_department_ids` | 所有递归子部门 ID 列表 |

## 副作用
- 子部门成员的 `users.department` 追加目标部门 ID
- 部门群（dialog_id）会自动 joinGroup 把"在部门但不在群"的人补齐
- 全程事务：失败回滚

## 不支持
- 不支持"反向同步"（把父部门成员推下去）
- 不支持"仅预览不写入"

## 相关
- 部门概念：[[org-department.concept]]
- 删除部门会自动移出成员：[[org-department.delete.howto]]
