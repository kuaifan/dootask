---
id: data-export.task.howto
title: 导出超期任务
type: howto
feature: data-export
scope: admin
locale: zh
aliases:
  - 导出超期任务
  - 全系统超期任务
  - 谁的任务超期了
  - 超期任务报表
  - overdue 导出
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 导出范围是全系统所有项目，不能按项目 / 成员筛选
  - 只导出"未完成"且 end_at 已过的任务；已完成的延期任务不在内
  - 没有计划截止时间（end_at 为空）的任务不会被纳入
last_verified: v1.7.90
---

# 导出超期任务

## 入口
桌面端：左侧栏「应用」→「数据导出」（仅管理员）→「导出超期任务」。
对应 API：`GET api/project/task/exportoverdue`。

## 操作步骤
1. 进入「数据导出」→ 选「导出超期任务」
2. 弹出确认框「你确定要导出所有超期任务吗？」→ 确定
3. 立即返回，文件由系统机器人 `system-msg` 私聊推送

## 导出范围
后端定义为：`complete_at IS NULL AND end_at IS NOT NULL AND end_at <= NOW()`。也就是：
- 还没完成
- 设置了截止时间
- 截止时间已经过去

按 `end_at` 升序（最早超期的在前），分批 100 条流式写出，避免内存爆。

## 导出字段
固定 11 列：任务 ID、父任务 ID、所属项目、标题、标签、开始时间、结束时间、计划用时、超时时间、负责人、创建人。

## 与「任务统计」的区别
- **任务统计**：要选成员 + 时间段，含已完成 / 已归档 / 未完成全部状态，看 [[data-export.project.howto]]
- **超期任务**：无参数，只看全系统当前已超期未完成

## 不支持
- 不支持按项目 / 部门筛选超期任务
- 不支持仅导出某个成员的超期任务
