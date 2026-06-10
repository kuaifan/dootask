---
id: app-admin.visibility.concept
title: 管理员应用对谁可见
type: concept
feature: app-admin
scope: admin
locale: zh
aliases:
  - 谁能看到管理员应用
  - 普通成员能看到 LDAP 吗
  - 管理员应用权限
  - 为什么我看不到管理员分区
  - 管理员区不显示
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 部门负责人 / 部门管理员不属于系统管理员，看不到管理员应用
  - 没有「按应用单独授权管理员卡片」的细粒度开关
  - 关掉账号管理员身份后，应用中心管理员区在下次刷新前可能仍有缓存
last_verified: v1.7.90
---

# 管理员应用对谁可见

## 定义
应用中心「管理员」分区（Admin row）只对系统管理员渲染，判断字段是前端 Vuex 的 `userIsAdmin`，对应后端 `User::isAdmin()`。普通成员、临时帐号、部门负责人均看不到这块。

## 判定逻辑
- 后端：用户 `identity` 字段包含 `admin` → `isAdmin() = true`
- 前端：`store.state.userIsAdmin` 为 `true` 才挂载管理员卡片列表
- 任一管理员应用项都带 `show: this.userIsAdmin` 过滤
- 整个「管理员」标题区也用 `adminAppItems.length > 0` 判定，0 项则连标题都不显示

## 普通成员看到什么
- 只看「常用」分区（系统应用 + 微应用，不含管理员卡片）
- 微应用如果设了 `visible_to: admin`，普通成员也看不到

## 升级身份后
- 让某成员变成管理员：系统管理员在「团队管理」勾选其「管理员」身份
- 该用户刷新页面后才能看到管理员应用区，应用中心不会自动热更新

## 相关
- 管理员应用清单：[[app-admin.list.concept]]
- 入口在哪：[[app-admin.entry.menu-map]]
