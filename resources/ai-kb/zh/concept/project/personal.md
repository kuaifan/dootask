---
id: project.personal.concept
title: 个人项目 vs 团队项目
type: concept
feature: project
scope: end-user
locale: zh
aliases:
  - 个人项目
  - 私人项目
  - 团队项目
  - 个人和团队项目区别
  - personal 项目
related_tools: [create_project, list_projects]
related_pages: [project_list]
prerequisites: []
negative:
  - 个人项目每个用户只能有 1 个
  - 个人项目不能邀请其他成员
  - 个人项目不能开启工作流
last_verified: v1.7.90
---

# 个人项目 vs 团队项目

## 定义
DooTask 用 `personal` 字段区分两类项目：
- `personal=0` — **团队项目**（默认）：可邀请多人协作
- `personal=1` — **个人项目**：仅创建者自己可见可改，每用户限 1 个

## 关键差异

| 维度 | 团队项目 | 个人项目 |
|---|---|---|
| 数量上限 | 不限 | 每用户 1 个 |
| 邀请成员 | ✓ | ✗ |
| 群聊（dialog_id） | 自动建群聊，全员加入 | 仅自己 |
| 工作流（flow） | ✓ | ✗ |
| 权限角色 | 拥有者 / 管理员 / 成员 | 仅拥有者 |
| 创建权限管控 | 受站点 project_add_permission 限制 | 不受限制 |
| 转让（transfer） | ✓ | ✗ |

## 用途
- **个人项目**：用作自己的待办列表，把私事 / 学习 / 副业任务藏起来
- **团队项目**：所有协作场景，包括开发、产品、运营、客户跟进

## 互转
- 个人项目不能直接转团队项目
- 想"开放"个人项目，需要新建团队项目，再用 [[task.move.howto.cross-project]] 把任务搬过去

## 不支持
- 不能在个人项目里邀请协作者（任务的 owner / assist 都只能是自己）
- 不能给个人项目设置工作流、权限规则
- 站点限制项目创建权限时，个人项目不受限（始终可创建）
