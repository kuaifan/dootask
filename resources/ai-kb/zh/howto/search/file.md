---
id: search.file.howto
title: 搜索文件
type: howto
feature: search
scope: end-user
locale: zh
aliases:
  - 找文件
  - 搜文件
  - 搜文档内容
  - 怎么搜文件内容
  - PDF 搜索
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 未装 search 插件时只能搜文件名，搜不到正文
  - Office 文件超过 50MB / 文本超过 5MB / 其他类型超过 20MB 不索引正文
  - 提取后正文超过 10 万字符会被截断
  - 单次最多返回 50 条（默认 20）
last_verified: v1.7.90
---

# 搜索文件

## 入口
- 全局搜索框（[[search.entry.menu-map]]）输入关键词，切到「文件」分类
- 文件管理器（左侧栏「文件」）内的局部搜索复用同一接口

## 接口
- 端点：`GET api/search/file`
- 参数：
  - `key`（必填）：搜索关键词，空则返回空数组
  - `search_type`（可选）：`text` / `vector` / `hybrid`，默认 `hybrid`，仅 Manticore 生效
  - `take`（可选）：返回数量，默认 20，上限 50

## 匹配字段
- 装 Manticore：文件名 + 正文（支持 Word / Excel / PPT / PDF / TXT / Markdown / 代码文件等）
- 未装 Manticore：仅文件名 `LIKE` 模糊匹配，先查用户自己的文件，再补充共享给当前用户的文件

可索引正文的类型：`document` / `word` / `excel` / `ppt` / `txt` / `md` / `text` / `code`。

## 权限范围
只返回当前用户**自己上传**或**被共享给当前用户**的文件，且按 Manticore 索引的 `allowed_users` 二次过滤。

## 返回字段（每条）
- 文件基础信息：`id` / `name` / `type` / `ext` / `size` / `userid` / `pid` 等
- `relevance`：相关度分（仅 Manticore，MySQL 回退恒为 0）
- `content_preview`：命中正文片段（仅 Manticore）

## 不支持
- 未装 search 插件搜不到文件内部文本，搜不到内容 → [[search.engine.concept]]
- 大文件不索引正文（限额见 frontmatter `negative`）
- 不会单次返回超过 50 条结果（最多 50 条）
