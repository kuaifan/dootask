---
id: mobile-client.download.howto
title: 下载移动端 App
type: howto
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 下载移动端
  - App 在哪下
  - 手机怎么装
  - 安装 App
  - 苹果商店搜什么
  - 应用宝下载
  - 安卓 apk
  - 移动端安装
related_tools: []
related_pages: []
prerequisites: []
negative:
  - App 不内嵌服务器,首次启动需手动录入企业服务器地址
  - 国内 Android 渠道(华为/小米/OPPO 等)不一定全有,具体以分发为准
  - 通过浏览器下载 apk 安装需要在 Android 设置中允许「未知来源」
last_verified: v1.7.90
---

# 下载移动端 App

## 下载渠道

### iOS(iPhone / iPad)
- **App Store**:打开 App Store 搜索「DooTask」(可能因地区差异)
- **TestFlight**:有些团队提供 TestFlight 测试通道(需邀请链接)
- **企业分发**:通过 MDM 推送到企业设备(需 IT 配合)

### Android
- **Google Play**:海外用户走 Google Play 搜「DooTask」
- **官方 apk**:从 DooTask 官网或公司内网下载 apk
- **应用商店**:部分国内应用商店(华为 / 小米 / OPPO / vivo / 应用宝)可能上架,以实际为准
- 公司私有部署常见做法:运维提供 apk 链接,扫码或在浏览器下载安装

## 安装步骤

### iOS
1. App Store 找到 → 点「获取」/「安装」(Touch ID/Face ID 验证)
2. 装完桌面出现 DooTask 图标

### Android(apk 直装)
1. 用浏览器下载 apk 文件
2. 点击 apk 安装,系统会提示「允许此来源安装应用」→ 打开开关
3. 完成安装

### Android(应用商店)
1. 商店搜索 DooTask → 点「安装」
2. 商店自动完成下载和安装

## 首次启动配置
1. 启动 App,首屏要求录入**服务器地址**(如 `https://dootask.公司域名/`)
2. 输入账号密码登录,见 [[mobile-client.login.howto]]
3. 同意推送通知请求,以便收到消息([[mobile-client.notify.concept]])

## 切换服务器
首次设置后服务器地址会保存。要切换:
1. 在 App 内退出登录(我的 → 退出登录)
2. 重新到登录页修改服务器地址
3. 再用新账号登录

## 不支持
- App 不能在同一设备同时登多个账号
- 不支持「应用内更新」自动下载 apk(走系统商店 / 手动下载,见 [[mobile-client.update.howto]])
- 国内分发渠道分布不固定,以最新官方说明为准
