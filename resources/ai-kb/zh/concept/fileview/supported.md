---
id: fileview.supported.concept
title: fileview 支持的文件类型
type: concept
feature: fileview
scope: end-user
locale: zh
aliases:
  - fileview 支持什么格式
  - 哪些文件能在线预览
  - DooTask 预览的格式
  - 能不能预览 PDF
  - 能不能预览 CAD
  - 预览支持的扩展名
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 fileview 插件
negative:
  - 加密/密码保护的 Office 文档预览失败（fileview 解不开）
  - 超大文件（>100MB）预览很慢甚至超时
  - 不能在预览页里编辑/批注
last_verified: v1.7.90
---

# fileview 支持的文件类型

## 定义
fileview 插件基于开源 kkfileview 引擎，可在线预览 30+ 种文件格式。主程序对扩展名做粗分流：图片、代码文本、视频音频走主程序自带能力；其余几乎全部交给 fileview。

## 主要支持的格式分类
- **PDF**：pdf
- **Word 系**：doc, docx, dot, dotx, odt, ott, rtf
- **Excel 系**：xls, xlsx, xlsm, xlt, xltx, ods, ots, csv, tsv
- **PowerPoint 系**：ppt, pptx, pps, ppsx, pot, potx, odp, otp
- **WPS 系**：wps, et, dps（与 Office 同一套转码路径）
- **OpenDocument 全系**：odt/ods/odp/ott/ots/otp 等
- **流程图 / 矢量**：vsd, vsdx（Visio），drawio 文件由 drawio 插件直接打开不走 fileview
- **CAD**：dwg
- **3D**：obj, stl
- **电子书**：epub, mobi
- **压缩包**：zip, rar, 7z, tar, gz（展开目录树预览）
- **邮件/日历**：eml, ics
- **代码（大文件兜底）**：当代码文本 > 2MB 主程序不再内嵌渲染，可由 fileview 兜底

## 主程序不走 fileview 的格式
- 图片：jpg, jpeg, webp, png, gif, bmp（主程序内置渲染）
- 视频/音频：mp3, wav, mp4, flv 等（内置播放器）
- 代码/文本 ≤ 2MB（内嵌 AceEditor）
- 思维导图（mind）→ minder 插件
- 流程图（drawio）→ drawio 插件
- Word/Excel/PPT 若装了 OnlyOffice → 走 OnlyOffice 编辑器（可编辑）

## 不支持
- 加密 / 密码保护的 Office 文档无法预览
- 不保证体积过大（数百 MB 起）的文件能正常预览（可能超时或加载缓慢）
- 不能在预览页里编辑（fileview 只读）

## 相关
- 插件元信息：[[fileview.plugin.concept]]
- 是什么：[[fileview.concept]]
