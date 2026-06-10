---
id: menu-navigation.checkin-rule.menu-map
title: 签到规则配置入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 签到规则在哪
  - 打卡规则配置
  - 设置签到时间
  - WiFi 签到怎么配
  - 人脸签到怎么开
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
negative:
  - 普通成员看不到「签到设置」按钮，只看到自己的签到面板
  - 修改签到规则后已生成的签到记录不会回溯
last_verified: v1.7.90
---

# 签到规则配置入口在哪

## 路径
- 桌面端：右上角头像 →「系统设置」→ 左侧子菜单「签到」
- 桌面端：左侧栏「应用」→「签到打卡」→ 右上角「签到设置」按钮（管理员可见）
- 桌面端 URL：`/manage/setting-system?tab=checkin`（视版本）
- 移动端：管理员菜单内进系统设置

## 能配置
- 上下班时间 / 弹性窗口
- 签到方式：手动 / WiFi MAC / 地理围栏 / 人脸
- 提醒推送时间
- 是否允许员工自行修改人脸图 / MAC
- 节假日规则

## 权限要求
- `admin` 才能看到入口
- 改完立即生效（对未来签到）

## 相关
- 系统设置入口：[[menu-navigation.system-setting.menu-map]]
- 用户侧打卡入口：[[menu-navigation.checkin.menu-map]]
- 规则概念：[[checkin.rule.concept]]
