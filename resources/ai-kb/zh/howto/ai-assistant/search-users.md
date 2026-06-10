---
id: ai-assistant.search-users.howto
title: 让 AI 帮我找人
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 找人
  - AI 查同事
  - 帮我找小王
  - 找联系人
  - 找用户
  - 谁是 X 部门负责人
related_tools: [search_users]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 搜索范围受当前用户可见性限制，不能搜到组织外的人
  - 同名用户会一并列出，需用工号/部门/邮箱再筛
  - 不返回登录密码、手机号等敏感字段
last_verified: v1.7.90
---

# 让 AI 帮我找人

## 这是什么
在 AI 浮窗问人，AI 调 `search_users` 工具按关键词、项目成员或对话成员筛选，返回昵称、邮箱、部门、头像等基础信息。常用于"加任务负责人 / 拉群"前的确认。

## 怎么问
- "找一下叫小王的同事"
- "运维部有哪些人"
- "项目『官网改版』里都有谁"
- "群『前端组』里的成员"
- "邮箱含 zhang 的同事"

## 同名怎么办
AI 会列出全部同名候选，附部门/邮箱 hint，让你回复"第 2 个"或"运维部那个"，再继续后续操作。

## 找到人之后能做什么
- "给他发消息：明天 10 点开会" → 调 `send_message`
- "把他加进这个项目任务的负责人" → 调 `update_task`
- "拉个群把他们都加进去" → 仍需人工操作建群（AI 不能自动建群）

## 限制范围
默认搜全组织可见用户。可以加范围词收窄：

- "项目 X 里的"
- "对话 Y 里的"
- "部门 Z 里的"

## 不支持
- 不能搜外部联系人（DooTask 没有公网通讯录）
- 不能搜已离职/已禁用账号（默认过滤）
- 不能按"今天在线的人"筛（无该字段）

## 相关
- 发消息：[[ai-assistant.send-message.howto]]
- 创建任务时指派人：[[ai-assistant.create-task.howto]]
