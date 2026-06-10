---
id: menu-navigation.files.menu-map
title: 我的文件入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 文件在哪
  - 我的文件入口
  - 网盘在哪
  - 怎么找上传过的文件
related_tools: [list_files, search_files]
related_pages: [file]
prerequisites: []
negative:
  - 「文件」入口不能隐藏 / 改顺序
  - 不支持挂载外部网盘（如百度网盘 / OneDrive）
last_verified: v1.7.90
---

# 我的文件入口在哪

## 路径
- 桌面端：左侧栏「文件」（一级菜单，文件夹图标）
- 桌面端 Electron：同上，支持窗口拖入上传
- 桌面端 URL：`https://<域名>/manage/file`
- 移动端竖屏：底部 Tabbar 不显示「文件」一级，需要从「应用」→「文件」卡片
- 快捷键：无

## 默认视图
- 顶部面包屑显示路径
- 主区列出文件夹和文件
- 分「我的文件」和「共享」两段

## 权限要求
- 所有登录用户可见
- 看到的是自己创建的 + 他人共享给我的

## 相关
- 入口详解：[[file.entry.menu-map]]
- 上传 / 新建：[[file.upload.howto]]
- 共享：[[file.share.howto]]
