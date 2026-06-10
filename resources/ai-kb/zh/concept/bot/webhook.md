---
id: bot.webhook.concept
title: 机器人 Webhook 接入
type: concept
feature: bot
scope: end-user
locale: zh
aliases:
  - webhook
  - 机器人回调
  - 外部系统接入机器人
  - 机器人事件订阅
  - 怎么让机器人自动回复
  - bot 推送地址
related_tools: []
related_pages: [application]
prerequisites:
  - 已创建用户机器人（[[bot.create.howto]]）
negative:
  - webhook_url 必须以 `http://` 或 `https://` 开头，否则不发送
  - URL 长度最大 255 字符
  - 调用超时 30 秒，超时不重试
  - 用户自建机器人收到斜杠开头（`/...`）的消息直接忽略，不会触发 webhook
last_verified: v1.7.90
---

# 机器人 Webhook 接入

## 定义
Webhook 是把「机器人收到的事件」用 HTTP POST 推到外部服务的地址。外部服务回 `{"code":200,"message":"..."}` 时，DooTask 会把 `message` 作为机器人的文本回复发回会话。

## 可订阅事件
后端常量见 `App\Models\UserBot`：

| 事件 key | 触发时机 |
|---|---|
| `message` | 机器人收到消息（单聊任意消息；群聊需 @ 机器人） |
| `dialog_open` | 用户首次打开和机器人的会话 |
| `member_join` | 群聊里机器人或他人加入 |
| `member_leave` | 群聊里成员离开 |

不勾任何事件时默认按 `[message]` 处理（参考 `normalizeWebhookEvents`）。

## 请求体字段
`event = message` 时主要字段：

- `event`: `message`
- `text`: 用户的纯文本指令
- `reply_text`: 若是引用回复，被引用消息的文本
- `token`: 机器人当次有效的 API token，可调 DooTask API 代发消息
- `dialog_id` / `dialog_type` / `session_id`
- `msg_id` / `msg_uid` / `mention`（是否被 @）
- `bot_uid`: 机器人 userid
- `msg_user`: 发送方信息（userid / email / nickname / 临时 token）
- `extras`: JSON 字符串，含 `timestamp` 等
- `version`: 当前 DooTask 版本

## 设置
在「我的机器人」编辑面板填 `webhook_url` 并勾事件；或单聊「机器人管理」里 `/webhook <bot_id> <url>`。

## 不支持
- 不支持鉴权签名（如 HMAC），请用 HTTPS + 服务端校验 `token`
- 不支持调用失败重试 / 死信队列；失败仅在后端 info 日志记录
- 不能区分 webhook 收到的消息是来自哪个具体群成员之外的额外字段
