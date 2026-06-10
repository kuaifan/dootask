---
id: appstore.concept
title: 应用市场是什么
type: concept
feature: appstore
scope: admin
locale: zh
aliases:
  - 应用商店
  - 应用市场
  - AppStore
  - 插件市场
  - 装插件在哪
  - 插件管理
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 不是 iOS / Android 那种第三方应用商店，仅装 DooTask 内部插件
  - 普通成员看不到「应用商店」入口
  - 不支持单用户安装，所有插件对全员生效
last_verified: v1.7.90
---

# 应用市场是什么

## 定义
应用市场（AppStore）是 DooTask 的插件管理后台，让系统管理员一键安装 / 卸载 / 更新各种功能插件，例如 AI 助手、审批、签到、OnlyOffice 等。其本体是一个名为 `appstore` 的微应用，注册在 `application/admin` 位置（见 `store/mutations.js` 第 396 行）。

## 关键属性
- **微应用形态**：通过 `MicroApps` 加载 iframe，URL 为 `appstore/internal`
- **后端校验**：`App\Module\Apps::isInstalled($appId)` 读取 `docker/appstore/config/{appId}/config.yml` 中 `status: installed` 判断
- **未安装会抛 ApiException**：`Apps::isInstalledThrow()` 提示「应用「X」未安装」
- **跨容器调度**：内部调 `http://appstore` 服务 API 完成安装动作
- **生命周期 Hook**：用户创建 / 离职会调 `dispatchUserHook` 通知各插件（user_onboard / offboard / update）

## 插件类型
- 官方内置：ai、approve、checkin/face、office、drawio、minder、okr、search（manticore）、fileview
- 社区插件：以 `community_` 前缀命名，如 `community_kuaifan_memos`、`community_kuaifan_kpi`、`community_Learntotolearn_roomly`

## 与「微应用菜单」的区别
- **应用市场**：管理插件「装/卸/更新」的容器
- **微应用菜单**：插件装好后注册到「应用」页的菜单项，普通成员可见的入口

## 不支持
- 不支持卸载 `appstore` 自身（`isInstalled('appstore')` 强制返回 true）
- 不支持普通成员浏览未装插件列表
- 不支持安装非 DooTask 兼容的任意 Docker 镜像

## 相关
- 安装：[[appstore.install.howto]]
- 卸载：[[appstore.uninstall.howto]]
- 入口：[[appstore.entry.menu-map]]
