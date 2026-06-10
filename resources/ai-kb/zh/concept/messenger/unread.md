---
id: messenger.unread.concept
title: 未读数和标记未读
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 未读数
  - 红点是什么
  - 怎么显示未读数
  - 标记未读
  - 把消息标记为未读
  - 群里多少条没看
related_tools: [get_message_list]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 红点（dot）和未读数（unread）是两个独立机制：dot 是轻提示，unread 按消息粒度统计
  - 未读数总上限受展示限制（一般 99+ 截断），实际 DB 仍精确
  - 标记未读（mark_unread）只对自己有效，且打开会话会自动清除
last_verified: v1.7.90
---

# 未读数和标记未读

「未读数」（unread count）是每个用户在每个会话里未读消息的条数，按 dialog_msg_reads.read_at=null 实时计算。「标记未读」（mark_unread）是用户主动把已读会话再次置成未读，用于稍后处理。

## 定义

- unread：会话当前未读消息条数
- unread_one：是否仅有 1 条（用于「N 条新消息」气泡显示）
- mention：未读 + 被 @ 的消息条数
- mention_ids：被 @ 的消息 ID 列表
- mark_unread：用户主动标记未读（1=已标）
- dot：服务端轻量推送红点，刷新即清

## 关键属性

- 进入会话并向上滚动经过该消息 → 自动标已读
- 主动「标记已读 / 一键已读」→ chunkById 批量写 read_at
- 主动「标记未读」→ mark_unread=1，列表上显示红点；打开会话自动清除
- 免打扰开启 / 关闭都不影响未读计数

## 与会话列表显示的关系

- 未读 > 0 显示「N」徽标
- mention > 0 显示「@N」徽标，优先级高于普通未读
- mark_unread=1 显示红点但不显数字

## 不支持

- 不支持「将整个会话彻底清零未读但不打开」（必须打开或调用一键已读）
- 不支持按消息类型筛选未读（如只看图片未读）
- 不支持把未读数同步到其他客户端的"动态"提示
