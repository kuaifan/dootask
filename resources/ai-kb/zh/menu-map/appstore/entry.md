---
id: appstore.entry.menu-map
title: 应用市场入口在哪
type: menu-map
feature: appstore
scope: admin
locale: zh
aliases:
  - 应用市场在哪
  - 应用商店在哪
  - 怎么打开 AppStore
  - 装插件入口
  - 插件管理在哪
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 普通成员看不到「应用商店」入口
  - 不在「系统设置」里，而在「应用 → 管理员」分组
  - 不支持快捷键打开
last_verified: v1.7.90
---

# 应用市场入口在哪

## 路径
- 桌面端：左侧栏「应用」→ 顶部分组「管理员」→「应用商店」卡片
- 移动端：底部 Tabbar「应用」→「管理员」分组 → 「应用商店」
- URL：内部走 `appstore/internal?language={lang}&theme={theme}`
- 快捷键：无

## 注册方式
入口由 `store/mutations.js` 的 `microApps/data` mutation 在 `userIsAdmin` 时动态注入，是一个 menu_item：
- `location`: `application/admin`
- `label`: `应用商店`
- `icon`: `images/application/appstore.svg`
- `capsule`: 右上角胶囊（top 18 / right 18）

## 权限要求
- 必须是系统管理员（identity 含 `admin`）
- 后端 `App\Module\Apps::isInstalled('appstore')` 强制返回 true，但前端入口注册只在 `userIsAdmin = true` 时执行
- 超级管理员（id=1）默认有权限

## 找不到怎么办
- 头像下拉菜单顶部如果没有显示「系统管理员」徽标 → 联系超管把 identity 加上 admin
- 已是管理员但仍看不到 → 强制刷新页面 / 重登
- 还是没有 → 看服务器是否禁用了 appstore 容器，必要时联系运维

## 在哪管理已装插件
入口里同一面板可看到「已安装」「未安装」「更新可用」三个分类。逐个操作详见：
- 安装：[[appstore.install.howto]]
- 卸载：[[appstore.uninstall.howto]]
- 更新：[[appstore.update.howto]]
- 装不上：[[appstore.cannot-install.faq]]
