---
id: menu-navigation.messages.menu-map
title: 消息列表入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 消息在哪
  - 怎么打开聊天
  - 消息列表入口
  - 看未读怎么进
  - 单聊群聊在哪
related_tools: [search_dialogs]
related_pages: [messenger]
prerequisites: []
negative:
  - 不支持完全隐藏消息模块，仅可逐个隐藏单条会话
  - 没有「未登录" 访客访问入口
last_verified: v1.9.59
---

# 消息列表入口在哪

## 路径
- 桌面端：左侧栏「消息」（气泡图标）
- 桌面端 Electron：同上，任务栏图标小红点提示未读
- 移动端：底部 Tabbar「消息」Tab
- 顶部搜索框可直接搜会话和历史消息
- 快捷键：无（但搜索可用 Cmd/Ctrl + F）

## 打开后界面
- 左侧：会话列表（置顶 + 最后活跃倒序）
- 右侧：当前会话消息流
- 每条会话右侧角标：未读数 / @我 数

## 会话类型
- 单聊（与单个用户）
- 群聊（手动建的多人群）
- 项目讨论（自动生成）
- 任务讨论（自动生成）
- 机器人单聊

## 权限要求
- 所有登录用户可见
- 临时账号也可收发消息

## 相关
- 入口详解：[[messenger.entry.menu-map]]
- 通讯录：联系人列表，默认按字母分组；点击搜索框右侧的图标可切换为「按部门显示」（部门树，点击部门标题展开/收起并查看成员，部门负责人排在最前，标题右侧为含下级部门的成员数），再次点击切回按字母显示；搜索时统一显示平铺的搜索结果；所选显示方式会被记住
