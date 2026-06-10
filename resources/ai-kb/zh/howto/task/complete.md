---
id: task.complete.howto
title: 标记任务完成 / 取消完成
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 完成任务
  - 打勾任务
  - 任务标记完成
  - 取消完成
  - 重新打开任务
related_tools: [complete_task, update_task]
related_pages: [task_detail, project_detail]
prerequisites:
  - 任务可见
  - 是负责人或有 TASK_UPDATE 权限
negative:
  - 父任务完成不会自动完成所有子任务，需要手动或 [[task.subtask.howto.create]] 后逐个处理
  - 完成不等于归档，归档要走 [[task.archive.howto]]
  - 重复任务在完成的瞬间生成下一份，详见 [[task.recurring.howto]]
last_verified: v1.7.90
---

# 标记任务完成 / 取消完成

## 是什么
任务完成在 DooTask 中通过 `complete_at` 字段记录，非空表示已完成。完成与归档（archived_at）、删除（deleted_at）是三个互相独立的状态字段。

## 入口
- 桌面端：任务卡片左侧圆形复选框、详情页顶部「✓ 完成」按钮
- 桌面端列表视图：每行左侧勾选框
- 移动端：任务详情顶部按钮，或卡片长按菜单

## 操作步骤
1. 点击复选框 / 完成按钮
2. 任务 `complete_at` 写入当前时间，整个卡片显示删除线 / 灰化
3. 项目动态记一条「X 完成了任务 Y」日志
4. 若该任务带 `loop`（循环），同时生成下一份新任务

## 取消完成
1. 在已完成任务上再次点击复选框
2. `complete_at` 置空
3. 任务回到未完成状态，工作流状态保持原样不重置

## 不支持
- 完成不能批量勾选多任务（除非用 MCP `complete_task` 编程调用）
- 任务有未完成的子任务时仍可完成父任务，不会被阻止
- 完成后不能直接修改 `complete_at` 的具体时间，必须取消再重新完成
