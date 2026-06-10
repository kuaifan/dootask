---
id: project.member.howto
title: 添加 / 移除项目成员，任命管理员
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 加项目成员
  - 删项目成员
  - 任命项目管理员
  - 罢免管理员
  - 项目里加人
related_tools: [search_users, get_project]
related_pages: [project_settings, project_member]
prerequisites:
  - 是项目拥有者（owner=1）或管理员（owner=2）
  - 任命 / 罢免管理员只能是拥有者
negative:
  - 不能移除项目拥有者本人（必须先转让拥有者再退）
  - 管理员不能移除其他管理员、不能罢免管理员
  - 加成员不需要对方同意（直接加入，[[project.dialog.concept]] 群也自动加）
last_verified: v1.7.90
---

# 添加 / 移除项目成员，任命管理员

## 入口
- 桌面端：项目顶部「⋯」 → 「成员管理」面板
- 也可：项目设置 → 「成员」

## 添加成员
1. 「+ 添加成员」搜框输入昵称 / 邮箱
2. 勾选目标用户（可多选）
3. 确认后服务端写 ProjectUser（owner=0），同步加进 [[project.dialog.concept]] 群聊
4. 成员立即看到项目，无需对方同意

## 移除成员
1. 在成员列表选成员 → 「移除」
2. 服务端删 ProjectUser、同步移出群聊
3. 该成员名下未完成任务的 owner 会被自动转给操作人 / 项目拥有者
4. 移除后该成员仍可在历史动态里被看到

## 任命管理员
- 只有拥有者可操作
- 成员行 → 「任命为管理员」 → owner 改为 2
- 管理员获得改项目设置 / 加删成员的权限

## 罢免管理员
- 只有拥有者可操作
- 成员行 → 「罢免管理员」 → owner 改回 0

## 不支持
- 管理员不能罢免其他管理员
- 不能直接移除拥有者：必须先 [[project.transfer.howto]] 转让
- 加成员后该成员立即可见所有 visibility=1（项目人员可见）的任务，无法分批授权
