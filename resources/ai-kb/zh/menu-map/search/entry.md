---
id: search.entry.menu-map
title: 全局搜索入口在哪
type: menu-map
feature: search
scope: end-user
locale: zh
aliases:
  - 搜索快捷键
  - 怎么打开搜索
  - 搜索框在哪
  - 搜索入口
  - Ctrl F
  - Cmd F
related_tools: []
related_pages: []
prerequisites: []
negative:
  - "桌面端无顶部固定搜索栏按钮，主要通过快捷键唤起"
  - "`Ctrl/Cmd + Shift + F` 不是全局搜索，会被浏览器或系统拦截"
last_verified: v1.7.90
---

# 全局搜索入口在哪

## 路径
- 桌面端：快捷键 `Ctrl + F` 或 `Cmd + F`（Mac）唤起全局搜索框
- 桌面端备用快捷键：`Ctrl + /` 或 `Cmd + /`
- 桌面端：左侧栏「仪表盘」页面中的「全局搜索」入口卡片
- 移动端：底部 Tabbar / 仪表盘内的搜索按钮，点击弹出全屏搜索

## 搜索框形态
- 桌面端：居中弹窗，宽 768px，含输入框、5 个对象分类标签（任务 / 项目 / 消息 / 联系人 / 文件）和结果列表
- 移动端（窗口宽 < 576px）：自动全屏展开

## 在搜索框内的操作
- 输入关键词后自动延时触发搜索（无需回车）
- 点击顶部标签可只看某一类对象的结果；再次点击取消筛选
- 同时装了 search + ai 插件时，会出现「AI 搜索」按钮，把搜索词转给 AI 助手做语义对话

## 权限要求
- 所有登录用户均可使用，无角色限制
- 但搜索结果会按用户权限过滤（见各子接口的权限说明，如 [[search.contact.howto]]）

## 不支持
- 桌面端没有顶部常驻搜索栏，必须用快捷键或仪表盘入口
- `Ctrl/Cmd + Shift + F` 已被快捷键路由忽略，不会触发全局搜索
