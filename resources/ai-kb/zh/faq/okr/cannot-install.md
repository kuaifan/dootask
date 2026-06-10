---
id: okr.cannot-install.faq
title: 看不到 OKR / 安装失败怎么办
type: faq
feature: okr
scope: end-user
locale: zh
aliases:
  - OKR 看不到
  - OKR 入口没有
  - OKR 装不上
  - OKR 安装失败
  - OKR 怎么开通
  - OKR 安装很慢
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 主程序不内置 OKR，未装插件时入口绝对不会出现
  - 普通用户无法自助安装应用市场插件，需要系统管理员操作
last_verified: v1.7.90
---

# 看不到 OKR / 安装失败怎么办

## 问题
打开 DooTask 后在应用中心看不到「OKR 管理」入口，或在「应用商店」（应用市场）点了「安装」后长时间没有反应、提示失败。

## 常见原因
- OKR 是独立插件（应用商店 app id：`okr`），主程序不内置；未装时入口不出现
- 普通用户没有应用商店的安装权限，必须系统管理员（`userIsAdmin`）安装
- 插件镜像约 15MB，首次拉取较慢，看起来像「卡住」实际仍在下载
- 服务器无法访问应用商店镜像源（网络 / 防火墙 / DNS 问题）
- 主程序版本低于 1.1.66，不满足插件要求

## 解决
1. 确认自己角色：非管理员就联系管理员安装
2. 管理员打开「应用」→ 管理员分区「应用商店」，搜索「OKR」或「目标管理」
3. 点击安装后，通过应用商店的安装日志查看实时进度（不要立即重试）
4. 日志卡在拉取镜像：检查服务器到镜像源的网络连通性，确认能拉取 Docker 镜像
5. 提示版本不兼容：先升级主程序到 > 1.1.66 再安装
6. 安装完成但入口不出现：刷新浏览器 / 重新登录一次
7. 安装失败可在应用商店重试，仍失败请保留日志反馈给运维

## 相关
- 插件元信息：[[okr.plugin.concept]]
- OKR 入口：[[okr.entry.menu-map]]
- 通用权限问题：[[role-permission.permission-denied.faq]]
