---
id: application.entry.menu-map
title: 应用中心入口在哪
type: menu-map
feature: application
scope: end-user
locale: zh
aliases:
  - 应用中心在哪
  - 怎么打开应用
  - 应用列表入口
  - 应用图标在哪
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 应用中心不等于应用市场（AppStore），后者只有管理员能进
last_verified: v1.7.90
---

# 应用中心入口在哪

## 路径
- 桌面端：左侧主导航栏的「应用」图标（位于仪表盘 / 日历 / 消息 / 文件之后）
- 移动端竖屏：底部 Tabbar 「应用」（与日历、文件、设置一并被收纳进应用中心）
- 快捷键：无

## 页面内构成
- 顶部右上角「⋯」菜单：调整排序、自定义应用菜单（仅管理员）
- 「常用」分区：所有用户可见的系统应用 + 微应用
- 「管理员」分区：仅 `userIsAdmin` 可见的管理员应用

## 权限要求
- end-user 可见「常用」区
- admin 才能看到「管理员」区及「自定义应用菜单」按钮

## 相关
- 应用中心的整体定义：[[application.center.concept]]
- 三类应用怎么区分：[[application.classify.concept]]
