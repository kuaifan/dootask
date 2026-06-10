---
id: project.transfer.howto
title: 移交项目（转让项目负责人）
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 转让项目
  - 项目交接
  - 改项目负责人
  - 离职交接项目
  - 移交项目
related_tools: [update_project]
related_pages: [project_settings]
prerequisites:
  - 当前用户是项目主负责人（owner=1）
negative:
  - 移交后原主负责人变回普通成员（owner=0），不会自动变成项目管理员
  - 移交动作没有回滚按钮，需要新负责人再移交回来
  - 项目管理员（owner=2）无法发起移交，仅主负责人可操作
last_verified: v1.7.90
---

# 移交项目（转让项目负责人）

## 是什么
DooTask 项目主负责人（owner=1）每项目唯一，是项目内最高权限角色。「移交项目」把这个角色让给另一名用户，原主负责人降为普通成员（owner=0）。常见于团队负责人变更、离职交接、内部组织调整。

## 入口
- 桌面端：打开项目页 → 右上角项目「···」下拉菜单 → 「移交项目」（仅项目主负责人可见此菜单项）

## 操作步骤
1. 弹出「移交项目」弹窗，在「新项目负责人」选择一名用户（单选；可以不是当前项目成员，移交后自动加入项目）
2. 点「移交」确认提交
3. 服务端：
   - 改 ProjectUser：原主负责人 owner=1→0，新负责人设为 owner=1（原是项目管理员则从 2 升为 1）
   - 其他项目管理员（owner=2）身份保留
   - 同步 [[project.dialog.concept]] 项目群聊的 owner_id（群归属一并变更）
   - Project 表的 `userid`（创建人）不变（保留历史）
4. ProjectLog 记一条「移交项目给 X」

## 不支持
- 移交不会顺带把任务负责人一起转，需要单独改任务字段
- 移交后没有回滚按钮，需新负责人再移交一次
- 项目管理员（owner=2）和普通成员看不到「移交项目」菜单
