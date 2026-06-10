---
id: calendar.drag.howto
title: 拖动日历事件改时间
type: howto
feature: calendar
scope: end-user
locale: zh
aliases:
  - 拖动任务改时间
  - 日历拖事件
  - 拖任务到别的日期
  - 改任务截止日
  - 日历改时间
related_tools: [update_task]
related_pages: [calendar]
prerequisites:
  - 你是任务负责人，有 TASK_TIME 或 TASK_UPDATE 权限
  - 桌面端，移动端只读
negative:
  - 移动端日历只读，不能拖动
  - 拖动只改 end_at / start_at，不改其他字段
  - 跨多天任务拖动会同步保持长度，不会自动延长
last_verified: v1.7.90
---

# 拖动日历事件改时间

## 是什么
桌面端日历支持鼠标拖动事件块，改任务的 start_at / end_at。拖完会立即触发 `update_task` 接口，按视图精度（月 / 周 / 日）改时间。

## 入口
- 桌面端：日历任意视图下，按住任务卡片拖动
- 周 / 日视图：纵向拖改时段，横向拖改日期
- 月视图：只能改日期（保留时段）
- 移动端：禁用拖动（[[calendar.mobile.faq]]）

## 拖动后的字段变化
| 视图 | 拖动方向 | 修改字段 |
|---|---|---|
| 月 | 任意格 → 另一格 | end_at 的日期部分（保留时分） |
| 周 | 纵向 | end_at 的时分 + start_at（保留 duration） |
| 周 | 横向 | end_at + start_at 的日期 |
| 日 | 纵向 | end_at 的时分 |

拖动**事件边缘**可单独改 start_at（延长 / 缩短）。

## 拖动后的副作用
- WebSocket 推送给项目所有在线成员
- 仪表盘 [[dashboard.today.howto]] / [[dashboard.overdue.howto]] 数字 / 列表跟着重算
- 任务详情页 [[task.field.deadline.concept]] 字段同步更新
- 项目动态 ProjectLog 记一条「X 改了任务 Y 的时间」

## 拖动失败的可能原因
- 你不是该任务负责人 → 无权限改
- 项目权限规则关掉了 TASK_TIME → 看 [[project.permission.concept]]
- 任务已归档 / 已删除 → 必须先恢复

## 不支持
- 不能批量拖多个任务
- 不支持「拖动同时改其他字段」
- 移动端只读，无法拖
