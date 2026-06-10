---
id: micro-app.permission.concept
title: 微应用权限
type: concept
feature: micro-app
scope: end-user
locale: zh
aliases:
  - 微应用权限
  - 谁能用微应用
  - 微应用对谁可见
  - 微应用登录态
  - 微应用 SSO
  - 微应用 token
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 没有「整体禁用所有微应用」的开关，按单个插件 / 单条菜单控制
  - 微应用内部的细粒度权限（如「谁能改 OKR」）由插件自身实现，不归 DooTask 主程序管
  - 临时帐号 / 受限身份的功能限制对微应用同样生效（如禁止创建群、禁止文件分享）
last_verified: v1.7.90
---

# 微应用权限

## 定义
微应用权限分两层：**主程序层**控制谁能看到 / 进入这个微应用；**插件内部**控制进入后能做什么。前者由 DooTask 主程序按用户身份 + 菜单配置过滤，后者由每个插件自行实现。

## 主程序层判断
- **可见范围 visible_to**：菜单注册时声明
  - `all` — 所有成员可见
  - `admin` — 仅系统管理员可见（应用中心走「管理员」分区）
- **管理员判断**：基于 `userIsAdmin`，对应 `User::isAdmin()`
- **菜单位置 location**：决定渲染在哪里（应用中心 / 主导航 / 管理员区）
- **临时帐号限制**：受限身份在主程序的所有限制（禁止创建群、禁止文件分享等）同样作用于微应用调用主程序接口

## 登录态传递
- 微应用通过 URL `?token={user_token}` 拿到当前用户身份
- 微应用自己再调用 DooTask 后端时携带该 token
- 后端会校验 token 关联的用户身份，决定是否放行接口

## 插件内部权限
- 由插件自行实现，DooTask 不强制规范
- 比如 OKR 内部有「目标负责人」「KR 协作者」概念
- 比如 approve 内部有「审批人」「抄送人」「发起人」概念
- 主程序对此不做拦截，只负责把用户身份传过去

## 与系统应用对比
- 系统应用走主程序权限体系，由 [[role-permission.permission-denied.faq]] 覆盖
- 微应用主程序层只控可见性，业务权限由插件自管
