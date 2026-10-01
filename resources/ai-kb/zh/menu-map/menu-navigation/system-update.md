---
id: menu-navigation.system-update.menu-map
title: 系统更新入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 系统更新在哪
  - 更新系统在哪
  - 在线升级入口
  - 服务器升级入口
  - 怎么更新服务端
related_tools: []
related_pages: [setting]
prerequisites:
  - 系统管理员账号
negative:
  - 普通成员看不到「系统更新」「更新系统」选项
  - 「更新系统」头像菜单项只在有系统新版本时出现
last_verified: v1.9.36
---

# 系统更新入口在哪

## 路径
- 设置 → 左侧菜单「系统更新」（位于「更新日志」「版本」上方）
- 右上角头像下拉菜单 →「更新系统」（位于「客户端下载」「更新客户端」同一组，仅在有系统新版本时显示）
- 路由：`/manage/setting/system-update`

## 能做什么
- 查看当前版本、最新版本与更新日志
- 检查更新、在线更新系统并查看进度与日志

## 权限要求
- 仅系统管理员（`admin`）可见
- 发起更新需要再次输入当前管理员的登录密码

## 相关
- 在线更新步骤：[[system-update.online-update.howto]]
- 客户端（桌面端）更新：[[electron-client.update.howto]]
