---
id: memos.concept
title: Memos 是什么
type: concept
feature: memos
scope: end-user
locale: zh
aliases:
  - Memos
  - 速记
  - 笔记应用
  - 个人笔记
  - 想法记录
  - 碎片笔记
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 memos 插件
negative:
  - Memos 不是主程序内置功能，未装插件时不可用
  - Memos 默认走 SQLite 本地存储，不接 DooTask 主库
  - 不支持游客 / 未登录访问
last_verified: v1.7.90
---

# Memos 是什么

## 定义
Memos（笔记 / 速记）是一款隐私优先、轻量级的开源笔记服务（开源项目 [usememos.com](https://www.usememos.com)），在 DooTask 中以独立插件形式集成，主要用来快速记录想法、待办、链接、代码片段等碎片化内容。每条记录就是一条「memo」，时间倒序排列在时间线上。

## 在 DooTask 中的形态
- 通过应用市场安装的社区插件，与主程序同源、共用 TLS
- 走主程序 nginx 子路径 `/apps/memos/` 反向代理，用户无需额外登录
- 数据自托管，使用 SQLite，存放于应用目录 `data/memos`

## 关键特性
- **轻量速记**：以时间线方式记录碎片想法，类似 Twitter/微博风格
- **隐私优先**：数据完全自托管，不上传第三方
- **单点登录**：DooTask 登录态自动同步，免输密码
- **标签 / 分类**：支持 `#标签` 语法分类整理（详见 [[memos.tag.concept]]）

## 适用场景
- 工作日志、灵感速记
- 临时收藏链接 / 代码片段
- 个人学习笔记 / 读书摘录
- 团队不强协作场景下的轻量笔记

## 不支持
- 不替代正式文档（如需协作文档请用项目内文档）
- Memos 不直接与任务 / 项目数据互通

## 相关
- 插件元信息：[[memos.plugin.concept]]
- 入口在哪：[[memos.entry.menu-map]]
- 怎么写一条：[[memos.create.howto]]
