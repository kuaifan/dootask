---
id: messenger.group.howto.dissolve
title: 解散群组
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么解散群
  - 解散群组
  - 删除群
  - 删群
  - 群没了怎么办
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites:
  - 必须是群主
  - 仅支持普通群（group_type=user）
negative:
  - 项目群 / 任务群 / 部门群 / 全员群不能解散；这些群会随项目 / 任务 / 部门删除而自动消失
  - 群管理员（deputy）不能解散群，仅群主能
  - 解散后群消息全部不可恢复，群成员列表立刻清空
last_verified: v1.7.90
---

# 解散群组

解散群组（disband）会软删除整个群（dialog 表 deleted_at），并清除所有成员的 dialog_user 记录。仅普通群（group_type=user）的群主可执行。

## 入口

- 桌面端：打开群聊 → 右上角群信息面板 → 底部「解散群组」红色按钮
- 移动端：进群 → 右上角「⋯」→ 群组信息 → 底部「解散群组」

## 操作步骤

1. 点击「解散群组」
2. 二次确认弹窗，输入「解散」/「确认」等校验文字
3. 提交后：
   - 群对话进入软删除（dialog.deleted_at）
   - 所有成员从会话列表移除该群
   - 服务端推送 groupDelete

## 与「退群」的区别

- 退群：自己离开，群继续存在（群主除外）→ 详见退出群组
- 解散：群消失，所有成员都被踢出

## 不支持

- 解散后不可恢复，没有「30 天回收站」机制
- 全员群（group_type=all）、部门群（department）、项目群（project）、任务群（task）、OKR 群（okr）不可手动解散
- 群里有未完成的项目讨论关联时仍可解散（项目讨论会失去对应群）
