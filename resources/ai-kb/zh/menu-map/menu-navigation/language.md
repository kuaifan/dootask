---
id: menu-navigation.language.menu-map
title: 切换语言入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 切换语言
  - 换语言在哪
  - 改成英文
  - 改成中文
  - language switch
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 切换后部分页面需要刷新才完全生效
  - 后端推送通知文本受推送时的当前语言影响，不会回填
last_verified: v1.7.90
---

# 切换语言入口在哪

## 路径
- 桌面端：右上角头像 →「个人设置」→「语言设置」子页
- 桌面端 URL：`/manage/setting/language`
- 移动端：底部 Tabbar「我的」→「设置」→「语言设置」
- 登录页：右上角语言切换器（未登录也可切）
- 快捷键：无

## 支持的语言
- 简体中文（zh）
- 繁体中文（zh-CHT）
- 英文（en）
- 越南语 / 韩语 等（视部署版本）

## 权限要求
- 所有用户（含未登录）可切换
- 个人选择存到当前账号 + 浏览器 localStorage

## 相关
- 详细步骤：[[user-settings.language.howto]]
- 个人设置入口：[[user-settings.entry.menu-map]]
