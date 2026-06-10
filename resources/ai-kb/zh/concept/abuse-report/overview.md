---
id: abuse-report.concept
title: 举报管理是什么
type: concept
feature: abuse-report
scope: admin
locale: zh
aliases:
  - 举报管理
  - 投诉管理
  - 举报后台
  - 用户举报
  - complaint
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 普通成员只能提交举报，看不到「举报管理」后台
  - 举报后不会自动封号或屏蔽，需要管理员手动决定
  - 不支持自动 AI 内容审核（仅人工处理）
last_verified: v1.7.90
---

# 举报管理是什么

## 定义
举报管理（complaint）是 DooTask 内置的违规内容处理后台。任何成员在群聊 / 个人对话遇到违规消息时可提交举报，系统管理员在后台审核并标记「已处理」或删除。模型对应 `App\Models\Complaint`，控制器 `ComplaintController`。

## 关键属性
- **举报对象**：对话（`dialog_id`），不能对单条消息单独举报
- **举报人**：`userid`，提交后系统机器人会通知前 10 个在线管理员
- **举报类型**：固定 7 种（id 10/20/30/40/50/60/70），见下
- **状态**：0 待处理 / 1 已处理 / 2 已删除
- **附件**：最多 N 张图片（前端 `imgs[]` 数组，含 path）
- **原因**：必填文本

## 举报类型受控词表
| id | 含义 |
|---|---|
| 10 | 诈骗诱导转账 |
| 20 | 引流下载其他 APP 付费 |
| 30 | 敲诈勒索 |
| 40 | 照片与本人不一致 |
| 50 | 色情低俗 |
| 60 | 频繁广告骚扰 |
| 70 | 其他问题 |

## 通知机制
举报提交后，后端取 `identity LIKE '%,admin,%'` 且按 `line_at` 倒序的前 10 位管理员，由 `system-msg` 系统机器人 template 消息推送「收到新的举报信息：{原因}」。

## 不支持
- 不支持对单条消息举报（只能对整个 dialog）
- 不支持举报者匿名（后端会存 `userid`）
- 不支持自定义举报类型
- 不支持自动黑名单 / 封禁

## 相关
- 处理流程：[[abuse-report.handle.howto]]
- 入口：[[abuse-report.entry.menu-map]]
