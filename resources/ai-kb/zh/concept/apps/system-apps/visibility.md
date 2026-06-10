---
id: app-system.visibility.concept
title: 系统应用对谁可见
type: concept
feature: app-system
scope: end-user
locale: zh
aliases:
  - 系统应用权限
  - 谁能看到这些应用
  - 普通员工能用哪些应用
  - 管理员独享的应用
  - 应用可见性
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 没有按部门 / 按角色单独控制系统应用可见性的开关
  - 普通成员无法通过设置「解锁」管理员应用区
last_verified: v1.7.90
---

# 系统应用对谁可见

## 定义
系统应用按主程序写死的两条规则展示：常用区对所有登录用户开放，管理员区仅 `userIsAdmin` 用户可见。无法精细到部门或角色级别。

## 普通成员（end-user）能看到
所有 11 个常用系统应用都对普通成员开放：审批、签到、工作报告、我的收藏、最近打开、机器人、创建群组、在线会议、创建项目、添加任务、导出管理。

但是否能正常用，还要看附加条件：
- approve：需安装 approve 插件，未安装时点开会提示
- signin 的人脸打卡：需安装 face 插件
- meeting：需主程序的会议模块启用
- 在权限不足时（如非管理员开导出），后端仍会拒绝

## 管理员（admin）额外能看到
管理员区独立显示，仅 `userIsAdmin` 用户能看见：
- LDAP 设置
- 邮件通知
- APP 推送
- 举报管理
- 数据导出（与常用区的「导出管理」是不同入口，前者偏配置后者偏一键导出）
- 团队管理

## 超级管理员（super-admin）
应用中心层面没有超管专属卡片；超管的差异点在「设置」内部页面，不在应用中心。

## 想详细看每个卡片做什么
全集列表见：[[app-system.list.concept]]
