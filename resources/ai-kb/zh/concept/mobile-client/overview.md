---
id: mobile-client.concept
title: 移动端是什么
type: concept
feature: mobile-client
scope: end-user
locale: zh
aliases:
  - 移动端
  - 手机端
  - App
  - 手机版
  - 移动版
  - 手机怎么用
  - iOS 客户端
  - 安卓客户端
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 移动端不是网页直接套壳,是基于 EEUI 框架的原生混合 App
  - 移动端不支持桌面端的全局快捷键 / 截图工具 / 多窗口
  - 移动端不能改服务器地址(首次登录设置后存住,需重置才能切换)
last_verified: v1.7.90
---

# 移动端是什么

## 定义
DooTask 移动端是 iOS 与 Android 双平台的原生 App,基于 EEUI 框架开发,内置 WebView 渲染部分页面、原生组件实现导航 / 弹窗 / 推送。用户代理(UA)包含 `eeui` 标识,服务端据此识别请求来自 App。

## 关键属性
- **跨平台**:iOS 与 Android 各有独立安装包
- **数据互通**:与网页端、桌面端共用同一份后端 API 和账号
- **原生体验**:底部 Tabbar、原生手势、原生输入法、原生推送
- **小巧专注**:核心是消息 + 任务 + 通知,功能集合比桌面端轻量

## 与桌面 / 网页端的差异
- 导航形态:移动端用底部 Tabbar(消息 / 任务 / 应用 / 我的),桌面端用左侧栏
- 输入习惯:发送按钮 / 换行按钮在移动端可切换(键盘上的「发送」是发还是换行)
- 推送通道:走 UMENG 系统推送([[mobile-client.notify.concept]]),不是 WebSocket 自送
- 富文本编辑器在小屏上做了简化

## 设备识别
- 前端通过 UA `/eeui/i` 检测 `$isEEUIApp`,据此走移动端专属逻辑
- 服务端调用别名注册接口(`/api/users/umeng/alias`)记录该设备 ID,用于推送路由

## 何时选移动端
- 出差 / 通勤时收消息和处理任务
- 配合桌面端使用,做「随身确认」型操作
- 需要原生推送即时到达

## 不支持
- 不支持复杂富文本编辑(超长公式、表格嵌套)
- 不支持白板、流程图、思维导图等画布类应用的编辑模式
- 不支持桌面端的截图工具 / 多窗口
