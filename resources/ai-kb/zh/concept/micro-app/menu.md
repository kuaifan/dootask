---
id: micro-app.menu.concept
title: 自定义微应用菜单
type: concept
feature: micro-app
scope: admin
locale: zh
aliases:
  - 自定义应用菜单
  - 自定义微应用
  - 接入第三方网页
  - microapp_menu
  - 加个外链应用
  - 自建应用卡片
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 自定义菜单不会自动同步给已离线的用户，需刷新页面
  - 不支持把自定义菜单注入到「应用市场」或「系统设置」
  - 自定义菜单的 URL 必须能被 DooTask 前端 iframe / 跳转打开，未对外的内网地址需自行确保连通
last_verified: v1.7.90
---

# 自定义微应用菜单

## 定义
自定义微应用菜单（microapp_menu）让管理员把任意外部网页或服务以「微应用卡片」的形式接入 DooTask，无需打成插件。配置保存到系统设置项 `microapp_menu`，按用户身份过滤后下发到前端 `microAppsMenus` 状态。

## 关键属性
- **应用 ID**：唯一标识，例如 `custom-okr`
- **菜单位置 location**：
  - `application` — 应用中心「常用」分区
  - `application/admin` — 应用中心「管理员」分区
  - `main/menu` — 主导航栏一级菜单
- **可见范围 visible_to**：`all`（所有成员）/ `admin`（仅管理员）
- **类型 type**：`iframe` / `iframe_blank` / `inline` / `inline_blank` / `external`
- **URL**：支持 `{user_token}` 占位符做 SSO
- **其他选项**：`keep_alive` 保持激活、`auto_dark_theme` 跟随暗黑、`immersive` 沉浸式、`transparent` 透明背景、`disable_scope_css` 禁用作用域样式

## 配置入口
- 桌面端：左侧栏「应用」→ 右上角「⋯」→「自定义应用菜单」
- 仅 `userIsAdmin = true` 显示该入口

## 接口
- 获取：`POST api/system/microapp_menu?type=get`
- 保存：`POST api/system/microapp_menu?type=save`（限管理员）

## 与插件微应用的关系
- 插件微应用：通过应用市场安装后自动注册菜单，不可在此页编辑
- 自定义菜单：完全由管理员手填，专门用于接入未打包成插件的外部系统

## 相关
- 在哪能看到：[[micro-app.entry.menu-map]]
- 微应用整体概念：[[micro-app.concept]]
