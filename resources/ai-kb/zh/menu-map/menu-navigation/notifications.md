---
id: menu-navigation.notifications.menu-map
title: 通知中心入口在哪
type: menu-map
feature: menu-navigation
scope: end-user
locale: zh
aliases:
  - 通知在哪
  - 系统通知怎么看
  - 通知中心入口
  - 系统消息在哪
related_tools: []
related_pages: [messenger]
prerequisites: []
negative:
  - DooTask 没有独立的「通知中心」页面，所有系统通知都通过系统机器人单聊推送
  - 没有桌面端通知小铃铛入口
last_verified: v1.7.90
---

# 通知中心入口在哪

## 路径
DooTask 的"通知"以系统机器人单聊形式存在，没有独立通知中心。

- 桌面端：左侧栏「消息」→ 列表里找系统机器人单聊：
  - 「任务提醒」（task-alert@bot.system）
  - 「签到机器人」（check-in@bot.system）
  - 「审批助手」（approve@bot.system）
  - 「系统消息」（system-msg@bot.system）
- 桌面端：搜索框输入机器人名（如「任务提醒」）直达
- 移动端：底部 Tabbar「消息」→ 同样找系统机器人
- 浏览器原生通知 / 桌面端弹窗也会同步推送

## 推送渠道
- 浏览器：浏览器通知 API（需授权）
- 桌面端：系统通知中心 + 任务栏角标
- 移动端：APP 推送（详见 [[push-notice.config.howto]]）
- 邮件：[[email-notice.config.howto]]

## 权限要求
- 所有登录用户可见
- 通知偏好开关在「个人设置」中

## 相关
- 通知偏好：[[menu-navigation.notification-setting.menu-map]]
