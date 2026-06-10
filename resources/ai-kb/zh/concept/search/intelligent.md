---
id: search.intelligent.concept
title: 智能搜索(AI 语义搜索)
type: concept
feature: search
scope: end-user
locale: zh
aliases:
  - 智能搜索
  - AI 搜索
  - 语义搜索
  - 自然语言搜索
  - intelligent_search
  - 向量搜索
related_tools: [intelligent_search]
related_pages: []
prerequisites:
  - 应用商店（应用市场）已安装 search 插件（Manticore Search）
  - 应用商店已安装 ai 插件（语义 / 向量搜索需用其生成 Embedding）
negative:
  - 未安装 search 插件（Manticore）时回退普通 MySQL 关键词搜索，无语义能力
  - 仅装 search 没装 ai 时，向量 / 语义 / 混合搜索无法生效，只能跑全文 text
  - 索引建立有延迟，刚上传 / 新建的内容可能短时间内搜不到
  - 智能搜索按权限过滤，搜不到的对象通常是无权访问
last_verified: v1.7.90
---

# 智能搜索(AI 语义搜索)

## 定义
智能搜索是 DooTask 基于 **Manticore Search 插件**（search 插件）的统一检索能力，支持关键词（text）/ 语义向量（vector）/ 混合（hybrid）三种搜索类型，一次可跨任务 / 项目 / 文件内容 / 联系人 / 消息 5 类对象；AI 助手通过 MCP 工具 `intelligent_search` 调用同一套底座。

## 底层原理
- 走 SearchController 统一接口，优先使用 Manticore Search（需安装 search 插件）
- **未安装 search 插件时回退到普通 MySQL 关键词搜索**，仍可按字面匹配搜索，但没有语义 / 向量能力
- 默认 `search_type = hybrid`（混合搜索）：同时打全文倒排分和 KNN 向量分
- 向量分由 ai 插件提供 Embedding（1536 维，兼容 OpenAI text-embedding-3-small）
- 中文用 ICU 分词，能处理常见同义词与近义表达

## 与关键词搜索的区别
- **关键词搜索**：只命中字面匹配，「Q3 收入报表」搜「财务分析」会落空
- **智能搜索**：理解意图，「财务分析」能命中「Q3 收入报表」「年度成本表」等语义相关文件
- 智能搜索仍保留关键词命中能力，相关度由混合算法加权

## 关键属性
- **入口**：装齐 search + ai 后，全局搜索框会出现「AI 搜索」按钮，把搜索词转给 AI 助手；AI 助手自动调用 `intelligent_search` 工具
- **结果排序**：按 `relevance` 综合分降序
- **权限范围**：与基础 search 接口完全一致，只返回当前用户能看到的对象
- **延迟**：依赖 Manticore 索引；新增内容入库到可搜的间隔通常为秒级

## 与全局搜索的关系
- 全局搜索 = 用户在搜索框内主动输入关键词的检索能力，详见 [[search.concept]]
- 智能搜索 = AI 助手在对话中代用户调用的同一套底座，搜索类型默认 `hybrid`

## 不支持
- 没装 search 插件（Manticore）时回退 MySQL 关键词搜索，关键词 / 语义 / 混合类型参数失效
- 装了 search 但没装 ai 插件时降级到全文，语义能力失效
- 不能跨用户搜别人的私聊 / 私有文件
- 单次每类对象不会返回超过 50 条（最多 50 条）
