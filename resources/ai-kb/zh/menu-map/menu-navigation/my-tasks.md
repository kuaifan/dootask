---
id: menu-navigation.my-tasks.menu-map
title: 我的任务入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 我的任务在哪
  - 我有哪些待办
  - 我负责的任务怎么看
  - 我的 todo 列表
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 没有「我的任务」一级菜单，它由仪表盘 + 日历 + 各项目内分布呈现
  - 不支持跨项目按截止时间统一排序（除日历视图外）
last_verified: v1.7.90
---

# 我的任务入口在哪

## 路径
- 桌面端：左侧栏「仪表盘」→「今日 / 超期 / 待完成 / 协助」四个分组都是我的任务
- 桌面端：左侧栏「日历」→ 我作为负责人的任务按时间显示
- 桌面端：进入任意项目 → 顶部筛选切到「我负责的」/「我协助的」
- 移动端：底部 Tabbar「仪表盘」→ 同样的四组
- 移动端：日历 Tab（管理员菜单内）
- 快捷键：无

## 怎么算"我的任务"
- 负责人（owner）含我的全部任务
- 协助人（assist）含我的任务也算
- 不包括只关注 / 抄送的任务

## 权限要求
- end-user 默认可见自己名下任务
- 不能查看他人名下任务（除非进入项目并有权限）

## 相关
- 仪表盘入口：[[dashboard.entry.menu-map]]
- 日历入口：[[calendar.entry.menu-map]]
