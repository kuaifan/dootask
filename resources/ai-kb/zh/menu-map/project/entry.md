---
id: project.entry.menu-map
title: 项目入口在哪
type: menu-map
feature: project
scope: end-user
locale: zh
aliases:
  - 项目在哪
  - 怎么找项目
  - 项目入口
  - 项目列表
  - 进入项目
related_tools: [list_projects]
related_pages: [project_list, dashboard]
prerequisites: []
negative:
  - 已归档项目默认不在主列表，要在筛选切到「已归档」
  - 已删除项目仅在回收站可见
  - 个人项目（[[project.personal.concept]]）也在同一列表，按类型筛选
last_verified: v1.7.90
---

# 项目入口在哪

## 路径
- **桌面端**：左侧栏 → 「项目」分组 → 单击展开项目列表
- **桌面端仪表盘**：顶部「我的项目」卡片
- **桌面端右上「+」全局快捷**：「+ 添加项目」直接建项目（[[project.create.howto]]）
- **移动端**：底部 Tabbar → 「项目」Tab
- **桌面快捷键**：Cmd/Ctrl + P 唤起项目快搜

## 项目列表里能看到什么
- 项目名 + 图标 / emoji
- 项目类型标签（团队 / 个人）
- 任务进度（X/Y 完成）
- 置顶项目排在前面（top_at DESC）

## 默认筛选
- 「我加入的」（默认）：仅显示 ProjectUser 命中你的
- 「全部」：站点管理员可见全站
- 「已归档」：archived_at 非空的项目
- 按时间范围、按项目名搜

## 权限要求
- 任何登录用户都能看到项目入口
- 看到具体项目要求你已加入（ProjectUser），管理员可见全部
- 加入项目方式：[[project.member.howto]] 被加 / [[project.invite.howto]] 链接邀请

## 不支持
- 没有"未加入的可申请项目"列表，无法主动申请加入
- 没有项目分组 / 文件夹（移动端 / 桌面端均扁平）
