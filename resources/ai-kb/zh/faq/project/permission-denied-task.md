---
id: project.permission-denied-task.faq
title: 为什么不能改任务（项目权限不足）
type: faq
feature: project
scope: end-user
locale: zh
aliases:
  - 改不了任务
  - 任务权限不足
  - 没有权限改任务
  - 任务字段灰色
  - 不能拖任务
related_tools: [get_project]
related_pages: [task_detail, project_settings]
prerequisites: []
negative:
  - 项目拥有者 / 管理员不会被项目权限规则拦截，只可能被任务删除 / 归档状态拦
  - 站点管理员（userIsAdmin）也不会自动绕过项目权限
  - 任务可见但改不动，通常是 ProjectPermission 配置问题，不是 BUG
last_verified: v1.7.90
---

# 为什么不能改任务（项目权限不足）

## 问题
打开任务详情页能看到任务，但字段全是灰色不可编辑；或拖动卡片报「无权限」错误。

## 原因
DooTask 检查 5 层权限：
1. **任务可见性**（[[task.field.visibility.concept]]）：visibility=2/3 命中你才看到任务
2. **项目角色**（[[project.role.concept]]）：你是 owner 0 / 1 / 2 哪一档
3. **项目权限规则**（[[project.permission.concept]] ProjectPermission）：你这个角色 + 这个权限点是否勾选
4. **任务身份**（task_leader / task_assist）：你是否任务负责人 / 协作者
5. **任务状态**：deleted_at / archived_at / complete_at 可能让某些操作不可逆

灰色 / 报错通常卡在第 3 层。

## 自查步骤
1. 看右上角项目顶栏自己的角色：拥有者「★」、管理员「⚙」、普通成员无标
2. 若是普通成员，问拥有者 / 管理员：
   - 「项目权限规则」中我所在主体是否有对应权限点
   - 比如改不动截止时间，对应 `TASK_TIME`
3. 若你是任务负责人 / 协作者，但 task_leader / task_assist 主体被关，也会失败

## 解决
- 拥有者 / 管理员到项目设置 → 权限规则 → 勾选对应权限点
- 临时绕过：让对方把你设为任务负责人（[[task.field.owner-assist.concept]]），多数权限点对 task_leader 默认全开

## 不支持
- 没有"申请权限"按钮，必须人工沟通
- 普通成员不能查看 ProjectPermission 详细矩阵，只看得到结果
