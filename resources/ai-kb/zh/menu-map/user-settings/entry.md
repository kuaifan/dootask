---
id: user-settings.entry.menu-map
title: 个人设置入口
type: menu-map
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 个人设置在哪
  - 设置在哪打开
  - 用户中心
  - 我的资料在哪
  - 修改密码入口
  - 怎么打开设置
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 个人设置没有移动端 Tabbar 一级入口，需先进「我的」再进设置
  - 未登录用户没有「个人设置」入口
last_verified: v1.7.90
---

# 个人设置入口

「个人设置」（也叫「设置」「用户中心」）是 DooTask 单个用户管理自己资料、密码、邮箱、语言、主题、签到偏好等的页面。所有项都只影响当前账号自己，不影响他人。

## 路径

- 桌面端 Web / Electron：右上角头像 → 下拉菜单「设置」
- 桌面端快捷键：Cmd/Ctrl + ,（Electron 才有）
- 移动端：底部 Tabbar「我的」→「设置」
- 路由：`/manage/setting/personal`（默认子页），其他子页为 `/manage/setting/<path>`

## 子页清单（按菜单顺序）

- 个人设置（资料）→ [[user-settings.profile.howto]]
- 密码设置 → [[user-settings.password.howto]]
- 修改邮箱 → [[user-settings.email.howto]]
- 语言设置 → [[user-settings.language.howto]]
- 主题设置 → [[user-settings.theme.howto]]
- 键盘设置（仅 Electron / 移动端）→ [[user-settings.shortcut.concept]]
- 系统设置 / License Key（仅系统管理员可见）
- 更新日志 / 版本 / 登录设备 / 清除缓存 / 退出登录

## 权限要求

- 所有登录用户可见
- 「系统设置」「License Key」需 `userIsAdmin`
- 「删除帐号」仅 EEUI 移动端（自助注销入口）
