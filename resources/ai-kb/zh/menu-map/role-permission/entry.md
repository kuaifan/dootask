---
id: role-permission.entry.menu-map
title: 改成员角色 / 权限相关入口在哪
type: menu-map
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 在哪改权限
  - 权限设置在哪
  - 怎么改角色
  - 团队管理在哪
  - 项目权限在哪改
  - 任务可见用户在哪设
  - 部门负责人在哪改
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 移动端无团队管理 / 部门管理 / LDAP 等系统级入口，必须在桌面端操作
  - 没有「全局权限设置」总入口，按场景分散在各模块
last_verified: v1.7.90
---

# 改成员角色 / 权限相关入口在哪

## 系统级（admin / 超管操作）
路径：右上角头像 → 「团队管理」（仅系统管理员可见）
- 设 / 取消系统管理员（setadmin / clearadmin）
- 设 / 取消临时账号（settemp / cleartemp）
- 设为离职 + 指定交接人（setdisable）
- 删除会员（delete）
- 修改部门归属（department）
权限要求：admin identity。详见 [[role-permission.grant-admin.howto]] 和 [[role-permission.transfer-owner.howto]]

## 部门级
路径：右上角头像 → 「团队管理」 → 「部门管理」标签
- 修改部门负责人（owner_userid）
- 任命 / 罢免部门管理员（adddeputy / deldeputy）
权限要求：admin identity

## 项目级
路径：左侧栏「项目」 → 进项目 → 右上角项目设置（齿轮图标）
- 项目成员管理：改成员的项目角色（负责人 / 项目管理员 / 成员）
- 转让项目：换项目负责人
- 项目权限策略：11 种动作粒度配置（添加列 / 删除任务等允许哪些角色）
权限要求：项目负责人或项目管理员。详见 [[role-permission.project-role.concept]]

## 任务级
路径：进任务详情页
- 任务负责人：负责人区域 → 点选成员
- 任务协助人：协助人区域 → 点选成员
- 可见用户：可见性区域 → 添加用户名单（不设 = 全员可见）
权限要求：项目负责人或任务负责人。详见 [[role-permission.task-role.concept]]

## 个人角色查询
路径：右上角头像 → 「个人设置」
- 查看自己所属部门、是否系统管理员
- 系统管理员 / 部门负责人身份会显示在右上角下拉菜单顶部
