---
id: kpi.plugin.concept
title: KPI 插件元信息
type: concept
feature: kpi
scope: end-user
locale: zh
aliases:
  - KPI 插件
  - kpi 怎么装
  - 绩效插件
  - 绩效考核插件
  - community_kuaifan_kpi
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - KPI 不是主程序内置功能，未装插件时入口不会出现
  - 插件升级不通过 git pull，需要在应用市场更新
  - 主程序版本必须高于 1.4.67，否则插件无法安装
last_verified: v1.7.90
---

# KPI 插件元信息

## 定义
KPI 绩效考核在 DooTask 中由社区插件提供，应用市场 app id 为 `community_kuaifan_kpi`（当前版本 0.1.9），feature 短名 `kpi`。主程序不内置任何 KPI 代码，所有绩效逻辑都跑在独立 Docker 容器中，作为应用插件挂载到 DooTask 界面。

## 关键属性
- **作者**：DooTask 官方
- **要求**：主程序版本 > 1.4.67（依赖新 API 能力）
- **运行形态**：单个 Docker 容器（镜像 `dootask/kpi:<version>`）
- **数据存储**：独立 SQLite 数据库，本地卷 `kpi_data` 挂载到 `/web/db`，不入主库
- **菜单注入**：安装后在「应用中心」注册「绩效考核」入口
- **重启策略**：`unless-stopped`，主机重启容器自动恢复

## 用户生命周期钩子
插件订阅了 DooTask 的用户事件，自动维护 KPI 内部用户：

- `user_onboard`：DooTask 创建用户时，自动在 KPI 内建号
  - 部门不存在则自动创建该部门
  - DooTask 管理员自动设为 HR 角色
  - 部门负责人自动设为 manager 角色
  - 其余设为 employee 角色
- `user_offboard`：DooTask 删除用户时，KPI 同步清理

## 信息同步规则
用户登录 KPI 时：

- 自动更新姓名、职位
- **角色保持不变**（不会因为 DooTask 角色变化而重新分配 KPI 角色）

## 不支持
- 不能离线安装到不联网的环境（需访问应用市场镜像源）
- 不能在主程序 < 1.4.67 的环境上安装

## 相关
- 是什么：[[kpi.concept]]
- 入口在哪：[[kpi.entry.menu-map]]
- 评分机制：[[kpi.scoring.concept]]
