---
id: app-admin.push.howto
title: APP 推送入口
type: howto
feature: app-admin
scope: admin
locale: zh
aliases:
  - APP 推送怎么打开
  - 手机推送配置
  - UMENG 设置
  - DooPush 设置
  - 友盟推送
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
  - 已取得对应推送服务的应用凭证
negative:
  - APP 推送不作用于 Web 端和桌面端
  - 配置页没有测试推送和事件订阅按钮
last_verified: v1.9.66
---

# APP 推送入口

## 是什么
APP 推送应用让管理员配置 DooTask 移动端通知：新版 App 使用 DooPush，旧版 App 使用友盟 UMENG，两者可同时开启。任务和审批等提醒通过会话消息进入推送链路。

## 入口
- 桌面端：左侧「应用」→「管理员」分区 →「APP 推送」卡片，打开配置抽屉。
- 也可从左上角头像 →「系统设置」→ 左侧「APP 推送」进入相同表单。

## 配置项
- 「友盟推送」区：独立开关、iOS / Android 各自的 Appkey 和 App Master Secret。
- 「DooPush 推送」区：独立开关、App ID、密码形式输入的 App Secret、服务地址。
- 服务地址留空使用 https://doopush.com/api/v1。
- 点击底部「提交」保存；详细字段及设备绑定见 [[push-notice.config.howto]]。

## 不支持
- APP 推送不作用于 Web 端和桌面端。
- 配置页没有测试推送和事件订阅按钮。
- 推送送达依赖推送服务可达和手机通知权限，不能保证所有设备即时送达。
