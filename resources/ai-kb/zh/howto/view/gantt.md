---
id: view.gantt.howto
title: 使用甘特图视图
type: howto
feature: view
scope: end-user
locale: zh
aliases:
  - 甘特图
  - 时间轴视图
  - 项目排期
  - 看任务时间线
  - gantt 视图
  - 任务计划图
related_tools: [list_tasks]
related_pages: [project_detail]
prerequisites: []
negative:
  - 没有 start_at 或 end_at 的任务在甘特图中不显示（需要先设置时间段）
  - 子任务在甘特图中独立显示，不会自动归并到父任务
  - 不支持任务依赖关系（A 完成才能开始 B 这种）
  - 移动端 / 触屏端不支持拖拽改时间，只能查看
last_verified: v1.7.90
---

# 使用甘特图视图

## 入口
- 桌面端 Web：项目详情页 → 右上角视图切换条 → 选第三个图标（时间轴）
- 视图记忆在 `cacheParameter.menuType='gantt'`

## 视图能力
甘特图（基于 GSTC 库）以横向时间轴展示项目任务，每条任务一行，矩形从 `start_at` 跨到 `end_at`。

- 矩形颜色 = 任务颜色 / 工作流状态颜色
- 鼠标悬停显示任务名 + 时间段 + 负责人
- 点击任务条 → 打开任务详情

## 操作能力
- **改时间段**：抓矩形两端拖动 → 改 start_at 或 end_at（仅桌面端）
- **整段移动**：抓矩形中间拖动 → 同步平移 start_at 与 end_at
- **横向滚动**：滚轮 / 拖动时间轴
- **缩放**：顶部按钮切换日 / 周 / 月粒度

## 与筛选的联动
顶部 Cascader 筛选条件（按工作流状态 / 负责人 / 标签）在甘特图同样生效，未命中的任务隐藏。

## 不显示的任务
- 没有 start_at 的任务（未排期）
- 没有 end_at 的任务（开放截止）
- 这些任务在「未计划」筛选项里可单独查看

## 不支持
- 不支持任务依赖（前置任务关系）
- 不支持关键路径计算
- 不支持资源直方图（按人负载）
- 不支持导出 PNG / PDF
- 不支持子任务的层级缩进展示（子任务平铺一行）
