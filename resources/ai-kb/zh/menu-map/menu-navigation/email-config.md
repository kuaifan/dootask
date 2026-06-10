---
id: menu-navigation.email-config.menu-map
title: 邮件配置入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 邮件配置在哪
  - SMTP 设置入口
  - 注册邮件怎么配
  - 邮件服务器配置
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
negative:
  - 个人邮件通知偏好在「个人设置」改，不在这里
  - 系统未配 SMTP 时所有「邮件验证」「忘记密码」等流程都失效
last_verified: v1.7.90
---

# 邮件配置入口在哪

## 路径
- 桌面端：右上角头像 →「系统设置」→ 左侧子菜单「邮件」
- 桌面端 URL：`/manage/setting-system?tab=email`（视版本）
- 移动端：通常不展示

## 能配置
- SMTP 服务器、端口、TLS 开关
- 发件人地址 + 显示名
- 注册验证邮件
- 未读消息提醒邮件（频率、阈值）
- 系统告警接收人

## 权限要求
- `admin` 才能看到
- 改后建议先发测试邮件验证

## 相关
- 系统设置入口：[[menu-navigation.system-setting.menu-map]]
- 用户侧通知偏好：[[menu-navigation.notification-setting.menu-map]]
- 邮件通知功能：[[email-notice.config.howto]]
