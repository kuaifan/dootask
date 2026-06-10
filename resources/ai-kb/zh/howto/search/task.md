---
id: search.task.howto
title: 搜索任务
type: howto
feature: search
scope: end-user
locale: zh
aliases:
  - 找任务
  - 搜任务
  - 找待办
  - 怎么搜到子任务
  - task 搜索
related_tools: [list_tasks]
related_pages: []
prerequisites: []
negative:
  - 不会返回已归档任务（`archived_at` 不为空）
  - 不会返回已删除任务（`deleted_at` 不为空）
  - 不会返回当前用户所在项目以外的任务
  - 单次最多返回 50 条（默认 20）
last_verified: v1.7.90
---

# 搜索任务

## 入口
- 全局搜索框（[[search.entry.menu-map]]）输入关键词，切到「任务」分类
- 项目内 / 看板内的局部搜索也复用同一接口

## 接口
- 端点：`GET api/search/task`
- 参数：
  - `key`（必填）：搜索关键词，空则返回空数组
  - `search_type`（可选）：`text` / `vector` / `hybrid`，默认 `hybrid`，仅 Manticore 生效
  - `take`（可选）：返回数量，默认 20，上限 50

## 匹配字段
装了 Manticore 时可命中：任务名、任务描述、子任务内容、备注等正文。MySQL 回退仅按任务名做 `LIKE` 模糊匹配，描述里的关键词搜不到。

## 权限范围
仅在「当前用户已加入的项目」内的任务范围搜索，且排除已归档、已删除任务。子任务也会被命中并返回。

## 返回字段（每条）
- 任务基础信息：`id` / `name` / `desc` / `start_at` / `end_at` / `complete_at` 等
- 关联信息：`task_user`（负责人 / 协作者）、`task_tag`（任务标签）
- `relevance`：相关度分（仅 Manticore）
- `desc_preview`：命中描述片段
- `content_preview`：命中子任务 / 内容片段

## 不支持
- 已归档 / 已删除任务不返回（先恢复才能搜到）
- 跨项目时只能搜当前用户参与的项目
- 不会单次返回超过 50 条结果（最多 50 条）

## 相关
- 引擎差异：[[search.engine.concept]]
- 搜不到怎么办：[[search.no-result.faq]]
