---
id: task.template.howto
title: 任务模板的创建与使用
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 任务模板
  - 怎么存任务模板
  - 重复用的任务模板
  - 模板创建任务
  - 怎么把任务存成模板
related_tools: [create_task]
related_pages: [project_detail, task_template]
prerequisites:
  - 已加入目标项目，有 TASK_ADD 权限
negative:
  - 任务模板不能跨项目共享，除非站点设置 task_template_share 开启
  - 模板不保存附件，只保存标题、内容、字段
  - 子任务模板不支持嵌套子任务
last_verified: v1.7.90
---

# 任务模板的创建与使用

## 是什么
任务模板（ProjectTaskTemplate）把常用的任务标题 + 描述 + 字段保存下来，下次新建任务时一键套用。每个模板属于一个项目，全局共享需要管理员在系统设置打开 `task_template_share`。

## 入口
- 桌面端：项目详情页「+ 添加任务」对话框 → 顶部「模板」下拉
- 桌面端：项目设置 → 「任务模板」可统一管理（增删改默认模板）

## 创建模板
1. 进入项目设置 → 任务模板 → 「+ 新增模板」
2. 填模板名（用于下拉显示）、任务标题、富文本内容
3. 可选标记 `is_default` 让它作为新建任务的默认套用项

## 使用模板
1. 在新建任务对话框顶部下拉里选模板
2. 标题与描述会自动填入，可继续编辑后保存
3. 每次使用会自增模板的 `use_count` 与 `last_used_at`

## 不支持
- 模板不携带附件、子任务列表
- 模板默认按项目隔离，开启 `task_template_share` 后所有项目可见，但不可分项目权限
