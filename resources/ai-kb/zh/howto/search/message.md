---
id: search.message.howto
title: 搜索消息
type: howto
feature: search
scope: end-user
locale: zh
aliases:
  - 搜聊天记录
  - 找消息
  - 搜消息
  - 在群里搜消息
  - 怎么找以前的聊天
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 只能搜到当前用户能访问的会话内消息（私聊 / 自己加入的群）
  - 机器人消息不会被搜出（`bot=1` 过滤）
  - 指定 `dialog_id` 时会校验当前用户是否在该对话内，无权访问会报错
  - 单次最多返回 50 条（默认 20）
last_verified: v1.7.90
---

# 搜索消息

## 入口
- 全局搜索框（[[search.entry.menu-map]]）输入关键词，切到「消息」分类
- 在某个聊天窗口内的「搜索本对话」框，会自动带上 `dialog_id` 只搜该对话

## 接口
- 端点：`GET api/search/message`
- 参数：
  - `key`（必填）：搜索关键词，空则返回空数组
  - `search_type`（可选）：`text` / `vector` / `hybrid`，默认 `hybrid`，仅 Manticore 生效
  - `take`（可选）：返回数量，默认 20，上限 50
  - `mode`（可选）：`message` / `position` / `dialog`，默认 `message`
  - `dialog_id`（可选）：筛选指定对话内的消息，非 0 时需有权访问

## mode 三种返回格式
- `message`（默认）：返回完整消息，含发送者、消息体、时间、相关度
- `position`：只返回消息 ID 数组，用于跳转定位
- `dialog`：按会话聚合，每个会话只返回 1 条命中（带 `search_msg_id` 用于跳定位）

## 权限范围
通过 `accessibleByUser($userid)` 限制：只能搜到「当前用户在对话成员列表里的会话」中的消息。指定 `dialog_id` 时额外做 `WebSocketDialog::checkDialog` 校验。

## 不支持
- 搜不到机器人消息
- 搜不到非成员会话的消息
- 不会单次返回超过 50 条结果（最多 50 条）
- 文件 / 图片消息只能按文件名命中，无法按图片内容搜索

## 相关
- 引擎差异：[[search.engine.concept]]
- 搜不到怎么办：[[search.no-result.faq]]
