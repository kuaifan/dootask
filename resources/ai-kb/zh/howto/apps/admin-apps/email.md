---
id: app-admin.email.howto
title: 邮件通知入口
type: howto
feature: app-admin
scope: admin
locale: zh
aliases:
  - 邮件通知怎么打开
  - SMTP 设置
  - 系统邮件
  - 通知邮件
  - 配置 SMTP
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 邮件通知关闭后任务 / 审批等业务邮件不发，但站内消息正常
  - 不支持每个用户单独换 SMTP 服务器，所有发件走同一组配置
last_verified: v1.7.90
---

# 邮件通知入口

## 是什么
邮件通知应用让管理员配置 DooTask 的发件 SMTP 服务器（用于注册验证、任务通知、审批提醒等系统邮件），并控制哪些事件触发邮件。属于管理员应用。

## 入口
- 桌面端：左侧栏「应用」→ 下方「管理员」分区 →「邮件通知」卡片
- 移动端竖屏：底部 Tabbar「应用」→「管理员」分区 →「邮件通知」
- 点击后右侧弹出 700 宽的「邮件通知」抽屉

## 抽屉内能做什么
- 配置发件人邮箱、SMTP 地址、端口、加密方式（SSL / TLS / 无）
- 配置 SMTP 账号 / 授权码
- 测试邮件发送（输入收件人 → 发送测试邮件）
- 选择哪些事件发邮件（注册验证、任务变更、审批提醒等）
- 开启 / 关闭整体邮件通知开关

## 详细配置
SMTP 参数说明与各邮件模板由 email-notice feature 的 chunk 覆盖（后续起草）。

## 不支持
- 不支持自定义邮件模板 HTML（模板内置）
- 不支持按部门 / 角色细粒度筛选收件人
