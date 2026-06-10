---
id: common-faq.permission-cannot-edit-project.faq
title: 不能编辑项目设置
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 不能编辑项目
  - 改不了项目名
  - 项目设置灰的
  - 项目设置打不开
  - 为什么改不了工作流
  - 项目设置无权限
related_tools: []
related_pages: [project_detail, project_settings]
prerequisites: []
negative:
  - 普通成员（非负责人）无法改项目名、流程、列、权限等结构性设置
  - 项目负责人也不能改超出本项目范围的设置（如系统级模板）
  - 没有「临时编辑权限」概念，必须先调角色再操作
last_verified: v1.7.90
---

# 不能编辑项目设置

## 问题
打开项目设置发现按钮变灰，或保存时弹「权限不足」「您不是项目负责人」「无权操作」。改项目名、改工作流、删列、改成员都不能用。

## 原因
DooTask 项目角色分三级，编辑权限按角色细分：

- **成员（member）**：能看任务、改自己负责的任务，不能改项目结构
- **负责人（owner）**：可改项目名 / 描述 / 流程 / 列模板 / 成员 / 删除项目；一个项目可有多个负责人
- **系统管理员**：能强制改任意项目（包括接管负责人为空的孤儿项目）

普通成员看到「编辑项目」按钮也能点开表单，但提交时后端拦截。

## 解决
1. 在项目详情页点右上角「成员」查看自己的角色徽章
2. 若显示「成员」，找现任负责人在「成员管理」把你升级为「负责人」：参考 [[role-permission.transfer-owner.howto]]
3. 若现任负责人已离职 / 找不到人，让系统管理员介入接管
4. 若只是想改个别任务，无需升级——直接成为任务负责人即可改任务自身

## 不支持
- 不能临时申请项目编辑权限，必须先调角色
- 角色变更后无需刷新，前端 socket 推送会立即生效

[[role-permission.permission-denied.faq]] / [[role-permission.project-role.concept]]
