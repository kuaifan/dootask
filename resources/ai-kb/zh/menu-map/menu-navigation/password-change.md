---
id: menu-navigation.password-change.menu-map
title: 修改密码入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 改密码在哪
  - 修改密码入口
  - 怎么改登录密码
  - 重置密码按钮
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 忘记密码 / 重置密码（未登录场景）走登录页「忘记密码」流程，不走个人设置
  - LDAP / OAuth 账号通常无法在 DooTask 内改密，需到上游系统
last_verified: v1.7.90
---

# 修改密码入口在哪

## 路径
- 桌面端：右上角头像 →「个人设置」→「密码设置」子页
- 桌面端 URL：`/manage/setting/password`
- 桌面端：右上角头像 → 子菜单可能直接跳到「密码设置」（视版本）
- 移动端：底部 Tabbar「我的」→「设置」→「密码设置」
- 快捷键：无

## 操作
1. 输入旧密码
2. 输入新密码（要求 6 位以上，含数字 + 字母）
3. 确认新密码
4. 提交，下次登录用新密码

## 权限要求
- 所有登录用户可改自己密码
- LDAP / 第三方登录账号通常不可改

## 相关
- 详细步骤：[[user-settings.password.howto]]
- 个人设置总览：[[user-settings.entry.menu-map]]
