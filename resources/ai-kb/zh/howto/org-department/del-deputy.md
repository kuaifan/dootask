---
id: org-department.del-deputy.howto
title: 罢免部门管理员
type: howto
feature: org-department
scope: admin
locale: zh
aliases:
  - 罢免部门管理员
  - 取消部门管理员
  - 移除 deputy
  - 删除部门管理员
  - 撤销部门管理员
  - 部门管理员怎么去掉
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
  - 目标部门已存在
negative:
  - 罢免操作**会同时**：① 删除 `user_department_owners` 记录；② 从 `users.department` 移除该部门 ID；③ 让用户退出部门群
  - 不能罢免部门负责人（owner_userid）；接口对 owner_userid 调用时仅做悬挂记录清理，不会把负责人踢出部门或群
  - 操作幂等：重复罢免非 deputy 用户不报错也不做任何事
  - 仅系统管理员可调用
last_verified: v1.7.90
---

# 罢免部门管理员

## 入口
- 桌面端：管理后台 → 团队管理 → 选中部门 → 在成员列表中找到该部门管理员 → 操作菜单「取消部门管理员」
- 接口：`POST api/users/department/deldeputy`，参数 `id`（部门 id）+ `userid`（被罢免用户）

## 操作步骤
1. 选择目标部门
2. 选择要罢免的部门管理员（必须当前是该部门的 deputy）
3. 提交，成功提示「罢免成功」

## 罢免后系统的副作用
| 副作用 | 说明 |
|---|---|
| 删除 deputy 记录 | `user_department_owners` 表对应行删除 |
| 移出部门 | 用户的 `users.department` 字段移除该部门 ID |
| 退出部门群 | exitGroup 调用，成员关系一致 |
| 推送 groupUpdate | 部门群内其他成员实时收到 deputy_ids 变更 |

## 误操作恢复
没有"撤销罢免"，重新走 [[org-department.add-deputy.howto]] 即可（流程一致，自动重新加入部门 + 群）。

## 不支持
- 不能罢免部门负责人（owner_userid）；改负责人要用 [[org-department.add.howto]] 传 id + 新 owner_userid
- 不能批量罢免，逐个用户调接口
- 不能"只删 deputy 标记保留部门成员关系"，二者绑定

## 相关
- 任命：[[org-department.add-deputy.howto]]
- 部门负责人/管理员的角色定义：[[org-department.deputy.concept]]
