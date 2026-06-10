---
id: task.sort.howto
title: 任务列内排序
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 任务排序
  - 重排任务顺序
  - 任务置顶
  - 调整任务顺序
  - 排序任务
related_tools: [update_task]
related_pages: [project_detail, task_list]
prerequisites:
  - 在看板或列表视图
  - 有 TASK_UPDATE 权限
negative:
  - 任务没有「整项目置顶」字段，只能调列内顺序
  - 排序变更不会进项目动态日志
  - 多人同时拖动时，最后一次写入覆盖前面（无冲突合并）
last_verified: v1.7.90
---

# 任务列内排序

## 是什么
DooTask 任务在每个 `column_id`（项目列）内有一个 `sort` 字段（整数），决定该列内的展示顺序。看板视图、列表视图均按 `sort` 升序展示。拖动任务卡上下移动会触发后端 `sort` API 重写整列的 `sort` 值。

## 入口
- 桌面端：看板 / 列表视图任意一列内
- 移动端：不支持拖动重排（按服务端默认 sort 显示）

## 操作步骤（看板视图）
1. 鼠标按住任务卡片
2. 上下拖动到列内目标位置
3. 松开后，服务端按列重排 `sort` 值（同时可能改 `column_id` 见 [[task.move.howto.column]]）

## 与「置顶」的关系
- DooTask 中**任务无单独置顶字段**
- 想让一条任务长期排首位，只能把它拖到当前列最上方
- 项目层面的「置顶」（`ProjectUser.top_at`）是把整个项目固定在侧边栏顶部，与任务排序无关

## 不支持
- 任务无项目级置顶
- 不能按字段（截止时间、优先级）自动排序，所有视图默认就是 sort 升序
- 移动端只读，不支持拖动
