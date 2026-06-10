---
id: org-department.delete.howto
title: 删除部门
type: howto
feature: org-department
scope: admin
locale: zh
aliases:
  - 删除部门
  - 删部门
  - 销毁部门
  - 移除部门
  - 部门怎么删
  - 删除部门会怎么样
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
  - 目标部门下没有子部门（否则需先删除子部门）
negative:
  - 含子部门的部门**不能直接删**，会报「含有子部门无法删除」，必须先删所有子部门
  - 删除会**自动**把所有成员从该部门移出（`users.department` 字段中移除该部门 ID）
  - 删除会**自动解散**该部门绑定的部门群（dialog_id 对应群）
  - 删除后无法恢复
  - 仅系统管理员可调用
last_verified: v1.7.90
---

# 删除部门

## 入口
- 桌面端：管理后台 → 团队管理 → 某部门行尾「⋯」→ 「删除」（红色文字）
- 接口：`GET api/users/department/del`，参数 `id`（部门 id）

## 前置条件
该部门下**不能**有子部门。如有子部门，先删子部门：
1. 选中最深层叶子部门 → 删除
2. 逐层向上，最后删父部门

## 操作步骤
1. 在部门树找到目标部门
2. 点击行尾「⋯」→「删除」
3. 二次确认后提交，成功提示「删除成功」

## 删除时系统的副作用
| 副作用 | 说明 |
|---|---|
| 移出全体成员 | 所有 `users.department` 包含该部门 ID 的用户被批量移除 |
| 解散部门群 | dialog_id 对应的部门群被删除 |
| 清理部门管理员 | `user_department_owners` 表对应记录全部删除（防悬挂） |
| 删除部门记录 | UserDepartment 行删除 |

## 不支持
- 不支持级联删（含子部门必须先删子）
- 不支持"软删除"，删除即彻底删
- 不支持"保留群解散部门"或反向操作

## 相关
- 新建/修改：[[org-department.add.howto]]
- 同步成员（避免误删）：[[org-department.sync.howto]]
