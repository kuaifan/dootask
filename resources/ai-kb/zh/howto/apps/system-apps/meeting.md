---
id: app-system.meeting.howto
title: 在线会议入口
type: howto
feature: app-system
scope: end-user
locale: zh
aliases:
  - 会议在哪
  - 在线会议入口
  - 创建会议
  - 加入会议
  - 会议应用
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 此入口仅打开会议导航抽屉，不直接发起或加入
  - 「会议设置」入口仅管理员可见，普通成员看不到
last_verified: v1.7.90
---

# 在线会议入口

## 是什么
应用中心「在线会议」卡片对应 `meeting` 系统应用，点击后打开会议导航抽屉，提供创建会议、加入会议（输入会议号）、查看历史会议等子入口。

## 入口
- 桌面端：左侧导航「应用」→「在线会议」卡片
- 移动端：底部 Tabbar「应用」→「在线会议」
- 抽屉右上角「会议设置」入口仅管理员可见

## 操作步骤
1. 应用中心 → 点「在线会议」
2. 抽屉内可选：
   - 「创建会议」：发起一场新会议（触发事件 `addMeeting / type=create`）
   - 「加入会议」：输入会议号加入（触发事件 `addMeeting / type=join`）
   - 查看历史 / 进行中的会议列表
3. 点条目可查看详情

## 不支持
- 没有快捷键直接创建会议
- 此入口不展示日历集成视图，会议日程在日历模块查看

## 详细会议功能
会议本身（音视频、共享、邀请等）由独立 feature 维护，本 chunk 只承担入口指引职责。
