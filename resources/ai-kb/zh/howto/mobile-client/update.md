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
  - 检查更新
  - 发现新版本
related_tools: []
related_pages: []
prerequisites: []
negative:
  - App 内不直接下载安装包:iOS 跳 App Store,Android 用浏览器下载 apk
  - 自部署版本可能强制要求最低 App 版本,过旧版本会被服务端拒绝
  - iOS 不支持侧载,只能走 App Store / TestFlight / 企业证书
last_verified: v1.9.66
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

## App 内检查更新
新版 App 每天回到前台时自动检查一次,有新版本时弹出「发现新版本」:
- 「立即更新」:iOS 跳转 App Store 的 DooTask 页面;Android 用系统浏览器打开官方 apk 下载地址,下载完点开安装
- 「以后再说」:关闭提示,第二天回到前台会再次提醒
- 版本来源:iOS 查 App Store 上架版本,Android 查官网(download.dootask.com)最新安装包
- 只在官方版本高于本机版本、且不高于当前连接的服务器版本时提示(与桌面端一致:服务器未升级时不提示,避免新版 App 调用旧服务器接口出错)
- 网络失败或获取不到服务器版本时不提示

手动检查:设置 →「版本」一行右侧点「检查更新」,已是最新会提示「已是最新版本」;官方有新版本但服务器版本较低时提示「当前服务器版本较低，请等待服务器升级后再更新」(自部署需管理员先升级服务器);可以更新时弹出同样的提示。

## 看当前版本
- 设置 →「版本」(格式:版本号 (构建号)),点击可查看服务器与客户端版本
- iOS 也可在「设置 → 通用 → iPhone 储存空间 → DooTask」看版本

## 升级后数据
- 个人配置 / 登录令牌保留
- 不会丢失聊天记录(数据在服务器)
- 推送 token 通常会重新注册一次

## 不支持
- 不支持「App 内一键下载新安装包」(走系统商店或浏览器)
- 不支持跳过某个版本的提示,也没有强制更新
- iOS 不支持任何形式的侧载
- 没有统一的 Android 更新流程:各厂商商店流程不同,以各自商店为准
