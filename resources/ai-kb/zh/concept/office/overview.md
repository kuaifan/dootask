---
id: office.concept
title: 在线文档（OnlyOffice）是什么
type: concept
feature: office
scope: end-user
locale: zh
aliases:
  - 在线文档
  - 在线 Word
  - 在线 Excel
  - 在线 PPT
  - OnlyOffice
  - office 编辑
  - 怎么在线编辑
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 office（OnlyOffice）插件
negative:
  - 在线编辑能力不是主程序内置，未装 OnlyOffice 插件时打开 Word/Excel/PPT 只能调 fileview 预览（详见 [[fileview.concept]]），不能编辑
  - 单文档大小过大（数十 MB 起）打开很慢，建议拆分
  - 不支持把 docx 直接转成 DooTask 自带的 `document` 文件类型
last_verified: v1.7.90
---

# 在线文档（OnlyOffice）是什么

## 定义
DooTask 通过 OnlyOffice 插件提供 Word/Excel/PowerPoint 文件的**在线编辑与多人实时协作**。文件存在 DooTask 文件系统（文件类型分别为 `word`/`excel`/`ppt`），编辑器以 iframe 形式嵌入 OnlyOffice Document Server 提供的页面，多人同时打开同一文件时能看到他人光标和实时改动。

## 关键属性
- **文件类型**：`word`（doc/docx/dot/dotx/odt/ott/rtf）、`excel`（xls/xlsx/xlsm/xlt/xltx/ods/ots/csv/tsv）、`ppt`（ppt/pptx/pps/ppsx/pot/potx/odp/otp）
- **存储位置**：DooTask 文件系统（个人文件 / 项目文件 / 共享）
- **编辑能力**：直接在浏览器编辑文档，与桌面端 Office 体验接近
- **实时协作**：多人同时打开会自动建立协作会话，详见 [[office.collaboration.concept]]
- **历史版本**：随 DooTask 文件历史一同保存

## 与其他文件类型的关系
- **vs document（Markdown/富文本）**：DooTask 自带的 `document` 文件是轻量富文本，无法编辑真正的 docx 二进制
- **vs fileview 预览**：fileview 提供只读的多格式文件预览（包括 office 文件），不能编辑
- **vs minder / drawio**：那些是图形文件，无法用 OnlyOffice 打开

## 相关
- 插件元信息：[[office.plugin.concept]]
- 创建在线文档：[[office.create.howto]]
- 多人协作能力：[[office.collaboration.concept]]
- 入口在哪：[[office.entry.menu-map]]
