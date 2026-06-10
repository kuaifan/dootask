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
  - 移动端推送
  - 友盟推送
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
  - 需要已申请的友盟（UMENG）账号与 App Key/Secret
negative:
  - 推送仅作用于 DooTask 官方移动 App（iOS / Android），不支持 Web 端
  - 推送依赖友盟服务可达，私有化网络隔离环境下需自行评估可用性
  - 不支持改用其他推送服务商（如极光 / 个推）
last_verified: v1.7.90
---

# APP 推送入口

## 是什么
APP 推送应用让管理员配置移动端的推送服务（基于友盟 UMENG），用于消息、任务、审批等场景在 App 关闭时弹通知。属于管理员应用。

## 入口
- 桌面端：左侧栏「应用」→ 下方「管理员」分区 →「APP 推送」卡片
- 移动端竖屏：底部 Tabbar「应用」→「管理员」分区 →「APP 推送」
- 点击后右侧弹出 700 宽的「APP 推送」抽屉

## 抽屉内能做什么
- 填入 iOS 与 Android 各自的 UMENG App Key / App Master Secret
- 启用 / 关闭整体推送开关
- 选择哪些事件触发推送（新消息、任务分配、审批待办等）
- 测试推送（向当前管理员的 App 发送一条）

## 详细配置
UMENG 字段含义、各事件推送格式由 push-notice feature 的 chunk 覆盖（后续起草）。

## 不支持
- 关闭推送后用户端会立即停止收到通知，无延迟回调
- 不支持自定义推送声音 / 图标，全部走 App 默认
