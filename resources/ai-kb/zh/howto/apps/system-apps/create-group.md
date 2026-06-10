---
id: app-system.create-group.howto
title: 创建群组应用入口
type: howto
feature: app-system
scope: end-user
locale: zh
aliases:
  - 创建群组卡片
  - 应用中心建群
  - 怎么从应用中心建群
  - createGroup 入口
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 此入口只是触发建群对话框，不是另一个独立的建群流程
  - 不支持「按部门一键拉群」的预设
last_verified: v1.7.90
---

# 创建群组应用入口

## 是什么
应用中心「创建群组」卡片对应 `createGroup` 系统应用，点击后等价于在消息模块按「+」→「创建群组」，是常用的快速建群入口之一。

## 入口
- 桌面端：左侧导航「应用」→「创建群组」卡片
- 移动端：底部 Tabbar「应用」→「创建群组」
- 等效入口：消息模块顶部「+」、全局右上角「+」均能触发同一对话框

## 操作步骤
1. 应用中心 → 点「创建群组」
2. 弹出「创建群组」对话框
3. 输入群名 → 勾选成员 → 提交，自动进入新群

## 不支持
- 此卡片不带"项目讨论组"等预设模板，只是普通建群
- 单群人数不能超过系统设置上限（默认 200，可由管理员调整）

## 想了解完整建群流程
建群完整步骤、字段、群机器人见：[[messenger.group.howto.create]]
