---
id: ai-assistant.match-elements.concept
title: AI 怎么找到页面元素
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - match_elements
  - AI 找按钮
  - 元素匹配
  - 向量匹配元素
  - AI 怎么知道点哪
  - AI 元素识别
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
negative:
  - 匹配纯依赖元素可见文本/aria-label/title，无文本的图标按钮命中率低
  - 单次匹配上限 50 个候选元素
  - 不持久化匹配结果，每轮交互需重新取上下文
last_verified: v1.7.90
---

# AI 怎么找到页面元素

## 定义
当 AI 想操作页面元素（点按钮、填表）时，先采集当前页面上下文拿到候选元素列表（每条含 ref / name / role），再通过后端 API `POST api/assistant/match-elements` 把"用户意图描述"和"候选列表"提交给 embedding 服务，返回按余弦相似度排序的命中元素。

## 工作流
1. **采集**：前端按 ARIA 角色扫描，给每个可交互元素分配 ref（e1, e2...）和 name
2. **关键词过滤**：先用 query 做子串匹配
3. **向量匹配**：关键词没命中时对 query 和元素 name 求 embedding，取相似度 top-K（默认 10，最多 50）
4. **执行**：模型拿匹配元素的 ref，让 AI 助手在你的页面上操作该元素

## 元素信息字段
- `ref`：本轮唯一标识（e1, e2...）
- `name`：可见文本 / aria-label / title
- `role`：ARIA 角色（button / textbox / link 等）
- `selector` + `nth`：兜底选择器

## 命中率
- 有清晰文本的按钮：高
- 仅 icon 无 aria-label：低，建议加 title
- 隐藏元素：默认不采集

## 不支持
- 元素匹配仅基于元素文本，不靠图像识别
- 不支持「按位置」找元素（"左上角第三个"）
- 不能跨 iframe 匹配

## 相关
- 取页面上下文：[[ai-assistant.page-context-tool.concept]]
- 操作元素：[[ai-assistant.element-action.howto]]
- 页面操作机制：[[ai-assistant.page-action.concept]]
