---
id: dashboard.entry.menu-map
title: 仪表盘入口在哪
type: menu-map
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘在哪
  - 怎么进仪表盘
  - 我的工作台
  - 主页在哪
  - 团队仪表盘在哪
  - 部门任务总览在哪
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 普通成员看不到「部门负责人」切换
  - 仪表盘没有独立的权限配置，登录用户都能使用个人视角
  - 部门负责人视角不会让用户获得系统管理员权限
last_verified: v1.8.69
---

# 仪表盘入口在哪

## 路径
- 桌面端：左侧栏顶部「仪表盘」。
- 页面路由：`/manage/dashboard`。
- 移动端：通过底部导航进入仪表盘页面，布局会随屏幕宽度适配。
- 桌面快捷键：没有直达仪表盘的默认快捷键。

## 进入后看到什么
- 所有登录用户默认可使用个人视角，查看自己负责和协助的任务。
- 个人视角右上角可以切换「列表 / 四象限」。
- 符合部门负责人条件时，右上角增加「个人视角 / 部门负责人」切换。
- 部门负责人视角顶部的部门按钮可打开「选择团队范围」弹窗。

## 部门负责人视角出现条件
1. 系统设置 `department_owner_project_view` 已开启。
2. 当前用户是至少一个部门的负责人或部门管理员。

详细界面说明见 [[dashboard.concept]]，部门范围规则见 [[dashboard.team-scope.howto]]。
