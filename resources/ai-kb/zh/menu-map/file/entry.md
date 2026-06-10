---
id: file.entry.menu-map
title: 文件入口在哪
type: menu-map
feature: file
scope: end-user
locale: zh
aliases:
  - 文件在哪里
  - 怎么进文件
  - 文件菜单
  - 我的文件入口
  - 网盘在哪
related_tools: [list_files]
related_pages: [file]
prerequisites: []
negative:
  - 文件入口不在「应用」二级页面，是左侧栏一级菜单
  - 移动端目前没有专门的「文件」Tabbar，需要从「更多」进
last_verified: v1.7.90
---

# 文件入口在哪

## 路径
DooTask 的「文件」是一级导航，相当于个人网盘 + 团队共享盘。

- 桌面端 Web：左侧栏「文件」（图标为文件夹）→ 进入个人文件根目录
- 桌面端 Electron：同上，支持外部窗口拖入上传
- 移动端：底部 Tabbar「更多」→「文件」

## 默认视图
- 顶部面包屑显示当前路径（根目录 / 文件夹层级）
- 主区域列出当前文件夹下的文件与文件夹（最多 500 条，超出滚动加载）
- 自己创建的在「我的文件」段，他人共享给我的在「共享」段

## 权限要求
- 所有登录用户可见
- 无管理员限制；访客（游客 token）只能访问被显式标记 `guest_access` 的链接

## 不支持
- 文件菜单不能隐藏 / 不能改顺序（系统级一级入口）
- 不支持挂载外部网盘（如百度网盘 / OneDrive）
- 不支持按全公司维度浏览所有人文件（出于隐私）
