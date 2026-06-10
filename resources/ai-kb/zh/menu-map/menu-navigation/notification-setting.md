---
id: menu-navigation.notification-setting.menu-map
title: 通知偏好设置入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 关闭通知在哪
  - 通知偏好设置
  - 静音消息在哪
  - 邮件通知怎么关
  - 推送怎么关
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 没有一站式「全部静音」开关，需要按渠道分别关
  - 关掉浏览器通知后桌面弹窗也会同时失效
last_verified: v1.7.90
---

# 通知偏好设置入口在哪

## 路径
- 桌面端：右上角头像 →「个人设置」→「键盘设置 / 通知设置」子页（视版本）
- 桌面端：浏览器原生通知 → 浏览器地址栏锁形图标 → 网站权限
- 桌面端 Electron：菜单栏 → 通知偏好（仅 Electron）
- 移动端：底部 Tabbar「我的」→「设置」→「通知 / 推送」
- 移动端原生：系统设置 → 通知 → DooTask APP
- 快捷键：无

## 可调项
- 浏览器 / 桌面端弹窗通知（on / off）
- 邮件通知（详见 [[email-notice.config.howto]]）
- 移动端 APP 推送（详见 [[push-notice.config.howto]]）
- 单个会话静音：消息列表中长按会话 →「免打扰」

## 权限要求
- 所有登录用户可改
- 部分系统级邮件（如管理员告警）由管理员设置

## 相关
- 邮件通知入口：[[email-notice.config.howto]]
- APP 推送入口：[[push-notice.config.howto]]
