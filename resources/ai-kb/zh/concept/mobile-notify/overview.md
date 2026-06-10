---
id: mobile-notify.concept
title: 移动端通知是什么
type: concept
feature: mobile-notify
scope: end-user
locale: zh
aliases:
  - 移动端通知
  - 手机通知
  - iOS 通知
  - Android 通知
  - APP 通知
  - 移动端推送
related_tools: []
related_pages: []
prerequisites:
  - 已下载并登录 DooTask 移动端 APP（iOS / Android）
negative:
  - 移动端通知由两部分组成：离线友盟推送 + 前台 APP 内浮层；二者不能任选其一
  - 未授予系统通知权限时，连前台浮层通知都不显示（依赖 setVibrate 系统调用会被拒）
  - DooTask 不内嵌任何「不推送某类消息」的细粒度开关，免打扰只能按会话或时段
last_verified: v1.7.90
---

# 移动端通知是什么

## 定义
移动端通知是 DooTask iOS / Android APP 收到新消息的两种提醒形式：APP 在后台或被关闭时通过友盟下发系统通知栏推送（[[push-notice.concept]]），APP 在前台运行时则在屏幕顶部展示一段浮层通知。

## 两种形态
| 形态 | 触发场景 | 实现 |
|---|---|---|
| 系统通知栏推送 | APP 后台 / 退出 / 锁屏 | 友盟 UMENG 通道，详见 [[push-notice.concept]] |
| APP 内浮层 | APP 前台运行 + 不在该会话 | `MobileNotification` 组件，屏幕顶部下拉横幅，点击进入会话 |

## APP 内浮层属性
- 显示头像 + 发送者昵称 + 消息摘要
- 默认显示 6 秒后自动收起；可手动下拉关闭
- 同时调用原生 `setVibrate` 触发短振动
- 点击会话直接打开

## 与桌面端通知的区别
- 移动端有「APP 后台 → 系统推送」（友盟），桌面端没有
- 桌面端通知可在通知里直接快速回复，移动端浮层不支持快捷回复
- 移动端推送会触发振动 + 角标，桌面端只闪烁/角标，不振动

## 相关
- 友盟通道与别名机制：[[push-notice.alias.concept]]
- 触发条件细节：[[push-notice.scenarios.concept]]
- 系统通知权限：[[mobile-notify.permission.faq]]
- 关闭单个会话的免打扰：[[push-notice.silent.howto]]
