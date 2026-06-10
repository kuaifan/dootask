---
id: org-department.add-deputy.howto
title: 任命部门管理员
type: howto
feature: org-department
scope: admin
locale: zh
aliases:
  - 任命部门管理员
  - 加部门管理员
  - 加 deputy
  - 给部门加副管理员
  - 部门多个负责人
  - 怎么让多人管理部门
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
  - 目标部门已存在
  - 被任命用户存在且不是该部门的部门负责人（owner_userid）
negative:
  - 不能把部门负责人（owner_userid）任命为部门管理员，会报「不能将部门负责人任命为部门管理员」
  - 任命部门管理员**会自动**把用户加入该部门（写入 `users.department`），并加入部门群（role=2、important=true）
  - 操作幂等：重复任命已是 deputy 的用户不会报错
  - 仅系统管理员可调用，部门负责人本人无权任命
last_verified: v1.7.90
---

# 任命部门管理员

## 入口
- 桌面端：管理后台 → 团队管理 → 选中目标部门 → 在右侧成员列表中找到目标用户 → 行操作菜单「任命为部门管理员」
- 接口：`POST api/users/department/adddeputy`，参数 `id`（部门 id）+ `userid`（被任命用户）

## 操作步骤
1. 选择目标部门
2. 选择被任命用户（必须存在的活跃用户）
3. 提交，成功提示「任命成功」

## 任命后系统会自动做的事
| 动作 | 影响 |
|---|---|
| 写 user_department_owners 表 | 标记 deputy 身份（unique key 幂等） |
| 加入 users.department | 该用户成为部门成员（与负责人对齐） |
| 加入部门群 | 自动入群，important=true（防止被普通群操作打散） |
| 群内 role 设为 2 | 在部门群中显示为部门管理员 |

## 部门管理员能做什么
任命后该用户拥有的能力见 [[org-department.deputy.concept]]：开启「部门负责人视角」后可只读查看本部门及下级成员参与的项目和任务。

## 不支持
- 不能批量任命，要逐个用户调接口
- 不能任命已是 owner_userid 的本部门负责人

## 相关
- 罢免：[[org-department.del-deputy.howto]]
- 负责人视角：[[org-department.deputy-view.howto]]
