---
id: project.create.howto
title: 创建项目
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 怎么建项目
  - 新建项目
  - 开个项目
  - 创建团队项目
  - 新建工作空间
related_tools: [create_project]
related_pages: [project_list, dashboard]
prerequisites:
  - 站点设置 project_add_permission 命中你的角色（详见 [[project.permission-to-create.faq]]）
negative:
  - 个人项目（personal=1）每个用户只能有 1 个
  - 项目名称必填，≤100 字符
  - 创建时启用工作流后无法再回退到「无工作流」模式
last_verified: v1.7.90
---

# 创建项目

## 入口
- 桌面端：左侧栏「项目」分组顶部「+」按钮 → 弹窗
- 桌面端：仪表盘空状态 → 「+ 新建项目」
- 移动端：项目 Tab → 右上角「+」

## 操作步骤
1. 输入项目名称（必填，≤100 字符），可加 emoji 当图标
2. 可选填项目描述
3. 选「项目模板」（系统预置的默认列模板，如开发 / 简单看板）
4. 选是否启用工作流（[[project.flow.concept.default]]）
5. 点「创建」，服务端生成 Project + ProjectColumn + WebSocketDialog 群聊

## 默认结构
- 默认创建你为「项目负责人」（owner=1）
- 默认 3-5 列（按选中的模板，可后续 [[project.column.howto.add]] 增删）
- 自动建项目群聊（[[project.dialog.concept]]），把你拉进群
- ProjectLog 记一条「X 创建了项目 Y」

## 团队项目 vs 个人项目
- 默认创建团队项目（personal=0），可邀请他人
- 想建只给自己用的项目，选「个人项目」（personal=1），详见 [[project.personal.concept]]

## 不支持
- 创建时不能直接指定第一批成员，需创建后再 [[project.member.howto]] 邀请
- 不支持把已有任务在创建时批量导入，需要走 [[task.move.howto.cross-project]]
