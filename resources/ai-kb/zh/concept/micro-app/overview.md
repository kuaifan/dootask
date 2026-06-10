---
id: micro-app.concept
title: 微应用是什么
type: concept
feature: micro-app
scope: end-user
locale: zh
aliases:
  - 微应用是什么
  - 什么是微应用
  - 插件应用
  - 第三方应用
  - 微应用和系统应用的区别
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 微应用不是 DooTask 主程序代码，是独立插件提供
  - 微应用与「应用商店（应用市场 / AppStore）」不是同一个东西，应用商店是用来安装它们的入口
  - 不是所有微应用都被自动安装，未装时菜单不会出现
last_verified: v1.7.90
---

# 微应用是什么

## 定义
微应用是由「应用商店」（也叫应用市场 / AppStore）安装的插件提供的一类应用，以独立卡片 / 页面形式嵌入 DooTask 主框架（iframe / inline 等方式）。每个微应用对应一个独立 Docker 容器或前端资源包，挂载到「应用」中心或主导航上让用户访问。

## 与系统应用的区别
| 维度 | 系统应用 | 微应用 |
|---|---|---|
| 来源 | 主程序内置代码 | 应用商店已安装插件 |
| 是否需要安装 | 不需要 | 需要管理员在应用商店点装 |
| 卸载后影响 | 不能卸载 | 卸载后菜单消失，业务数据保留在数据库 |
| 升级方式 | 跟主程序版本走 | 应用商店单独更新 |

## 关键属性
- **登录态继承**：打开微应用时自动带上当前用户 token，无需再次登录
- **菜单注入**：插件通过 `microapp_menu` 接口或自带 `menu_items` 配置注册到 `location: application`、`application/admin` 或 `main/menu`
- **显示条件**：受 `visible_to`（all / admin）与系统管理员身份共同控制
- **生命周期**：独立容器；主程序与微应用之间通过 nginx 反代和 token 鉴权交互

## 当前可见列表
随已安装插件而变，详见 [[micro-app.list.concept]]。

## 相关
- 怎么安装：[[micro-app.install.howto]]
- 在哪能看到：[[micro-app.entry.menu-map]]
- 自定义菜单：[[micro-app.menu.concept]]
