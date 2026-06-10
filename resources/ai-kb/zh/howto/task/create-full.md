---
id: task.create.howto.full
title: 完整创建任务（详情页所有字段）
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 完整建任务
  - 怎么填所有字段
  - 创建任务时设置负责人
  - 创建任务怎么加截止时间
  - 完整模式建任务
related_tools: [create_task, update_task]
related_pages: [project_detail, task_detail]
prerequisites:
  - 已加入目标项目，且对该项目有 TASK_ADD 权限
negative:
  - 任务名称必填且最长 255 字符
  - 描述富文本最长 50000 字符
  - 一个任务最多 50 个子任务
last_verified: v1.7.90
---

# 完整创建任务（详情页所有字段）

## 入口
- 桌面端：项目详情页右上角「+ 添加任务」按钮 → 弹出完整任务对话框
- 也可以先用 [[task.create.howto.quick]] 快速建一条空壳，再进入任务详情页补字段

## 可设置字段
1. **名称**（必填，≤255 字符）
2. **描述**（富文本，≤50000 字符）
3. **所属列**（默认项目第一列；可下拉选）
4. **优先级**（取项目级或全局优先级配置，详见 [[task.field.priority.concept]]）
5. **负责人 / 协作者**（详见 [[task.field.owner-assist.concept]]）
6. **可见性**（项目人员可见 / 任务人员可见 / 指定成员，详见 [[task.field.visibility.concept]]）
7. **计划时间**（start_at / end_at，详见 [[task.field.deadline.concept]]）
8. **标签**（项目内独立，详见 [[task.field.tag.concept]]）
9. **颜色**（color 字段，覆盖优先级颜色）
10. **子任务**（最多 50 条）
11. **附件**（创建后才能上传）

## 操作步骤
1. 填上述字段，必填项是名称
2. 点「保存」，任务出现在所选列底部
3. 创建后可继续打开详情页改字段，所有改动即时写入并广播给项目成员

## 不支持
- 创建时不能直接上传附件，必须保存后再传
- 不允许任务名称为空（会被服务端拒绝）
