---
id: favorite.remove.howto
title: 取消收藏
type: howto
feature: favorite
scope: end-user
locale: zh
aliases:
  - 取消收藏
  - 移除收藏
  - 取消加星
  - 删除收藏
  - 怎么取消我的收藏
related_tools: []
related_pages: [application, task_detail, project_detail]
prerequisites: []
negative:
  - 取消收藏不会删除原对象本身（任务 / 项目 / 文件 / 消息 都还在）
  - 取消后无法撤销，再次收藏需重新点击
  - 不支持批量取消所有收藏（接口存在但前端未暴露入口）
last_verified: v1.7.90
---

# 取消收藏

## 两种入口
取消收藏与添加收藏共用一个 toggle 接口，已收藏的对象再次操作即为取消。

### 在对象本身上取消
进入已收藏对象（任务 / 项目 / 文件 / 消息）的详情或右键菜单，点击「取消收藏」/「取消加星」按钮。按钮文字会根据当前收藏态变化。

### 在「我的收藏」列表中取消
入口：左侧栏「应用」→「我的收藏」（见 [[favorite.entry.menu-map]]）

操作步骤：
1. 顶部「收藏类型」筛选到对应类型（任务 / 项目 / 文件 / 消息）
2. 找到目标行
3. 点击该行的「取消收藏」操作按钮
4. 列表中该行立即消失

## 效果
- 收藏关系从 `user_favorites` 表删除
- 备注一同丢失，无法恢复
- 原任务 / 项目 / 文件 / 消息本体不受影响
- 不会通知任何人

## 与「最近打开」的区别
取消收藏只移除「我的收藏」记录，**不影响「最近打开」**。最近打开是系统按浏览时间维护的独立列表，见 [[favorite.recent.concept]]。

## 不支持
- 不支持「一键清空全部收藏」前端按钮（后端 cleanUserFavorites 存在但未暴露）
- 取消后无回收站，不可撤销
- 不能恢复历史收藏（如对象已被删除）
