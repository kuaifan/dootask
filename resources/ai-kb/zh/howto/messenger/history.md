---
id: messenger.history.howto
title: 查看历史消息
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 翻看历史消息
  - 看以前的聊天
  - 加载更早消息
  - 怎么往上翻
  - 历史聊天记录
  - 跳到某条消息
related_tools: [get_message_list]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 翻历史只能一次拉一页（50 条），无法一次性导出全部聊天记录
  - 已撤回的消息显示为「XXX 撤回了一条消息」占位，原内容不可恢复
  - 已退群成员看不到退群之前 / 之后的群历史，加回群也不能看历史
last_verified: v1.7.90
---

# 查看历史消息

查看历史消息（msg list）通过滚动加载（infinite scroll）实现：会话默认显示最近 50 条，向上滚动触发加载更早 50 条，向下补齐增量。也可通过 position_id 跳到任意一条上下文。

## 入口

- 桌面端：会话内向上滚动鼠标 / 触控板
- 桌面端：搜索结果 / 引用回复点击「跳转原消息」自动定位
- 移动端：进会话向上滑动

## 操作步骤

1. 默认请求：`msg_id=0` 返回最近 50 条
2. 向上翻：传 `prev_id=最早一条的 id`，倒序取该 id 之前 50 条
3. 向下翻：传 `next_id=最晚一条的 id`，正序取该 id 之后 50 条
4. 定位上下文：传 `position_id=消息 id`，返回前后各 25 条
5. 也可按 msg_type 过滤（text / image / file / record / meeting / tag / todo / link）

## 字段说明

| 字段 | 含义 |
|---|---|
| take | 单次取多少条，默认 50，最大 100 |
| msg_type | 过滤条件（按消息类型） |
| dialog | 顺带返回会话信息（仅首次加载） |
| todo / top | 顺带返回当前会话的待办、置顶消息 |

## 跨设备一致性

历史消息存储在服务端，跨设备登录加载内容一致；不会因换设备丢失记录。

## 不支持

- 不支持「按日期跳到某天」的快捷入口，需要搜索后定位
- 不支持下载整段聊天记录为本地文件
- 不支持加载历史时同时改变排序方向（始终时间正序展示）
