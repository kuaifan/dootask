---
id: bot.concept
title: 机器人是什么
type: concept
feature: bot
scope: end-user
locale: zh
aliases:
  - 机器人
  - bot
  - robot
  - DooTask 机器人
  - 机器人有什么用
  - 机器人分几种
related_tools: []
related_pages: [messenger]
prerequisites: []
negative:
  - 机器人本质是特殊 User（users.bot = 1），不能用普通账号登录网页
  - 机器人不能登录前端 UI，只能通过 token 调用 API 或被 @ 触发
  - 普通用户最多创建 50 个自建机器人
last_verified: v1.7.90
---

# 机器人是什么

## 定义
DooTask 的机器人（bot / robot）是一种特殊用户账号（数据库 `users.bot = 1`），可以加入会话、收发消息、被 @ 触发，但不能登录 UI。它通常用于自动通知、外部系统接入、AI 对话。

## 三类机器人
DooTask 把机器人分三类，能力依次递增：

- **内置系统机器人**（system bot）：邮箱以 `@bot.system` 结尾，由系统创建并维护，如「任务提醒」「审批」「签到打卡」「AI 助手」「会议通知」。普通用户不能新建或删除，仅管理员可改设置。详见 [[bot.system-list.concept]]。
- **用户自建机器人**（UserBot）：任何登录用户都可在「应用 → 我的机器人」里创建，用于把外部系统消息推进 DooTask，或拿到 token 后由外部代码代发消息。详见 [[bot.create.howto]]。
- **Webhook 接入**：自建机器人配置 `webhook_url` 后，收到的群消息 / @触发 / 成员变更等事件会被 POST 到该地址，外部服务可据此回复。详见 [[bot.webhook.concept]]。

## 关键属性
- 机器人有独立 `userid`、`token`、头像、昵称
- 像普通用户一样被加入群（[[bot.invite.howto]]）或被 @
- 群聊里必须 @ 机器人才会触发回复，单聊则任意消息都触发（[[bot.mention.howto]]）
- 自建机器人可设 `clear_day`（消息保留天数，1-999，默认 90 天）

## 不支持
- 机器人之间不会互相触发（避免死循环），收到对方消息直接忽略
- 系统机器人不能由普通用户删除（删除会报「系统机器人不能删除」）
- 单个用户不能创建超过 50 个自建机器人（超出报「超过最大创建数量」）
