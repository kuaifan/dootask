---
id: okr.entry.menu-map
title: OKR 入口在哪里
type: menu-map
feature: okr
scope: end-user
locale: zh
aliases:
  - OKR 在哪
  - 怎么打开 OKR
  - 找不到 OKR
  - OKR 管理入口
  - OKR 结果在哪看
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 okr 插件（约 15MB）
negative:
  - 主程序不内置 OKR，未装插件时入口不会出现
  - 「OKR 结果」（OKR Analysis）入口仅系统管理员可见
last_verified: v1.7.90
---

# OKR 入口在哪里

## 路径
OKR 由独立插件提供，安装后会在应用中心注册两个菜单项：

- 桌面端：左侧栏「应用」→「OKR 管理」（对应 URL `apps/okr/list`）
- 移动端：底部 Tabbar「应用」→「OKR 管理」
- 管理员视角：左侧栏「应用」→「管理员应用」→「OKR 结果」（对应 URL `apps/okr/analysis`，仅 admin 可见）

## 加载方式
两个入口都是 `url_type: inline`，会在 DooTask 主框架内嵌打开插件页面，不跳转到外部站点。

## 权限要求
- 「OKR 管理」：所有已登录用户可见可用
- 「OKR 结果」：只有系统管理员（`userIsAdmin`）能看到入口

## 看不到入口怎么办
1. 确认应用市场已安装 okr 插件，参见 [[okr.cannot-install.faq]]
2. 安装较慢（约 15MB），首次安装后等几分钟刷新页面
3. 「OKR 结果」入口缺失通常是因为当前用户不是系统管理员

## 相关
- 插件元信息：[[okr.plugin.concept]]
- OKR 方法论：[[okr.concept]]
