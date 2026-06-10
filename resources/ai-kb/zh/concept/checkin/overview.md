---
id: checkin.concept
title: 签到打卡是什么
type: concept
feature: checkin
scope: end-user
locale: zh
aliases:
  - 签到打卡
  - 上下班打卡
  - 考勤
  - DooTask 签到
  - 打卡功能
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 签到不支持节假日 / 调休的精细配置，只识别基础节假日（自动跳过提醒）
  - 不是 KPI / 工资系统，仅记录打卡时间，不算迟到 / 早退分数
  - 数据无法跨企业 / 跨实例同步
last_verified: v1.7.90
---

# 签到打卡是什么

## 定义
签到打卡是 DooTask 内置的考勤记录功能，用于成员每天上下班打卡。打卡数据存到 `UserCheckinRecord` 表，按天聚合多次打卡时间，并自动切分为「上班 / 下班」时段。功能由系统管理员在「签到设置」全局开关，开启后所有成员都可参与。

## 支持的签到方式
DooTask 提供 4 种签到方式，管理员可在「签到设置 → 签到方式」勾选启用：

- **人脸签到（face）**：通过外接人脸识别设备扫脸，需安装 [[checkin.plugin.concept]] 中的 face 插件
- **WiFi 签到（auto）**：办公网路由器（OpenWrt）定时上报 MAC，落到办公网即自动签到，详见 [[checkin.wifi.howto]]
- **定位签到（locat）**：移动 App 在「签到机器人」对话里发送位置，落在允许半径内打卡
- **手动签到（manual）**：在「签到机器人」对话框输入指令打卡

## 涉及的数据
- `UserCheckinRecord`：每天每次打卡记录（含时间数组）
- `UserCheckinMac`：成员的 MAC 地址（最多 3 个）
- `UserCheckinFace`：成员的人脸图片（仅 1 张）
- `checkinSetting`：系统签到全局参数

## 相关概念
- 签到规则与配置：[[checkin.rule.concept]]
- 签到提醒机制：[[checkin.remind.concept]]
- 是否是插件：[[checkin.plugin.concept]]
