---
id: common-faq.account-multi-device-conflict.faq
title: 多设备登录冲突 / 一边登另一边掉线
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 多设备冲突
  - 一登就掉
  - 另一边掉线
  - 多端互踢
  - 手机登了电脑掉了
  - 同账号多设备
  - 顶号
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 默认不互踢，但同类终端会限制单设备
  - 没有「禁止同时多端」开关，端类型隔离是默认行为
  - 强制下线某设备的入口在「个人设置 → 已登录设备」
last_verified: v1.7.90
---

# 多设备登录冲突 / 一边登另一边掉线

## 问题
- 桌面端登录后浏览器就掉线了
- 手机一登录，平板就被踢
- 两台电脑同时登同一账号会互踢

## DooTask 多端策略
登录设备按类型分桶：

| 端类型 | 同时在线 |
|---|---|
| Web 浏览器 | 多浏览器 / 多 Tab 互不踢 |
| 桌面端 Mac | 同类只能一台 |
| 桌面端 Win | 同类只能一台 |
| iOS App | 同类只能一台 |
| Android App | 同类只能一台 |

跨类型不互踢：Mac 桌面端 + 手机 App + 网页可同时在线。同类互踢：第二台 Win 电脑登录会踢掉第一台。

## 解决

**保留某台设备不被踢**
- 不要在另一台同类型设备上登录
- 登录前先在「已登录设备」检查现有会话

**看哪些端在线**
1. 个人头像 → 「个人设置」
2. 「已登录设备」标签
3. 列表展示设备类型、最近活跃时间、IP

**强制下线某设备**
- 「已登录设备」点对应设备的「下线」
- token 立即失效，强制重新登录

**误在公共设备登录**
- 在常用设备进「已登录设备」找到它 → 「下线」
- 顺便改密码 → [[common-faq.account-forget-password.faq]]

## 不支持
- 不支持同账号同终端类型双开
- 不支持下线时弹通知给被踢端
- 不支持「这台机器永不被踢」白名单

## 相关
- 设备管理：[[user-account.device.howto]]
- 多端不同步：[[common-faq.sync-multi-device.faq]]
