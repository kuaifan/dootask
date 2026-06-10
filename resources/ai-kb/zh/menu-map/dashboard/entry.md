---
id: dashboard.entry.menu-map
title: 仪表盘入口在哪
type: menu-map
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘在哪
  - 怎么进仪表盘
  - 我的工作台
  - 主页在哪
  - 首页入口
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 仪表盘是登录后默认页之一，无单独权限
  - 移动端有专属的"我"Tab，与桌面端仪表盘内容相近但布局不同
  - 仪表盘内容是当前用户私有，不能切换查别人的
last_verified: v1.7.90
---

# 仪表盘入口在哪

## 路径
- **桌面端**：左侧栏顶部「仪表盘」菜单项（路由 `/manage/dashboard`）
- **桌面端**：登录默认页之一，多数用户进站后自动落到此页
- **桌面端 URL**：`https://<域名>/manage/dashboard`
- **移动端**：底部 Tabbar 的「我」Tab（功能近似仪表盘）
- **桌面快捷键**：无直达快捷键

## 谁能看到
- 所有登录用户都能看到仪表盘
- 内容是当前用户私有（自己名下任务、自己负责的任务）
- 站点管理员看到的也是自己的视图，不能切换查他人

## 主要内容
仪表盘展示当前用户「今日要做什么」的快速视图，详见 [[dashboard.concept]]：
- 顶部三个数字卡片：今日到期、超期任务、待完成
- 下方四个分组列表：今日到期 / 超期 / 待完成 / 协助的任务
- 每个分组可折叠展开

## 不支持
- 不能给团队 / 部门展示统一的"团队仪表盘"
- 不能切换"看某成员的仪表盘"
- 不能把仪表盘嵌入其他页面 / 桌面小组件
