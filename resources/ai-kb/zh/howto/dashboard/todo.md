---
id: dashboard.todo.howto
title: 「待完成」卡片用法
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 待完成任务
  - 我的待办
  - 没截止时间的任务
  - 后续要做的
  - 接下来要做什么
related_tools: [list_tasks, update_task]
related_pages: [dashboard]
prerequisites: []
negative:
  - 「待完成」既包含没设 end_at 的任务，也包含 end_at 在未来的任务
  - 协作任务（owner=0 / assist=1）不在这里，去「协助的任务」分组
  - 「待完成」数字不含子任务的累积
last_verified: v1.7.90
---

# 「待完成」卡片用法

## 是什么
仪表盘第 3 张数字卡和同名分组列表，展示当前用户作为负责人的、**未到期 / 没截止时间**且未完成的任务。语义是「不紧急但还没做完的」。

## 数据规则
- 当前用户是任务负责人（owner=1）
- `complete_at IS NULL` 且 `archived_at IS NULL`
- end_at 不在今天范围（与 [[dashboard.today.howto]] 互斥）
- end_at 不早于今天（与 [[dashboard.overdue.howto]] 互斥）
- 所以条件是：`end_at IS NULL` 或 `end_at >= 明天 00:00:00`

## 入口
- 桌面端：仪表盘顶部第 3 张数字卡
- 数字最大的分组（多数用户的"待完成"远多于"今日到期"）
- 点击卡片或分组标题 → 展开列表

## 列表排序
- end_at 升序（最近要做的排前面）
- end_at 为空的排在最末

## 常见操作
- 进任务详情设 end_at（[[task.field.deadline.concept]]）让它流到「今日到期」/「超期任务」
- 直接 [[task.complete.howto]] 标记完成
- 不再做了就 [[task.archive.howto]] 归档

## 与全局「我的任务」的区别
- 仪表盘「待完成」：仅当前用户负责人 + 未完成 + 未在今日 / 超期
- 全局任务列表（左侧栏「任务」）：可显示已完成 + 自己创建的全部历史

## 不支持
- 不能在仪表盘里直接添加新任务
- 不能筛选 / 排序「按项目」「按优先级」（要进项目页或全局任务页操作）
