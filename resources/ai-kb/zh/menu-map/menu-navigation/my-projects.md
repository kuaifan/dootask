---
id: menu-navigation.my-projects.menu-map
title: 我的项目入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 我的项目在哪
  - 我加入了哪些项目
  - 项目列表怎么看
  - 我负责的项目入口
related_tools: [list_projects]
related_pages: [project_list, dashboard]
prerequisites: []
negative:
  - 默认列表只显示我加入的项目，未加入项目不显示
  - 已归档项目要切到「已归档」筛选
  - 不支持申请加入未加入的项目
last_verified: v1.7.90
---

# 我的项目入口在哪

## 路径
- 桌面端：左侧栏 →「项目」分组（默认展开），所有我加入的项目按置顶 + 自定义顺序排列
- 桌面端：左侧栏顶部「仪表盘」→「我的项目」卡片
- 桌面端：头像下拉（管理员）→「所有项目」可看全站
- 移动端：底部 Tabbar「项目」Tab
- 桌面快捷键：Cmd/Ctrl + P 唤起项目快搜

## 筛选选项
- 「我加入的」（默认）
- 「全部」：仅站点管理员可见
- 「已归档」：archived_at 非空的

## 权限要求
- end-user 默认看到自己加入的项目
- 站点管理员（admin）可看到全站项目

## 相关
- 项目入口总览：[[project.entry.menu-map]]
- 个人项目概念：[[project.personal.concept]]
