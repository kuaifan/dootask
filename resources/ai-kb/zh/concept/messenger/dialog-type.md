---
id: messenger.dialog-type.concept
title: 对话类型
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 都有什么会话
  - 群有哪几种
  - 项目讨论组是什么
  - 任务讨论是什么
  - 单聊和群聊区别
  - 全员群是什么
related_tools: [search_dialogs]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 用户不能手动把单聊（type=user）升级成群聊，需重新建群并把对方加入
  - 项目群 / 任务群 / 部门群 / 全员群不允许普通成员解散
  - 单聊不能设免打扰按群粒度，但可在系统设置里设置「个人会话静默」全局策略
last_verified: v1.7.90
---

# 对话类型

DooTask 的「会话」（也叫对话、Dialog）有两大类：单聊（type=user）和群聊（type=group）。群聊按 group_type 又分多个子类，影响成员管理权限和能否解散。

## 定义

| type | group_type | 说明 |
|---|---|---|
| user | — | 单聊（两人） |
| group | user | 普通自建群（群主可改名、加人、解散、转让） |
| group | project | 项目讨论组（与项目同生命周期） |
| group | task | 任务讨论组（按任务参与人自动维护） |
| group | department | 部门群（按部门成员自动维护） |
| group | all | 全员群（系统级，仅管理员可改名） |
| group | okr | OKR 评论会话 |

## 关键属性

- 单聊的 dialog_id 由系统按双方 userid 自动分配，找对方即可打开
- 项目群、任务群、部门群、全员群属于自动维护类型，成员变化跟随源数据
- 群主（owner_id）+ 群管理员（deputy_ids）只对 group_type=user 的普通群有完整管理权

## 与其他概念的关系

- 单聊与「联系人」一一对应，给某人发消息会自动创建或复用单聊
- 项目群与项目（project）双向关联，详见项目讨论概念 [[project.dialog.concept]]
- 任务群与任务（task）双向关联，详见任务讨论概念 [[task.dialog.concept]]

## 不支持

- 不能将单聊转换成群聊
- 不能将自动维护型群（项目 / 任务 / 部门 / 全员）改成普通群
