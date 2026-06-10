---
id: project.dialog.concept
title: 项目聊天室（自动建群）
type: concept
feature: project
scope: end-user
locale: zh
aliases:
  - 项目群聊
  - 项目对话
  - 项目里聊天
  - 项目群
  - 自动建群
related_tools: [send_message, get_message_list]
related_pages: [project_detail, messenger]
prerequisites: []
negative:
  - 项目群成员同步自 ProjectUser，不能单独加非项目成员到群
  - 个人项目（[[project.personal.concept]]）无群（仅自己一人）
  - 群解散需要先删项目，否则无法手动解散
last_verified: v1.7.90
---

# 项目聊天室（自动建群）

## 定义
DooTask 团队项目（personal=0）创建时自动生成一个 WebSocketDialog 群聊，对应 `Project.dialog_id` 字段。所有项目成员（ProjectUser）自动加入群聊；项目成员变更（加 / 删 / 转让）时，群成员同步变更。

## 群成员同步规则
- 加项目成员 → 自动入群（[[project.member.howto]]）
- 删项目成员 → 自动出群
- 转让拥有者（[[project.transfer.howto]]） → 群 owner_id 同步换
- 群 role 字段镜像 ProjectUser.owner（0/1/2）

## 入口
- 桌面端：项目顶部「💬 项目对话」按钮 → 弹出对话面板
- 桌面端：左侧栏「消息」→ 找项目同名群聊
- 移动端：项目详情底部「💬」

## 与任务对话的关系
- 项目群聊：全项目级，所有成员都在
- 任务聊天室（[[task.dialog.concept]] / [[task.related.concept]]）：任务级，只可见用户在

两者独立，互不嵌套。

## 群名变更
- 默认与项目名同步
- 改项目名不会自动改群名（设计：群可有自己的展示名）
- 想同步：管理员需在群设置里手动改

## 不支持
- 不能给项目关掉群聊（dialog_id 一旦创建无法置空）
- 不能加非项目成员进群（即便是临时访客）
- 不能合并两个项目的群聊
- 个人项目不会自动建群
