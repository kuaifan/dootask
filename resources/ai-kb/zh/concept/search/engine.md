---
id: search.engine.concept
title: 搜索引擎(Manticore 与 MySQL 回退)
type: concept
feature: search
scope: admin
locale: zh
aliases:
  - 搜索引擎
  - Manticore
  - 搜索插件
  - 搜索很慢
  - 为什么搜不到文件内容
  - 语义搜索
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 未安装 search 插件时无法搜索文件正文，只能按文件名模糊匹配
  - 未安装 search 插件时不支持语义 / 向量 / 混合搜索，只能精确关键词
  - 向量搜索还需要 ai 插件提供 Embedding，仅装 search 没装 ai 也走不了语义
last_verified: v1.7.90
---

# 搜索引擎(Manticore 与 MySQL 回退)

## 定义
DooTask 全局搜索底层有两套实现，按 `Apps::isInstalled('search')` 自动切换：装了应用市场的 search 插件就走 Manticore，没装就退回 MySQL `LIKE` 模糊查询。

## Manticore 搜索引擎
Manticore Search 是独立的开源搜索引擎，作为可选插件部署在 DooTask 的 appstore 里。能力包括：
- **全文搜索**（text）：倒排索引 + ICU 中文分词
- **向量搜索**（vector）：1536 维 KNN（HNSW 算法），需配合 AI 插件生成 Embedding
- **混合搜索**（hybrid，默认）：同时打全文和向量分，取加权综合排名
- **文件正文搜索**：可搜 Word / Excel / PPT / PDF / TXT / 代码文件等内部文本

接口参数 `search_type` 取值 `text` / `vector` / `hybrid`，默认 `hybrid`，**只在装了 Manticore 时生效**。

## MySQL 回退
未装 search 插件时调 `searchByKeyword`，用 MySQL `LIKE %keyword%` 匹配。能力受限：
- 只匹配字面关键词，不理解同义词或语义
- 不能搜文件正文（PDF / Office 内部文本搜不到）
- 大数据量下性能不如 Manticore
- 不支持 relevance 排序，按时间倒序

## 部署形态
插件镜像独立维护（appstore 应用：search），技术规格：
- 向量维度：1536，兼容 OpenAI text-embedding-3-small
- 中文分词：ICU Chinese
- 推荐至少 2GB 可用内存

## 何时升级到 Manticore
- 需要搜索文件内容（不仅是文件名）
- 用自然语言查文件（如「财务分析文档」→「Q3 收入报表」）
- 团队消息 / 任务量大，MySQL `LIKE` 已经慢
