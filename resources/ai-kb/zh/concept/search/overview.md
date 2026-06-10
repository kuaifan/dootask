---
id: search.concept
title: 全局搜索是什么
type: concept
feature: search
scope: end-user
locale: zh
aliases:
  - 全局搜索
  - 搜索是什么
  - 总搜索
  - 怎么搜东西
  - 在哪里搜
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 全局搜索不会跨用户：只能搜到当前账号有权限看到的内容
  - 关键词为空时不会触发搜索，必须输入至少 1 个字符
  - 单次搜索每类对象最多返回 50 条（默认 20 条）
last_verified: v1.7.90
---

# 全局搜索是什么

## 定义
全局搜索是 DooTask 内的统一检索入口，能够一次性跨 5 类对象查找内容：联系人、项目、任务、文件、消息。对应后端 `api/search/*` 5 个接口，统一在 SearchController 实现。

## 5 类搜索对象
- **联系人**（contact）：按昵称 / 邮箱 / 个人简介 / 技能标签匹配用户
- **项目**（project）：按项目名 / 项目描述匹配当前用户可访问的项目
- **任务**（task）：按任务名 / 描述 / 子内容匹配用户所在项目内的任务
- **文件**（file）：按文件名匹配，安装 search 插件后还能搜文件正文
- **消息**（message）：按消息文本匹配用户能看到的会话消息

## 权限模型
所有搜索都先做用户身份验证（`User::auth()`），结果只返回当前用户**已经有权限访问**的对象。搜不到不一定是关键词错，也可能是没有权限。

## 双引擎设计
全局搜索有两套底层实现，按是否安装 search 插件自动切换：
- 装了 Manticore 搜索插件：走 Manticore，支持全文 / 向量 / 混合搜索
- 未装：自动降级到 MySQL `LIKE` 模糊匹配

详见 [[search.engine.concept]]。

## 入口
- 桌面端：快捷键 `Ctrl/Cmd + F` 或 `Ctrl/Cmd + /`，详见 [[search.entry.menu-map]]
- 移动端：在仪表盘等页面通过搜索按钮触发
