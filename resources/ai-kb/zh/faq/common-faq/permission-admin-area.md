---
id: common-faq.permission-admin-area.faq
title: 进不了管理员区
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 进不了管理员
  - 看不到系统设置
  - 没有管理后台
  - 我是管理员怎么进不去
  - 没有左上角设置入口
  - admin 入口不见
related_tools: []
related_pages: [user_dropdown]
prerequisites: []
negative:
  - 普通用户即使是部门负责人也不是系统管理员，看不到管理后台
  - 仅靠改前端路由地址访问 /manage 不能绕过权限，后端 API 全部拦截
  - 超级管理员（id=1）专属菜单连普通系统管理员也看不到
last_verified: v1.7.90
---

# 进不了管理员区

## 问题
个人头像下拉里没有「管理后台」「系统设置」入口，或菜单里少了「团队管理」「数据导出」「LDAP」「License」等管理员项。

## 原因
DooTask 后台访问分两级：

- **系统管理员（admin）**：`userIsAdmin = true`，看得到「系统设置」「团队管理」「APP 推送」「数据导出」「LDAP」「举报管理」「License Key」等
- **超级管理员（super-admin）**：仅注册的第一个账号（id=1），额外可改超管设置、看许可证激活等

部门负责人、项目负责人都不属于系统管理员，与管理员菜单无关。

## 解决
1. 让现任系统管理员进「团队管理 → 成员」找到你，给你勾上「管理员」标识
2. 你刷新页面或重新登录后，头像下拉会出现「系统设置」「管理后台」
3. 若该功能只对超级管理员开放（如重置 license），只能由 id=1 的账号操作
4. 全公司没人记得管理员账号 → 用根账号到容器内执行 `./cmd artisan dootask:reset-admin` 重置（需服务器权限）

参考 [[role-permission.grant-admin.howto]]，权限规则见 [[role-permission.admin.concept]] / [[role-permission.super-admin.concept]]。

## 不支持
- 没有「临时管理员」「申请代管」流程
- 系统管理员权限是开关式（true/false），不细分子模块（不能只让某人看 LDAP 不让看用户）
