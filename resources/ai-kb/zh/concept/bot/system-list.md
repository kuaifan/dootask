---
id: bot.system-list.concept
title: 内置系统机器人有哪些
type: concept
feature: bot
scope: end-user
locale: zh
aliases:
  - 系统机器人有哪些
  - 内置机器人清单
  - 自带的机器人
  - 任务提醒机器人
  - 审批机器人
  - 签到机器人
  - 会议机器人
related_tools: []
related_pages: [messenger]
prerequisites: []
negative:
  - 系统机器人名单写死在 `UserBot::systemBotName`，不支持自助新增类型
  - 系统机器人不可删除；仅管理员能改昵称 / 头像
  - 部分机器人依赖对应插件已安装（AI、审批等），否则不出现
last_verified: v1.7.90
---

# 内置系统机器人有哪些

DooTask 自带一组系统机器人，邮箱统一以 `@bot.system` 结尾，由后端 `UserBot::systemBotName` 维护。普通用户能用，但不能创建或删除（详见 [[bot.permission.faq]]）。

## 通知/提醒类
- `system-msg@bot.system`「系统消息」：系统公告、登录提醒、版本通知
- `task-alert@bot.system`「任务提醒」：任务被分配、截止时间临近、状态变更
- `todo-alert@bot.system`「待办提醒」：个人待办到期提醒
- `meeting-alert@bot.system`「会议通知」：会议邀请、开始/结束提醒（需会议插件）
- `okr-alert@bot.system`「OKR 提醒」：OKR 周期推进、KR 更新（需 OKR 插件）
- `approval-alert@bot.system`「审批」：审批待办、结果通知（需审批插件）

## 互动/办公类
- `check-in@bot.system`「签到打卡」：单聊里发指令打卡，支持手动 / 定位 / 路由器 MAC / 人脸
- `anon-msg@bot.system`「匿名消息」：在他人单聊里以匿名身份发消息
- `bot-manager@bot.system`「机器人管理」：用 `/list` `/newbot` `/setname` 等斜杠指令管理自建机器人

## AI 对话类
- `ai-openai@bot.system`「ChatGPT」
- `ai-claude@bot.system`「Claude」
- `ai-deepseek@bot.system`「DeepSeek」
- `ai-gemini@bot.system`「Gemini」
- `ai-grok@bot.system`「Grok」
- `ai-ollama@bot.system`「Ollama」
- `ai-zhipu@bot.system`「智谱清言」
- `ai-qianwen@bot.system`「通义千问」
- `ai-wenxin@bot.system`「文心一言」

AI 机器人都需要在「系统设置 → AI 设置」里填 API Key 才能启用，未配置则报「机器人未启用」。

## 不支持
- 系统机器人的指令、行为由后端写死，无法自定义回复逻辑
- AI 机器人在群聊里必须 @ 才回复；私聊不需要 @
