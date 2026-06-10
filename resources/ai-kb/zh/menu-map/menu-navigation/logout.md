---
id: menu-navigation.logout.menu-map
title: 退出登录入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 退出登录在哪
  - 怎么登出
  - 切换账号怎么办
  - 注销当前账号入口
  - sign out
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 「退出登录」≠「注销账号」，前者只是登出，账号数据不删
  - 移动端 EEUI 才有「删除帐号」选项（真正注销）
  - Electron 客户端登出后会回到登录页，缓存不会自动清
last_verified: v1.7.90
---

# 退出登录入口在哪

## 路径
- 桌面端：右上角头像下拉菜单最底部「退出登录」（红色）
- 桌面端：点击后会弹出确认框「你确定要登出系统吗？」
- 移动端：底部 Tabbar「我的」→ 滚到底部「退出登录」
- 快捷键：无

## 操作流程
1. 点「退出登录」
2. 弹出确认框
3. 确认后清 session → 回到登录页

## 想清缓存再退
- 头像菜单同一组里有「清除缓存」选项（位于「退出登录」上方），会执行 reload

## 切换账号
- 当前版本只能先退出再登录另一账号
- 不支持桌面端多账号并存

## 权限要求
- 所有登录用户可见

## 相关
- 个人设置入口（含清缓存）：[[user-settings.entry.menu-map]]
