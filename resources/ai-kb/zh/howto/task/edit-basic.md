---
id: task.edit.howto.basic
title: 编辑任务基本信息（标题 / 描述 / 负责人）
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 改任务名
  - 改任务描述
  - 改任务负责人
  - 怎么编辑任务
  - 修改任务内容
related_tools: [update_task]
related_pages: [task_detail]
prerequisites:
  - 任务可见（你是负责人 / 协作者 / 项目成员且 visibility 命中）
  - 改负责人需要项目 TASK_UPDATE 权限或是当前负责人
negative:
  - 改字段会即时写入并广播到所有项目成员，不需要点保存
  - 任务名为空无法保存
  - 改完成时间(complete_at) 和归档时间(archived_at) 不能直接编辑，必须用 [[task.complete.howto]] / [[task.archive.howto]]
last_verified: v1.7.90
---

# 编辑任务基本信息（标题 / 描述 / 负责人）

## 入口
- 桌面端：项目详情页 → 任意视图点击任务卡 → 详情页弹出
- 移动端：任务列表点击任务 → 详情页

## 可即时编辑的字段
- 标题（≤255 字符）
- 描述（富文本，≤50000 字符）
- 负责人（owner=1 的用户，可多人）
- 协作者（owner=0 的用户）
- 优先级（详见 [[task.field.priority.concept]]）
- 标签（详见 [[task.field.tag.concept]]）
- 计划时间（详见 [[task.field.deadline.concept]]）
- 可见性（详见 [[task.field.visibility.concept]]）
- 颜色（详见 [[task.field.color.concept]]）

## 编辑方式
1. 点击对应字段
2. 直接修改，失焦或回车即时保存
3. 服务端 `update_task` 写入并通过 WebSocket 推送到项目所有在线成员

## 不支持
- 字段无版本回滚，只有描述区会保留历史（ProjectTaskContent 表）
- 编辑不弹「确认」对话框，所有改动立即生效
- 已删除任务无法编辑，需要先 [[task.delete-restore.howto]] 恢复
