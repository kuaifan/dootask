---
id: view.list.howto
title: 使用列表视图查看任务
type: howto
feature: view
scope: end-user
locale: zh
aliases:
  - 列表视图
  - 任务表格
  - 怎么用表格看任务
  - 表格视图
  - 按行看任务
  - table 视图
related_tools: [list_tasks]
related_pages: [project_detail]
prerequisites: []
negative:
  - 列表视图列结构固定（任务名 / 列表 / 优先级 / 负责人 / 到期时间），不能自定义增删列
  - 不支持双击编辑（点击任务行打开任务详情）
  - 没有 Excel 式选区批量操作
  - 行内排序仅 优先级 / 到期时间 两列可点
last_verified: v1.7.90
---

# 使用列表视图查看任务

## 入口
- 桌面端 Web：进入项目详情页 → 右上角视图切换条 → 选第二个图标（「表格」）
- 视图切换记忆在 `cacheParameter.menuType='table'`，下次进入同一项目仍是列表视图

## 列表分段
切到列表视图后，任务自动按归属分成 4 段：

1. **我的任务**：当前用户是负责人的任务
2. **协助的任务**：当前用户是协助者的任务（仅当存在时显示）
3. **未完成任务**：项目内其他未完成任务
4. **已完成任务**：项目内已完成任务（仅当 task_num > 0 时显示）

每段标题旁有箭头可折叠 / 展开，状态记忆在 `cacheParameter.showMy / showHelp / showUndone / showCompleted`。

## 列结构
列表视图列固定为：

- 任务名称（含 # 编号）
- 列表（任务所在 column）
- 优先级（可点击表头排序）
- 负责人
- 到期时间（可点击表头排序）

## 行内操作
- 点击任务行 → 打开任务详情
- 行右侧「+」 → 在该段下快速添加任务
- 行右侧「···」 → 单行任务操作菜单

## 不支持
- 不能自定义列（增删 / 改顺序 / 改宽度）
- 不能多选批量改字段（如批量改负责人）
- 表头排序只对优先级 / 到期时间生效
- 没有筛选器（用顶部 Cascader 筛选）
