---
id: task.field.visibility.concept
title: 任务可见性（3 级）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务谁能看
  - 任务可见范围
  - 任务保密
  - 仅自己可见
  - 指定成员可见
related_tools: [update_task, get_task]
related_pages: [task_detail]
prerequisites: []
negative:
  - 子任务不能单独设置可见性，强制继承父任务
  - 可见性只控制「能不能看到这个任务」，不控制评论、附件的二级权限
  - 项目管理员不会自动看到 visibility=2/3 的任务，除非被加入任务人员
last_verified: v1.7.90
---

# 任务可见性（3 级）

## 定义
DooTask 任务的可见性字段 `visibility` 取 3 个值：
- `1` — **项目人员可见**（默认）：项目中所有成员都能看到
- `2` — **任务人员可见**：仅负责人、协作者可见
- `3` — **指定成员可见**：负责人、协作者 + 显式加入「可见用户」列表的人可见

## 数据来源
- visibility=3 时，额外维护 `ProjectTaskVisibilityUser` 表，记录每个可见用户的 userid
- visibility 改回 1 / 2 时，可见用户记录会被清空

## 修改入口
- 任务详情页 → 「可见性」下拉
- 创建任务时也可在对话框里设置

## 子任务的继承
- 子任务的可见性强制等于父任务，无法在详情页单独切换
- 父任务改可见性时，子任务的可见判断也实时跟着变

## 与归档、删除的关系
- visibility 只决定「能不能看到任务」
- 看到任务后能否编辑还需要看 TASK_UPDATE 权限
- 归档、删除是独立状态，不受 visibility 控制

## 不支持
- 子任务不能独立设置可见性
- visibility 不能限制评论 / 附件单独可见
- 不能按部门 / 角色批量授可见，必须逐人
