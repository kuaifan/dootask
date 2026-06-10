---
id: search.project.howto
title: 搜索项目
type: howto
feature: search
scope: end-user
locale: zh
aliases:
  - 找项目
  - 搜项目
  - 怎么找项目
  - 项目搜不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 不会返回已归档项目（`archived_at` 不为空）
  - 不会返回当前用户没有加入的项目
  - 单次最多返回 50 条（默认 20）
last_verified: v1.7.90
---

# 搜索项目

## 入口
- 全局搜索框（[[search.entry.menu-map]]）输入关键词，切到「项目」分类
- 项目列表 / 项目选择器内的局部搜索复用同一接口

## 接口
- 端点：`GET api/search/project`
- 参数：
  - `key`（必填）：搜索关键词，空则返回空数组
  - `search_type`（可选）：`text` / `vector` / `hybrid`，默认 `hybrid`，仅 Manticore 生效
  - `take`（可选）：返回数量，默认 20，上限 50

## 匹配字段
装了 Manticore 时可命中：项目名、项目描述、文本类附加信息。MySQL 回退仅按项目名做 `LIKE` 模糊匹配。

## 权限范围
仅在「当前用户已加入的项目」范围内搜，使用 `Project::authData()` 限定。未加入的项目即使关键词匹配也不会返回。

## 返回字段（每条）
- 项目基础信息：`id` / `name` / `desc` / `owner_userid` / `created_at` 等
- `relevance`：相关度分（仅 Manticore，MySQL 回退恒为 0）
- `desc_preview`：命中描述片段（仅 Manticore）

## 不支持
- 已归档项目不返回（解归档后才能搜到，[[search.no-result.faq]]）
- 不在当前用户成员列表的项目不返回
- 不会单次返回超过 50 条结果（最多 50 条）

## 相关
- 引擎差异：[[search.engine.concept]]
- 搜不到怎么办：[[search.no-result.faq]]
