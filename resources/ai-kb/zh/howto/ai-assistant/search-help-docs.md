---
id: ai-assistant.search-help-docs.howto
title: AI 帮我查 DooTask 怎么用
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 怎么用 DooTask
  - X 在哪
  - X 怎么操作
  - 帮我查文档
  - DooTask 怎么做 X
  - 找帮助
related_tools: [search_help_docs]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
negative:
  - 知识库只覆盖 DooTask 自身功能，不回答行业知识或通用 IT 问题
  - 中英文知识库分别检索，所选会话语种决定查哪边
  - 没找到资料时 AI 会明示「未在知识库找到」而不是编造
last_verified: v1.7.90
---

# AI 帮我查 DooTask 怎么用

## 这是什么
当你在 AI 浮窗里问 DooTask 功能怎么用，例如"任务怎么设置可见用户"、"报告模板在哪改"，AI 会自动触发 `search_help_docs` 工具，从内置知识库（本仓库）检索相关 chunk，再综合给出步骤。

## 怎么触发
直接用自然语言提问，无需特殊指令：

- "如何创建项目？"
- "权限不足提示怎么解决？"
- "看板列怎么加？"
- "微应用是什么？"
- "签到怎么补打？"

## 看 AI 调用了哪些资料
- 浮窗中会出现 `search_help_docs` 工具气泡，展开可看检索关键词
- 回答末尾通常会标注"参考自帮助文档"或附上相关功能名
- 若回答错了，可继续追问"你查到的是哪一篇？"，AI 会列出 chunk id

## 让 AI 查得更准的小技巧
- 一次只问一个具体功能，别堆三个问题
- 出错时把错误提示原文贴上，AI 会同时去查 FAQ 类 chunk
- 含产品名加上"DooTask"前缀（如"DooTask 的看板"）能避开开放知识

## 不支持
- 不支持「教我怎么编程」「我项目用 React 怎么写」等编程问题
- 英文知识库覆盖不全：知识库以中文为主，英文环境检索 en 库时覆盖度可能偏低

## 相关
- 入口：[[ai-assistant.entry.howto]]
- 跨任务/项目/文件的语义搜索：[[ai-assistant.intelligent-search.howto]]
