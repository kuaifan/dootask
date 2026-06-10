---
id: app-admin.checkin-setting.howto
title: 签到规则配置入口
type: howto
feature: app-admin
scope: admin
locale: zh
aliases:
  - 签到设置在哪
  - 签到规则配置
  - 打卡规则
  - 配置考勤
  - 修改签到时间
  - 添加 wifi 签到
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 签到「记录查看」对所有用户开放，但「设置」入口仅管理员
  - 签到规则改完立即生效，不回填历史记录
last_verified: v1.7.90
---

# 签到规则配置入口

## 是什么
签到规则配置（签到设置）让管理员设定考勤时段、wifi / 位置范围、人脸识别开关、打卡提醒等。属于管理员功能，但入口被嵌在面向全员的「签到打卡」抽屉里。

## 入口
- 桌面端：左侧栏「应用」→「常用」分区 →「签到打卡」卡片 → 抽屉右上角「签到设置」链接
- 移动端竖屏：底部 Tabbar「应用」→「签到打卡」→ 抽屉右上角「签到设置」
- 「签到设置」链接仅 `userIsAdmin = true` 时显示

## 设置抽屉内能做什么
- 设定上下班签到时段（多组班次）
- 配置 wifi 名 / Mac 地址 / GPS 范围
- 启用人脸识别（依赖 face 插件）
- 配置提醒推送时间
- 是否允许补卡、是否计算迟到 / 早退

## 详细规则
签到具体规则字段、wifi/face 实现、补卡流程见 checkin feature 的 chunk（已有 [[checkin.setting.howto]] 等）。

## 不支持
- 不支持按部门设置不同班次规则，全员同一套
- 不支持自助修改打卡记录（仅管理员可补卡）
