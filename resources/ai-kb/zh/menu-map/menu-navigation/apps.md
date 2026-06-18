---
id: menu-navigation.apps.menu-map
title: 应用中心入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 应用中心在哪
  - 应用菜单怎么找
  - 怎么打开应用面板
  - apps 入口
  - 所有插件入口
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 部分卡片受插件安装 / 角色权限影响显隐
  - 卡片顺序可在「应用排序」里调整，详见 [[menu-navigation.apps-sort.menu-map]]
last_verified: v1.7.90
---

# 应用中心入口在哪

## 路径
- 桌面端：左侧栏「应用」一级菜单（图标为九宫格）
- 桌面端 URL：`/manage/application`
- 移动端：底部 Tabbar「应用」Tab（5 个 Tab 之一）
- 快捷键：无

## 默认看到什么
桌面端进入后是「常用应用 + 管理员应用」的卡片墙，含：
- 常用：审批 / 我的收藏 / 最近打开 / 工作报告 / 我的机器人 / 签到打卡 / 在线会议 / 创建群组 / 群投票 / 群接龙 / 创建项目 / 添加任务
- 管理员（仅管理员可见）：LDAP / 邮件通知 / APP 推送 / 举报管理 / 数据导出 / 团队管理 / 应用商店

移动端竖屏额外含：日历、文件、设置（因为它们不在 Tabbar 上）。

## 权限要求
- 所有登录用户可见入口
- 但「管理员」分组卡片只对 `admin` 可见

## 相关
- 应用排序：[[menu-navigation.apps-sort.menu-map]]
- 微应用入口：[[micro-app.entry.menu-map]]
- 审批中心：随审批（approve）插件提供，安装后可在 AI 助手中检索
- 在线会议：[[meeting.entry.menu-map]]
