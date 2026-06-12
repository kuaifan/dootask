---
id: file.search.howto
title: 搜索文件
type: howto
feature: file
scope: end-user
locale: zh
aliases:
  - 找文件
  - 搜文件
  - 按名字搜
  - 文档搜索
  - 文件内容搜
  - 找之前传的文件
related_tools: [search_files, intelligent_search]
related_pages: [file]
prerequisites: []
negative:
  - 「文件」页内置搜索只匹配文件名 / 文件 ID，不搜文件内容
  - 文件内容搜索需走全局搜索（Manticore 索引），仅文档类与文本类文件被索引
  - 单次返回最多 100 条，默认 50 条
  - 搜索范围 = 自己的文件 + 共享给自己的文件，不跨他人私有空间
last_verified: v1.7.90
---

# 搜索文件

## 入口
- 桌面端 Web：「文件」页顶部搜索框，输入即筛选
- 全局搜索：顶部全局搜索框（放大镜）→ 切换「文件」标签
- AI 助手：调用 search_files 工具按关键词检索

## 文件名搜索（文件页内置）
1. 在「文件」页顶部输入关键词
2. 系统按 `name LIKE %关键词%` 匹配（包含匹配，不分词）
3. 如果关键词是纯数字，会同时尝试按文件 ID 精确匹配
4. 同时搜索：自己的文件 + 他人共享给自己的文件
5. 单次返回最多 100 条

## 通过分享链接反查
搜索框可粘贴分享链接（如 `https://t.../single/file/xxxxxx`），系统会解析 code 反查到对应文件。

## 文件内容搜索（全局搜索）
- 入口：全局搜索框 → 选择「文件」标签
- 索引由 Manticore 提供，仅文档类（document / txt / code / pdf 抽取出的文本）被索引
- office 文件（doc/xls/ppt）已索引后可按内容关键词命中
- 图片本身不参与内容索引；如需理解图中文字，可把图片交给 AI 助手直接识别（多模态）

## 不支持
- 不支持模糊匹配 / 拼音搜索
- 不支持跨用户隐私空间搜索
- 不支持二进制文件（压缩包 / 视频）的内容搜索
- 不支持自定义文件搜索结果排序（默认按权重）
