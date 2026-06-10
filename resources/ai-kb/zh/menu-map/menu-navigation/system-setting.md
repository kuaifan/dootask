---
id: menu-navigation.system-setting.menu-map
title: 系统设置入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 系统设置在哪
  - 后台在哪
  - 管理后台入口
  - 系统配置怎么进
related_tools: []
related_pages: []
prerequisites:
  - 当前账号必须是系统管理员（identity 含 admin）
negative:
  - 普通成员菜单中没有该项
  - 部分子项（License）仅超级管理员可改
  - 移动端通常不展示系统设置入口
last_verified: v1.7.90
---

# 系统设置入口在哪

## 路径
- 桌面端：右上角头像下拉菜单 →「系统设置」（管理员可见）
- 桌面端 URL：`/manage/setting-system`
- 桌面端快捷键：无直达
- 移动端：通常不显示，建议在桌面端 / 浏览器操作

## 主要子菜单
- 通用 / 邮件 / 会议 / AI 设置 / AI 机器人
- 签到 / APP 推送 / 第三方接入（LDAP / OAuth）
- 文件 / 任务优先级 / 列模板 / License

## 权限要求
- `admin` 才能看到入口
- 部分项要超级管理员（id=1）

## 相关
- 系统设置详细子菜单：[[system-setting.entry.menu-map]]
- 通用设置：[[system-setting.general.howto]]
