---
id: abuse-report.entry.menu-map
title: 举报管理入口在哪
type: menu-map
feature: abuse-report
scope: admin
locale: zh
aliases:
  - 举报管理在哪
  - 投诉管理入口
  - 怎么找到举报后台
  - 用户举报哪里看
  - complaint 入口
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（普通成员看不到）
negative:
  - 普通成员只看得到「举报」提交入口，看不到管理后台
  - 移动端同样在「应用 → 管理员」分组下
last_verified: v1.7.90
---

# 举报管理入口在哪

## 路径
- 桌面端：左侧栏「应用」→ 顶部分组「管理员」→「举报管理」卡片
- 移动端：底部 Tabbar「应用」→ 同样在「管理员」分组
- 快捷键：无

## 权限要求
卡片在 `pages/manage/application.vue` 中声明为 `{type: 'admin', value: 'complaint', show: this.userIsAdmin}`。
- 必须是系统管理员（identity 含 `admin`）
- 后端 `api/complaint/lists` 和 `api/complaint/action` 也会用 `User::auth('admin')` 二次校验
- 超级管理员（id=1）自然有权限

## 找不到怎么办
- 个人头像下拉菜单顶部如果没有显示「系统管理员」徽标，说明账号无权限
- 联系超级管理员到「团队管理」把账号 identity 加上 `admin`
- 刷新页面后「举报管理」会出现在「管理员」分组

## 用户侧举报入口
普通成员举报路径不在「应用」菜单里，而是在群聊 / 个人对话内：
- 桌面端：对话窗口右上角「···」→「举报对话」
- 移动端：对话窗口右上角「···」→「举报对话」
- 走 `POST api/complaint/submit`，需要选举报类型、填原因、可传图

## 相关
- 处理流程：[[abuse-report.handle.howto]]
- 概念：[[abuse-report.concept]]
