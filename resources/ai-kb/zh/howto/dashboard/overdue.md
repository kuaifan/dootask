---
id: dashboard.overdue.howto
title: 「超期任务」卡片用法
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 超期任务
  - 过期任务
  - 任务超期了
  - 逾期未完成
  - 超时任务
related_tools: [list_tasks, update_task, complete_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - 超期判断按用户本地时间，不同时区用户看到的列表可能差几小时
  - 没有 end_at 的任务永远不会变超期，归到「待完成」分组
  - 超期不自动改任务字段，只是前端展示分类
last_verified: v1.7.90
---

# 「超期任务」卡片用法

## 是什么
仪表盘第 2 张数字卡和同名分组列表，展示当前用户作为负责人（owner=1）的、`end_at < 今天 00:00:00` 但 `complete_at IS NULL` 的任务。即「应该已经做完但还没做」的清单。

## 数据规则
- end_at 比今天 0 点早
- complete_at 为空（未完成）
- archived_at 为空（未归档）
- 当前用户是任务负责人
- 任务按 end_at 升序（最久没做的排最前）

## 入口
- 桌面端：仪表盘顶部第 2 张数字卡
- 标题色一般用红 / 警告色
- 点卡片 → 跳转 / 展开分组

## 处理超期任务的常见做法
1. **延期**：进任务详情把 end_at 推到合理时间，详见 [[task.edit.howto.basic]]
2. **完成**：补做后 [[task.complete.howto]] 标记完成
3. **取消**：归档 [[task.archive.howto]] 或删除 [[task.delete-restore.howto]]
4. **转交**：把负责人改为他人（owner 从 1 → 0），任务从你的超期列表消失

## 不支持
- 仪表盘不会"自动延期超期任务"
- 没有"延期申请"流程，改 end_at 不需要审批
- 不能批量改一组超期任务的 end_at

## 与「[[project.export.howto]] 超期导出」的关系
- 仪表盘超期仅当前用户、跨项目
- 项目级超期导出按项目维度全员任务，含他人任务
