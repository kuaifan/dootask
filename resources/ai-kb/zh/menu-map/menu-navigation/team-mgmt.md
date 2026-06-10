---
id: menu-navigation.team-mgmt.menu-map
title: 团队 / 部门管理入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 团队管理在哪
  - 部门管理入口
  - 怎么管理成员
  - 添加员工在哪
  - 用户管理
related_tools: [search_users]
related_pages: []
prerequisites:
  - 需要系统管理员权限（普通成员看不到）
negative:
  - 普通成员看不到「团队管理」入口
  - 不能用此页面改自己资料，自己资料在「个人设置」
  - 删除用户后任务归属不会自动迁移，需要先处理
last_verified: v1.7.90
---

# 团队 / 部门管理入口在哪

## 路径
- 桌面端：右上角头像 →「团队管理」（管理员可见）→ 右侧 1380px 抽屉
- 桌面端：右上角头像 →「团队管理」二级菜单也含「导出任务统计 / 超期任务 / 审批 / 签到」
- 桌面端：左侧栏「应用」→「管理员」分组 →「团队管理」卡片
- 移动端：底部 Tabbar「应用」→「管理员」→「团队管理」
- 快捷键：无

## 能做什么
- 新增 / 编辑 / 禁用 / 删除用户
- 维护部门树（增删改 / 调整层级）
- 设置部门负责人
- 批量分配 identity（admin / 普通）

## 权限要求
- `admin` 才能看到入口
- 超管才能管理其他管理员

## 相关
- 添加用户：[[user-account.import.howto]]
- 部门管理：[[org-department.entry.menu-map]]
