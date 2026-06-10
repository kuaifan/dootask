---
id: mobile-client.update.howto
title: 升级移动端 App
type: howto
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 移动端升级
  - App 升级
  - 手机端更新
  - 新版本 App
  - 怎么升级 App
  - 移动端有新版本
related_tools: []
related_pages: []
prerequisites: []
negative:
  - App 内通常不直接下载安装包,只引导用户去商店
  - 自部署版本可能强制要求最低 App 版本,过旧版本会被服务端拒绝
  - iOS 不支持侧载,只能走 App Store / TestFlight / 企业证书
last_verified: v1.7.90
---

# 升级移动端 App

## 升级方式概览
移动端不像桌面端有 `electron-updater` 自动下载,升级走「应用市场更新」或「重新下载安装包」。

## iOS

### App Store 升级
1. 打开 App Store → 头像 → 滚到「可用更新」
2. 找到 DooTask → 点「更新」
3. 也可以打开 DooTask 在 App Store 的页面手动点更新

### TestFlight(测试版)
1. 打开 TestFlight App
2. 列表里 DooTask 旁边显示「更新」时点击
3. TestFlight 版有效期 90 天

### 自动更新
设置 → 「App Store」→ 打开「App 更新」开关,系统会自动后台升级。

## Android

### Google Play 升级
1. Google Play → 头像 → 「管理应用和设备」→「可用更新」
2. 找到 DooTask 点「更新」

### 应用商店升级(国内)
1. 各品牌应用商店(华为 / 小米 / OPPO 等)的「我的」→「更新管理」
2. 找到 DooTask 点更新

### apk 直装升级
若用 apk 安装(私有部署):
1. 从公司渠道下载新版 apk
2. 直接安装新版会自动覆盖旧版(版本号高于已装版本)
3. 安装权限不足请到系统设置允许该浏览器 / 文件管理器作为「未知来源」

## App 内提示
登录后若服务端检测到客户端版本过低,可能弹出「检测到新版本,请升级」提示。点提示会引导到对应商店或下载链接。

## 看当前版本
- 我的 → 「关于」/「设置」→「版本号」
- iOS 也可在「设置 → 通用 → iPhone 储存空间 → DooTask」看版本

## 升级后数据
- 个人配置 / 登录令牌保留
- 不会丢失聊天记录(数据在服务器)
- 推送 token 通常会重新注册一次

## 不支持
- 不支持「App 内一键下载新安装包」(走系统商店或浏览器)
- iOS 不支持任何形式的侧载
- 没有统一的 Android 更新流程:各厂商商店流程不同,以各自商店为准
