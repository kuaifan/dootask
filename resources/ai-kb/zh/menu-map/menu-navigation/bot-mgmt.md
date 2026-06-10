---
id: menu-navigation.bot-mgmt.menu-map
title: 机器人管理入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 机器人管理在哪
  - 我的机器人入口
  - 怎么加机器人
  - 自建机器人
  - bot 在哪管理
related_tools: []
related_pages: [application, messenger]
prerequisites: []
negative:
  - 系统内置机器人（任务提醒 / 审批等）不会出现在「我的机器人」列表
  - 系统机器人无法删除，仅管理员可改头像 / 昵称
  - 单个账号最多创建 50 个自建机器人
last_verified: v1.7.90
---

# 机器人管理入口在哪

## 路径
- 桌面端：左侧栏「应用」→「我的机器人」卡片 → 右侧 720px 抽屉
- 桌面端指令入口：消息列表搜 `bot-manager@bot.system` → 在单聊里发 `/list` 看列表、`/add` 新建
- 移动端：底部 Tabbar「应用」→「我的机器人」
- 快捷键：无

## 抽屉里能做什么
- 查看自己创建的机器人列表
- 「添加机器人」按钮：新建
- 每条机器人：「开始聊天 / 修改 / 删除」三个操作

## 权限要求
- 任何登录用户都能创建自建机器人（最多 50 个）
- 修改 / 删除系统机器人需管理员权限

## 找系统机器人
- 不在「我的机器人」里
- 在「消息」列表搜机器人名（如「任务提醒」「Claude」）

## 相关
- 机器人入口详解：[[bot.entry.menu-map]]
- 创建机器人：[[bot.create.howto]]
