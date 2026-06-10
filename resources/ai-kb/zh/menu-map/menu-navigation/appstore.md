---
id: menu-navigation.appstore.menu-map
title: 应用市场入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 应用市场在哪
  - 应用商店入口
  - 装插件在哪
  - 插件管理入口
  - AppStore 怎么进
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 普通成员看不到「应用商店」入口
  - 应用商店不在「系统设置」里，而在「应用」中心 → 管理员分组
  - 不支持快捷键
last_verified: v1.7.90
---

# 应用市场入口在哪

## 路径
- 桌面端：左侧栏「应用」→ 顶部分组「管理员」→「应用商店」卡片
- 移动端：底部 Tabbar「应用」→「管理员」分组 →「应用商店」
- URL：内部走 `appstore/internal?language={lang}&theme={theme}`
- 快捷键：无

## 能做什么
- 安装 / 卸载 / 更新插件
- 查看已安装、未安装、有更新可用三类

## 权限要求
- `admin` 才能看到入口
- 普通成员无此卡片

## 相关
- 完整入口说明：[[appstore.entry.menu-map]]
- 安装插件：[[appstore.install.howto]]
- 卸载插件：[[appstore.uninstall.howto]]
