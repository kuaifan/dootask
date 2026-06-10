---
id: report.concept
title: 工作报告是什么
type: concept
feature: report
scope: end-user
locale: zh
aliases:
  - 工作报告
  - 工作汇报
  - 什么是日报周报
  - 报告功能
  - DooTask 报告
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 只支持周报（weekly）和日报（daily）两种类型，没有月报 / 季报 / 年报
  - 同一个用户在同一周期（同一周 / 同一天）只能提交一份同类型报告，重复提交会被拒绝
  - 接收人列表在每次编辑保存时会被覆盖（不是追加）
last_verified: v1.7.90
---

# 工作报告是什么

## 定义
工作报告（也叫工作汇报）是 DooTask 内置的成员定期向他人发送工作总结的功能，分为周报和日报两种类型。撰写后可指定一组接收人发送，接收方可在「我收到的」中查看，并标记已读/未读。

## 关键属性
- **类型**：`weekly`（周报）/ `daily`（日报）；类型决定模板和唯一标识周期 [[report.type.concept]]
- **唯一标识 sign**：每位用户在每个周期内只允许提交一份同类型报告；底层用 `userid + 周期编号` 拼接
- **接收人**：可指定多人，发送后他们在「我收到的」看到，且收到 WebSocket 推送
- **已读状态**：每个接收人独立维护 read 字段，详情打开时自动置已读
- **可分享**：可生成 ReportLink 短码 [[report.link.concept]]，把单条报告分享到任意对话
- **AI 解读**：每个查看者（提交人或接收人）可保存独立的 AI 分析 [[report.analysis.concept]]

## 与其他概念的关系
- 模板自动汇总自任务系统（已完成 + 未完成 + 下周拟定）→ [[report.template.howto]]
- 分享到对话走消息系统（生成 mention 链接） → [[report.share.howto]]
- 「我收到的」未读数会展示在头像菜单旁的红点上

## 入口
头像菜单 / 应用中心「工作报告」→ [[report.entry.menu-map]]
