---
id: bot.create.howto
title: 创建用户机器人
type: howto
feature: bot
scope: end-user
locale: zh
aliases:
  - 怎么建机器人
  - 创建机器人
  - 新建 bot
  - 添加机器人
  - 我要做个机器人
  - 自建机器人
related_tools: []
related_pages: [application, messenger]
prerequisites: []
negative:
  - 单个用户最多 50 个自建机器人，超出报「超过最大创建数量」
  - 机器人名称必须 2-20 字符，太短/太长会被拒
  - 创建后不能改「是否支持会话」开关，需重建
  - 用户自建机器人不识别斜杠指令（`/help` 等只对系统机器人有效）
last_verified: v1.7.90
---

# 创建用户机器人

## 入口
- 桌面端：左侧栏「应用」→「我的机器人」卡片 → 右上角「添加机器人」
- 也可在「机器人管理」（`bot-manager@bot.system`）单聊里发 `/newbot 名称` 创建

## 操作步骤
1. 在「我的机器人」面板点「添加机器人」
2. 填写「机器人名称」（2-20 字符，必填）
3. 可选：上传头像（512×512 推荐）
4. 可选：设置「保留消息天数」`clear_day`（1-999，默认 90）
5. 可选：填写 `webhook_url`，并勾选要订阅的事件（message / dialog_open / member_join / member_leave）
6. 点「保存」，对应后端接口 `api/users/bot/edit`（`bot__edit`）

## 字段说明
| 字段 | 必填 | 说明 |
|---|---|---|
| name | 是 | 机器人昵称，2-20 字符 |
| avatar | 否 | 头像 URL；留空用默认 |
| session | 否 | 0/1，是否开启「新会话/历史会话」菜单；仅新建时生效 |
| clear_day | 否 | 消息保留天数 1-999，超期自动清理 |
| webhook_url | 否 | `http(s)://` 开头，≤ 255 字符 |
| webhook_events | 否 | 事件列表，详见 [[bot.webhook.concept]] |

## 创建之后
- 在「我的机器人」列表点「开始聊天」进入和机器人的单聊
- 把机器人邀请到群里：[[bot.invite.howto]]
- 拿 token：在「机器人管理」单聊发 `/token <bot_id>`，或 `/revoke` 重置
- 删除：列表里点「删除」（要求填删除备注，≤ 255 字符）

## 不支持
- 不能跨账号转让机器人
- 不能把已有的普通用户改成机器人，反之亦不能
