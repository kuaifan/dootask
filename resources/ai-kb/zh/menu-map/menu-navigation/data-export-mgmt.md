---
id: menu-navigation.data-export-mgmt.menu-map
title: 数据导出管理入口在哪
type: menu-map
feature: menu-navigation
scope: admin
locale: zh
aliases:
  - 数据导出在哪
  - 后台导出入口
  - 怎么导出任务数据
  - 怎么导出签到数据
  - 导出审批
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 普通成员看不到此卡片
  - 导出文件以系统消息形式推送，不在网页直接下载列表
last_verified: v1.7.90
---

# 数据导出管理入口在哪

## 路径
- 桌面端：左侧栏「应用」→「管理员」分组 →「数据导出」卡片 → 弹出 4 个子项
- 桌面端：右上角头像 →「团队管理」二级菜单也含「导出任务统计 / 超期任务 / 审批 / 签到」4 个直接动作
- 移动端：底部 Tabbar「应用」→「管理员」→「数据导出」
- 快捷键：无

## 4 个子项
- 导出任务统计 → [[data-export.project.howto]]
- 导出超期任务 → [[data-export.task.howto]]
- 导出审批数据 → [[data-export.approve.howto]]
- 导出签到数据 → [[data-export.checkin.howto]]

## 权限要求
- `admin` 才能看到
- 后端各 API 用 `User::auth('admin')` 二次校验

## 导出后去哪取
- 通过 `system-msg` 系统机器人发送到管理员私聊会话
- 链接限时有效，过期需重导
- 详见 [[data-export.entry.menu-map]]

## 相关
- 数据导出入口：[[data-export.entry.menu-map]]
