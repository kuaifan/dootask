---
id: micro-app.entry.menu-map
title: 微应用入口在哪
type: menu-map
feature: micro-app
scope: end-user
locale: zh
aliases:
  - 微应用在哪看到
  - 插件入口在哪
  - 找不到微应用
  - 应用图标在哪
  - 怎么打开微应用
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 普通成员看不到 `visible_to: admin` 的微应用
  - 仅装了未配置菜单的插件，应用中心不会显示卡片
  - 微应用入口和应用市场入口不是同一个，前者是用、后者是装
last_verified: v1.7.90
---

# 微应用入口在哪

## 路径
- 桌面端：左侧主导航「应用」→ 「常用」分区里夹杂的非系统卡片（图标来自插件资源）
- 桌面端：部分微应用经管理员配置可挂到「左侧主导航」（`location: main/menu`）
- 移动端竖屏：底部 Tabbar「应用」→ 滚动卡片网格
- 快捷键：无

## 三种挂载位置
- `location: application` → 应用中心「常用」分区（最常见）
- `location: application/admin` → 应用中心「管理员」分区（仅管理员可见）
- `location: main/menu` → 主导航顶层（如 OKR 安装后可注入）

## 微应用卡片长什么样
- 图标取自插件 `icon` 字段，背景与系统应用区分明显
- 标签为插件 `label`（支持中英双语 JSON）
- 点击直接打开 iframe / 跳转目标 URL
- 不显示徽标（系统应用如审批 / 报告才有未读数）

## 权限要求
- end-user 看到所有 `visible_to: all` 的微应用
- admin 额外看到 `visible_to: admin` 的微应用
- 详见 [[micro-app.permission.concept]]

## 相关
- 微应用是什么：[[micro-app.concept]]
- 排序：[[micro-app.sort.howto]]
- 自定义菜单：[[micro-app.menu.concept]]
