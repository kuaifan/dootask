---
id: file.preview.concept
title: 文件预览类型
type: concept
feature: file
scope: end-user
locale: zh
aliases:
  - 文件能不能预览
  - 在线看文件
  - 文档预览
  - 支持哪些文件预览
  - excel 能直接看吗
related_tools: [get_file_detail, fetch_file_content]
related_pages: [file]
prerequisites: []
negative:
  - office 类（doc/xls/ppt 等）预览需要管理员在应用市场安装 office 插件（OnlyOffice）
  - 其他格式（PDF / CAD / OFD 等）预览需要 fileview 插件
  - 未安装对应插件时打开会提示「未安装」，但仍可下载
  - 视频 / 音频不在文件预览里直接播放，会触发下载
last_verified: v1.7.90
---

# 文件预览类型

## 定义
文件预览指在浏览器内不下载即可查看的能力。DooTask 不同文件类型走不同预览管线，依赖不同插件。

## 浏览器直接预览（无需插件）
- **document**：内置在线文档（Markdown 渲染器）
- **txt / code**：纯文本与代码高亮
- **picture**：jpg / png / webp / gif / bmp 直接渲染
- **mind**：思维导图（需 minder 插件，但产品默认随包提供）
- **drawio**：流程图（需 drawio 插件）

## 需安装插件预览
- **word / excel / ppt**：内置类型，但实际渲染由 OnlyOffice 完成，需 office 插件
- **pdf / cad / ofd / tif** 等：由 fileview 插件提供
- **wps**：fileview 插件

## 不支持预览的类型
- **archive**（zip / rar / 7z 等）：只能下载，无在线解压
- **media**（mp3 / mp4 / mov 等）：从文件入口直接下载，预览能力依赖浏览器原生
- **axure（rp）**：仅记录类型，需用 Axure 客户端查看

## 在线编辑 vs 仅预览
- 在线编辑：document、word/excel/ppt（office 插件）、mind、drawio
- 仅预览：pdf、其他 fileview 支持的类型
- 编辑权限受 FileUser 权限位（读写 / 只读）控制
