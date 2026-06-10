---
id: messenger.read-receipt.concept
title: 已读未读状态
type: concept
feature: messenger
scope: end-user
locale: zh
aliases:
  - 已读未读
  - 谁看过了
  - 消息已读
  - 谁还没看
  - 看消息回执
  - 已读列表
related_tools: [get_message_list]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 已读未读不支持手动关闭显示，是系统统一行为
  - 通知类消息（notice）、系统消息不计入已读统计
  - 离开会话不会自动标记已读，需要打开会话并向上滚动经过该消息
last_verified: v1.7.90
---

# 已读未读状态

DooTask 的「已读未读」（read receipt）以消息 + 用户两两组合粒度记录。每个对话成员对每条消息有一行 `web_socket_dialog_msg_reads`，未读 read_at=null，已读后写入时间戳。

## 定义

- 未读（unread）：read_at 为 null
- 已读（read）：read_at 有值
- @提及未读（mention）：未读 + mention=1 的子集，会在会话列表右侧显示带数字的「@」标记
- 红点（dot）：服务端推送轻提示，刷新会话即清除

## 关键属性

- 未读数：会话级 / 全局级两个统计；会话内可点「未读消息」气泡跳转最早未读
- 已读列表：群消息支持查看「谁已读 / 谁未读」，调用 readlist 接口
- 标记未读（mark_unread）：用户主动把已读会话再次置成未读，便于稍后处理

## 与其他概念的关系

- 与免打扰：开免打扰后不弹通知但仍累计未读；@仍强制提醒
- 与标记未读：mark_unread=1 时打开会话会自动清除标记
- 与隐藏：隐藏的会话依然计入未读总数

## 不支持

- 不支持「单条消息按用户级别已读时间戳」的展示，仅二元 has-read / not-read
- 不支持「24h 内未读自动清除」
- 不支持已读后再撤销已读
