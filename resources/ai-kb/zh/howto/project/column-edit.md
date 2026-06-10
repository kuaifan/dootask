---
id: project.column.howto.edit
title: 编辑项目列（改名 / 颜色 / 排序）
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 改列名
  - 改列颜色
  - 列重命名
  - 调整列顺序
  - 看板列改名
related_tools: [get_project]
related_pages: [project_kanban, project_settings]
prerequisites:
  - 有 TASK_LIST_UPDATE / TASK_LIST_SORT 权限
negative:
  - 改列名 / 颜色不影响列内任务的 column_name 快照（仅看板色条变化）
  - 拖动改顺序只调 sort，不会迁移任务
  - 列绑定工作流节点时，改列名不同步改节点名
last_verified: v1.7.90
---

# 编辑项目列（改名 / 颜色 / 排序）

## 入口
- 桌面端：项目看板视图 → 列标题右侧「···」菜单，里面有「修改」「归档」「删除」和预设色板

## 改列名
1. 点列标题右侧「···」→「修改」
2. 弹出「修改列表」弹窗，输入新列表名称（不能为空）
3. 点确定保存，服务端写入后 WebSocket 推送给所有项目成员

## 改颜色
1. 「···」菜单下半部分是预设色板（色块在左、色名在右）
2. 点击某色即设为列头底色；再次点击当前已选色取消颜色
3. 颜色只能从预设色板选，不支持自定义十六进制

## 归档 / 删除列
- 「归档」：归档该列所有已完成任务（二次确认）
- 「删除」：删除列及列内任务（二次确认）

## 调整排序
- 直接左右拖动列头
- 服务端按位置重写整组列的 `sort` 字段，不改任何任务字段

## 与工作流的联动
- 列绑定工作流节点（[[project.flow.howto.edit]] 的 columnid）时：改列名、改列顺序都**不同步**到工作流节点

## 不支持
- 不支持双击列名直接改名，必须走「···」→「修改」弹窗
- 列名不强制唯一（允许同名）
- 不能批量改多列颜色
- 不能给列单独配权限（仅整项目 TASK_LIST_UPDATE 一档）
