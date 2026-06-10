---
id: dashboard.assist.howto
title: 「协助的任务」卡片用法
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 协助的任务
  - 我帮忙的任务
  - 协作的任务
  - assist 任务
  - 别人让我协助的
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 协作任务我不能直接 [[task.complete.howto]] 标记完成（仅负责人能）
  - 协作任务不计入「今日到期」「超期」「待完成」三张顶部数字卡
  - 项目转让 / 离职后该协作关系会保留，直到对方手动调整
last_verified: v1.7.90
---

# 「协助的任务」卡片用法

## 是什么
仪表盘第 4 个分组列表，独立于顶部 3 张数字卡之外，展示当前用户作为**协作者**（[[task.field.owner-assist.concept]] 中 `owner=0` 且 `assist=true`）参与的任务，但不是负责人。语义是「别人主任务上把我加进来一起做的活」。

## 数据规则
- ProjectTaskUser 命中当前用户、owner=0（协作者标识）
- complete_at IS NULL（任务未完成）
- archived_at IS NULL（任务未归档）
- deleted_at IS NULL

## 入口
- 桌面端：仪表盘下方第 4 个分组（在「今日到期」「超期任务」「待完成」之下）
- 标题旁数字 = 协作任务数
- 点标题折叠 / 展开

## 操作权限
| 行为 | 协作者（assist） | 负责人（owner=1） |
|---|---|---|
| 查看任务详情 | ✓ | ✓ |
| 评论 / 发消息 | ✓ | ✓ |
| 改任务字段 | 视项目权限规则 | ✓ |
| 标记完成 | ✗ | ✓ |
| 删除任务 | ✗ | 视权限 |

如果改不动字段，看 [[project.permission-denied-task.faq]] 排查。

## 不支持
- 协作者不能完成任务
- 协作者不能给任务再加协作者
- 仪表盘不区分"我创建的"与"别人加我的"协作任务，全混在一起
