---
id: messenger.vote.howto
title: 群投票
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么发起投票
  - 群投票
  - 创建投票
  - 投票消息
  - 群里投个票
  - 怎么做投票
related_tools: [send_message]
related_pages: [messenger, dialog_chat]
prerequisites:
  - 必须在群对话内（包括项目群 / 任务群）
negative:
  - 投票不支持设定截止时间，仅支持发起人手动「结束」
  - 投票不支持修改选项；要改只能结束后再发起
  - 用户对同一投票不可重复投，第二次提交会报「不能重复投票」
last_verified: v1.7.90
---

# 群投票

群投票（vote）是 messenger 内置消息类型之一，发起后以特殊卡片样式显示在消息流中，所有人可点击参与。支持单选 / 多选 / 匿名 / 强制结束 / 重新发起。

## 入口

- 桌面端：会话底部「+」附加菜单 → 「投票」
- 移动端：底部「+」→ 「投票」

## 操作步骤

1. 输入投票主题（text，最长 200000 字）
2. 添加选项（list，至少 2 项）
3. 切换开关：
   - 多选（multiple=1）
   - 匿名（anonymous=1）
4. 发起 → 群里出现投票卡片，所有人均可点击参与

## 投票动作 (type)

| type | 含义 |
|---|---|
| create | 发起新投票（默认） |
| vote | 提交选项 |
| finish | 发起人结束投票（state=0）|
| again | 用相同选项再发起一次 |

## 投票统计

- 实时显示各选项票数
- 匿名（anonymous=1）：不显示投票人，仅显示数量
- 非匿名：可点选项查看投票人列表
- 多选（multiple=1）：用户一次可勾多个

## 不支持

- 不支持改投（投后不可改），仅可重发整个投票
- 不支持限定可投人员（群成员均可）
- 不支持加权投票，每人每选项 1 票
