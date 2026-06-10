---
id: common-faq.sync-multi-device.faq
title: 多端不同步（桌面端、Web、手机）
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 多端不同步
  - 手机和电脑数据不一样
  - 桌面端没刷新
  - 多设备数据不一致
  - 多端登录数据不同
  - 手机看到的和网页不一样
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 多端不存在「本地副本同步」概念，所有数据实时从服务器拉
  - 数据本身不会因为多端登录冲突而被复制或合并
  - 某些缓存（如未读数、消息列表）需要重新进入页面才会刷新
last_verified: v1.7.90
---

# 多端不同步（桌面端、Web、手机）

## 问题
同一个账号在桌面端 / 浏览器 / 手机上同时登录时，三端看到的数据不一致：消息已读未读、任务变化、未读数等不同步。

## 原因
DooTask 所有数据都从同一个后端服务器读，不存在本地副本同步问题。但每端的实时性独立：

- 每端各自维持一条 WebSocket 长连接
- 每端各自管理本地 store 缓存
- 后端推送变更时，离线 / 断网的端会丢失推送
- 已读状态不会在端之间联动同步（消息在 A 端读了，B 端可能仍显示未读，直到 B 端进入该会话）

桌面端（Electron）和移动端 App 在后台时 WebSocket 也可能被系统冻结。

## 解决
1. **强制刷新**：浏览器 F5；桌面端右键托盘「重启」；移动端 App 杀进程重开
2. **确认 WebSocket 在线**：右下角连接状态显示绿色
3. **检查多端实际登录**：「个人设置 → 已登录设备」看哪些端在线
4. **未读数差异**：进入对应会话 / 任务详情即可重算

## 不支持
- 不支持设备间「同步本地草稿 / 输入中的内容」
- 不支持端到端 P2P，所有数据都走服务器中转

## 相关
- 设备管理：[[user-account.device.howto]]
- 多设备冲突：[[common-faq.account-multi-device-conflict.faq]]
