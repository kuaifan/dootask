---
id: search.contact.howto
title: 搜索联系人
type: howto
feature: search
scope: end-user
locale: zh
aliases:
  - 找人
  - 搜人
  - 搜同事
  - 怎么找联系人
  - 按技能找人
  - 搜邮箱
related_tools: [search_users]
related_pages: []
prerequisites: []
negative:
  - 不会返回已禁用账号（`disable_at` 不为空）
  - 不会返回机器人账号（`bot=1`）
  - 单次最多返回 50 条（默认 20）
last_verified: v1.7.90
---

# 搜索联系人

## 入口
- 全局搜索框（[[search.entry.menu-map]]）输入关键词，切换到「联系人」分类，或不切分类查看跨类结果
- 在新建群、邀请项目成员等成员选择器里也会复用联系人搜索

## 接口
- 端点：`GET api/search/contact`
- 参数：
  - `key`（必填）：搜索关键词，空串直接返回空数组
  - `search_type`（可选）：`text` / `vector` / `hybrid`，默认 `hybrid`，仅 Manticore 生效
  - `take`（可选）：返回数量，默认 20，上限 50

## 匹配字段
装了 Manticore 时可命中：昵称、邮箱、个人简介、技能标签。MySQL 回退仅按昵称 / 邮箱 / 部门做 `LIKE` 模糊匹配。

## 返回字段（每条）
- 用户基础信息：`userid` / `nickname` / `email` / `avatar` / `profession`
- `relevance`：相关度分（Manticore 才有，MySQL 回退恒为 0）
- `introduction_preview`：命中简介片段
- `search_tags`：命中的技能标签数组（最多 10 个，按认可数排序）

## 不支持
- 不会返回已禁用 / 已离职的账号
- 不会返回机器人账号
- 不会单次返回超过 50 条结果（最多 50 条）
- 无权访问的用户也搜不到

## 相关
- 引擎差异：[[search.engine.concept]]
- 入口与快捷键：[[search.entry.menu-map]]
