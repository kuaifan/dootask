---
id: task.field.owner-assist.concept
title: 负责人与协作者
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务负责人
  - 协作者
  - assist
  - owner
  - 任务参与人
related_tools: [update_task, get_task]
related_pages: [task_detail]
prerequisites: []
negative:
  - 指派候选人必须是项目成员
  - 协作者不能完成任务，只有负责人能（或有 TASK_UPDATE 权限的项目角色）
  - 负责人 / 协作者不会自动获得评论权限以外的项目权限
last_verified: v1.9.18
---

# 负责人与协作者

## 定义
DooTask 通过 `ProjectTaskUser` 表维护任务参与人：每条记录 `userid + owner`：
- `owner=1` — **负责人**
- `owner=0` — **协作者**（assist）

一个任务允许多个负责人（多 owner=1）和多个协作者，也可以暂时没有负责人。

## 设置入口
- 已有负责人：打开任务详情，在「负责人」字段中增删项目成员
- 暂无负责人：既可以通过顶部「领取任务」将自己设为负责人，也可以在详情底部的「添加」菜单选择「负责人」，再指定项目成员
- 「负责人」和「协助人员」未设置时都收纳在详情底部的统一「添加」菜单中，设置后显示为独立字段
- 系统开启任务流转后，有权限的用户也可在「流转」中指派负责人并附带留言；部门视角使用单独的授权及人员范围，见 [[task.handoff.howto]]。

## 权限差异

| 行为 | 负责人 | 协作者 |
|---|---|---|
| 编辑字段 | ✓ | 仅评论 / 查看 |
| 标记完成 | ✓ | ✗ |
| 增删子任务 | ✓ | ✗ |
| 删除任务 | ✓ | ✗ |
| 接收提醒推送 | ✓ | ✓ |
| 出现在「我的任务」列表 | ✓ | ✓ |

## 关系
- 添加负责人 / 协作者只能从「项目成员」中选；不会因此自动加入项目
- 用户退出项目时会移除对应任务身份，流转记录保留实际负责人变化
- 删除用户时，名下任务的 owner 会被转给操作人或项目负责人

## 与可见性的关系
- 不论 visibility 设几，负责人 / 协作者**总是可见**该任务
- visibility=2「任务人员可见」就是只让 ProjectTaskUser 表内的人能看
- visibility=3 时，可见人 = 负责人 + 协作者 + 额外 ProjectTaskVisibilityUser

详细可见性见 [[task.field.visibility.concept]]。

## 不支持
- 不能"匿名分配"
- 不能为协作者单独设权限粒度（如允许改截止时间但不允许改名称）
