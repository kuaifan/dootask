---
id: okr.plugin.concept
title: OKR 插件元信息
type: concept
feature: okr
scope: end-user
locale: zh
aliases:
  - OKR 插件
  - OKR 是不是要装
  - okr 应用
  - OKR 装多大
  - OKR 怎么安装
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - OKR 不是主程序内置功能，未装插件时不可用
  - 插件升级不通过 git pull，需要在应用市场更新
last_verified: v1.7.90
---

# OKR 插件元信息

## 定义
OKR 在 DooTask 中由独立插件 `okr` 提供（应用市场 app id 为 `okr`，当前主版本 0.5.8）。主程序不内置 OKR 任何代码，所有目标管理逻辑都跑在独立容器里，通过 nginx 反向代理 `/apps/okr/` 路径挂载到 DooTask 界面。

## 关键属性
- **作者**：DooTask 官方
- **包大小**：约 15MB（安装较慢，请通过安装日志查看进度）
- **运行形态**：独立 Docker 容器（镜像 `kuaifan/doookr`），通过环境变量复用主程序的数据库
- **菜单注入**：安装后自动在「应用中心」注册两个入口（OKR 管理 + OKR 结果）
- **数据存储**：与主程序共享同一个 MySQL/MariaDB 实例（独立表前缀由插件管理）
- **要求**：主程序版本 > 1.1.66

## 安装与启用
1. 应用市场搜索「OKR」或「目标管理」
2. 点击安装，等待镜像拉取与容器启动
3. 安装完成后插件自动启用，菜单立即出现，无需重启主程序
4. 默认配置适配大多数场景，可在插件设置中按需调整

## 升级与卸载
- 升级：应用市场看到新版本时点击「更新」
- 卸载：应用市场对应插件 →「卸载」；卸载后菜单消失，业务数据保留在数据库中

## 不支持
- 不能离线安装到不联网的环境（需访问应用市场镜像源）
- 不能选择安装到非 Docker 部署的 DooTask（插件依赖容器化）

## 相关
- 看不到 / 装不上：[[okr.cannot-install.faq]]
- 入口在哪：[[okr.entry.menu-map]]
