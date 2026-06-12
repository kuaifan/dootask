---
id: ai-assistant.search-files.howto
title: 让 AI 帮我找文件
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 找文件
  - 帮我找文档
  - AI 查文件
  - 我上传过的 X 文件
  - 找上次那个表
  - 谁分享过 X
related_tools: [search_files]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 仅搜当前用户可见的文件（自己上传的 + 项目共享的 + 群里共享的）
  - 不能搜本地未上传到 DooTask 的文件
  - 文件内容搜索仅对支持解析的格式有效（txt/md/docx/pdf/xlsx 等）
last_verified: v1.7.90
---

# 让 AI 帮我找文件

## 这是什么
在 AI 浮窗描述要找的文件，AI 调 `search_files` 工具按文件名 / 内容关键词 / 创建人 / 时间检索，返回文件名、路径、所有者、修改时间，常带跳转链接。

## 怎么问
- "找一下我上周上传的 PRD 文档"
- "搜叫『2026 年规划』的 Excel"
- "项目 X 里所有 .pdf"
- "小王最近共享给我的文件"
- "包含『接口文档』关键字的文件"

## 内容搜索 vs 文件名搜索
- 提问含"文件名"、"叫…"时按文件名匹配
- 提问含"包含…"、"提到…"、"关于…"时按内容关键词匹配（需后端文件内容索引可用）

## 找到文件能继续做什么
- "打开第一个" → AI 在你的页面上跳到文件预览
- "下载它" → AI 会给下载链接（无法直接触发浏览器下载）
- "把摘要发我" → 调 `fetch_file_content` 取文本，再让模型总结

## 不支持
- 不支持搜历史版本/已删除文件（默认过滤）
- 加密文件（如带密码 PDF）的内容索引可能为空，只能文件名匹配
- 不能跨用户搜别人不共享给你的文件

## 相关
- 取文件文字内容：`fetch_file_content`（暂未单独 chunk）
- 跨类型语义搜索：[[ai-assistant.intelligent-search.howto]]
