---
id: messenger.entry.menu-map
title: 即时通讯入口
type: menu-map
feature: messenger
scope: end-user
locale: zh
aliases:
  - 即时通讯在哪
  - 怎么打开消息
  - 聊天入口
  - 消息中心
  - 聊天界面在哪
  - 打开消息面板
related_tools: [search_dialogs]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 即时通讯没有独立的子路由，所有会话共用一个左侧栏「消息」入口
  - 未登录用户无法访问消息模块
last_verified: v1.7.90
---

# 即时通讯入口

DooTask 的「即时通讯」（也叫「消息」「聊天」）是内置 IM 模块，所有单聊、群聊、项目讨论、任务讨论、机器人消息都在这里。

## 路径

- 桌面端 Web：左侧主导航栏「消息」图标（气泡形状）
- 桌面端 Electron：同 Web；可通过任务栏图标小红点提示未读
- 移动端：底部 Tabbar「消息」
- 全局搜索：顶部搜索框可直接搜会话名 / 联系人 / 历史消息内容

打开后页面左侧是「会话列表」（按置顶时间 + 最后活跃时间倒序），右侧是当前会话的消息流。

## 权限要求

- 所有登录用户可见
- 临时账号（temp）无法主动创建群组，但可参与项目讨论 / 任务讨论
- 全员群、部门群由系统自动维护，普通成员不能解散

## 不支持

- 即时通讯模块不支持完全隐藏，仅能逐个隐藏单条会话（隐藏不等于退出）
- 不支持游客 / 未登录访问
