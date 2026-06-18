---
id: checkin.plugin.concept
title: 签到是插件还是内置功能
type: concept
feature: checkin
scope: end-user
locale: zh
aliases:
  - 签到是插件吗
  - 签到要装吗
  - checkin 插件
  - 人脸签到是插件吗
  - 签到要不要安装
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 应用市场没有名为「checkin」或「签到」的独立插件
  - 卸载 face 插件不会影响 WiFi / 定位 / 手动签到
last_verified: v1.7.90
---

# 签到是插件还是内置功能

## 结论
签到打卡的**主体功能是 DooTask 内置的**，不需要单独安装插件。模型 (`UserCheckinRecord` / `UserCheckinMac` / `UserCheckinFace`)、签到机器人 (`check-in@bot.system`)、提醒任务 (`CheckinRemindTask`)、设置接口都打包在主程序里，开箱即用。

## 各签到方式的依赖
- **手动签到（manual）**：无依赖，主程序自带
- **WiFi 签到（auto）**：无插件依赖，只需要管理员在 OpenWrt 路由器执行一键安装脚本
- **定位签到（locat）**：无插件依赖，但需要管理员在「签到设置」配置百度 / 高德 / 腾讯地图 Key，且只支持移动端 App
- **人脸签到（face）**：**需要安装 face 插件**（应用市场搜「Face check-in」），并配套人脸识别硬件设备

## 关联应用市场
- `face` 插件：人脸识别后端服务，未装时人脸上传 / 现场刷脸都会失败（人脸签到详细说明随 face 应用知识库提供）
- `approve` 插件：影响提醒筛选——已请假 / 外出审批的成员不会收到缺卡提醒

## 不支持
- 没有第三方「考勤」插件取代内置签到
- 没法只装签到不装签到机器人（机器人是系统自动创建的）
- 主程序版本升级后签到能力随版本走，无独立版本号
