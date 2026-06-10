---
id: task.subtask.howto.create
title: 创建子任务
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 加子任务
  - 拆任务
  - 新增子任务
  - 父任务下加待办
  - 怎么建 subtask
related_tools: [create_sub_task]
related_pages: [task_detail]
prerequisites:
  - 父任务可见，有 TASK_UPDATE 权限
  - 父任务自身不是子任务（不允许嵌套）
negative:
  - 单个父任务最多 50 个子任务
  - 子任务不能再嵌套子任务（一级深）
  - 子任务必须与父任务在同一项目，不能跨项目
last_verified: v1.7.90
---

# 创建子任务

## 是什么
子任务是挂在某个父任务下的下级任务，用 `parent_id` 维护父子关系。子任务有独立的负责人、截止时间、状态，但**继承父任务的可见性**与项目归属。详细定义见 [[task.subtask.concept]]。

## 入口
- 桌面端：父任务详情页 → 「子任务」区 → 「+ 新增子任务」
- 桌面端：在任务详情页底部展开子任务列表
- 移动端：任务详情页向下滑动到子任务区，点底部「+」

## 操作步骤
1. 在子任务区点 + 号
2. 输入子任务名称（必填，≤255 字符）
3. 回车保存，子任务出现在列表底部
4. 点子任务行可继续设置负责人、截止时间、描述

## 字段默认值
- 负责人：当前用户
- `parent_id`：父任务 id
- `project_id`、`column_id`：继承父任务
- 可见性：继承父任务（不可单独配置）

## 不支持
- 子任务**不能**自己再有子任务（最多 1 层）
- 子任务**不能**单独配置可见性、对话、附件
- 不能把已有任务转成另一个任务的子任务（只能新建）

具体限制详见 [[task.subtask.limits.concept]]。
