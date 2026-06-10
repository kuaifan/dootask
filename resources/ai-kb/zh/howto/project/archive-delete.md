---
id: project.archive-delete.howto
title: 项目归档 / 删除 / 恢复
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 归档项目
  - 删除项目
  - 项目恢复
  - 项目隐藏
  - 项目结束了怎么收起
related_tools: [update_project]
related_pages: [project_settings, project_list]
prerequisites:
  - 是项目拥有者（owner=1）
negative:
  - 项目管理员（owner=2）不能归档 / 删除项目
  - 删除项目会软删所有任务、列、标签、工作流，但不立即清理磁盘
  - 归档项目仍在搜索、可访问；只是从默认列表收起
last_verified: v1.7.90
---

# 项目归档 / 删除 / 恢复

## 三种状态

| 状态 | 字段 | 列表展示 | 可恢复 |
|---|---|---|---|
| 正常 | 无 | ✓ | n/a |
| 归档 | archived_at 非空 | 「已归档」筛选下 | 取消归档即可 |
| 删除 | deleted_at 非空 | 不显示 | 回收站恢复 |

## 入口
- 桌面端：项目顶部「⋯」 → 「归档项目」/「删除项目」
- 桌面端：项目列表「已归档」筛选 → 单项「取消归档」
- 回收站：管理后台 → 系统设置 → 数据回收

## 归档操作
1. 拥有者点「归档项目」
2. 写入 `archived_at` + `archived_userid`
3. 项目从默认列表收起，群聊（dialog_id）不被清理
4. 成员能在「已归档」筛选下找到，仍可读，但不能修改

## 删除操作
1. 拥有者点「删除项目」并二次确认
2. 写入 `deleted_at`，级联软删所有任务、列、工作流、标签
3. 群聊同步软删（成员从群里看不到这个对话）
4. ProjectLog 留一条删除记录

## 恢复
- 归档：在已归档列表点「取消归档」，立即恢复
- 删除：进回收站找到项目 → 「恢复」，任务 / 列 / 标签一并恢复
- 普通成员只能恢复自己删除的项目，看不到他人删除的

## 自动归档
- 在 [[project.update.howto]] 设置 `archive_method=custom + archive_days=N`
- 任务完成 N 天后自动归档对应任务（不归档整个项目）

## 不支持
- 个人项目（[[project.personal.concept]]）不能转给他人后再删
- 项目删除不会自动通知成员，需要手动告知
