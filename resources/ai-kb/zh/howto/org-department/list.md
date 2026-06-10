---
id: org-department.list.howto
title: 查看与管理部门列表
type: howto
feature: org-department
scope: admin
locale: zh
aliases:
  - 部门列表
  - 看部门
  - 部门有哪些
  - 查看所有部门
  - 部门管理
  - 怎么看公司部门
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
negative:
  - 普通成员看不到全量部门列表，只能看自己所在部门（info__departments，最多 10 条）
  - 列表按 id 升序，不支持自定义排序
  - 列表不分页，全量返回（受全系统 200 个上限保护）
last_verified: v1.7.90
---

# 查看与管理部门列表

## 入口
- 桌面端：右上角头像菜单 → 「管理后台」→ 左侧 → 「团队管理」→ 顶部部门栏
- 接口：`GET api/users/department/list`（仅管理员）

## 操作步骤
1. 进入团队管理页，左侧是部门树
2. 顶层「默认部门」表示未分配部门的成员（系统内置，不能删/改）
3. 点击任一部门可在右侧看到该部门的成员列表

## 部门项展示的信息
- 部门名称（最多 20 字）
- 部门负责人头像（owner_userid）
- 部门管理员头像（deputy_userids，多个时显示 `+N`）
- 行尾「⋯」菜单：添加子部门、部门交流群、同步部门成员、编辑、删除

## 普通成员视角
普通成员通过 `GET api/users/info/departments` 拿到的"我的部门列表"最多 10 条，按是否本人为负责人优先排序；不是这里讲的管理后台。

## 不支持
- 列表无搜索（部门数最多 200，肉眼可找）
- 列表无分页

## 相关
- 新建：[[org-department.add.howto]]
- 任命部门管理员：[[org-department.add-deputy.howto]]
- 删除：[[org-department.delete.howto]]
- 同步成员：[[org-department.sync.howto]]
