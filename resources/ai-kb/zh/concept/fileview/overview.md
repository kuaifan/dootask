---
id: fileview.concept
title: 文件预览（fileview）是什么
type: concept
feature: fileview
scope: end-user
locale: zh
aliases:
  - fileview
  - 文件预览
  - 在线预览
  - 预览 PDF
  - 预览 Word
  - kkfileview
  - 在线看文件
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 fileview 插件
negative:
  - fileview 只提供「只读预览」，不能在线编辑（编辑要装 OnlyOffice，见 [[office.concept]]）
  - 主程序本身能直接预览图片（jpg/png/gif 等）和文本/代码，这些不需要 fileview
  - 视频/音频在线播放走主程序自带能力，也不属于 fileview 范畴
last_verified: v1.7.90
---

# 文件预览（fileview）是什么

## 定义
fileview 是 DooTask 的**通用文件在线预览**能力，基于开源 kkfileview 引擎，由独立插件提供。所有不能被主程序直接渲染的文件类型（PDF、Word 系、Excel 系、PPT 系、Visio、CAD 等）在点击文件名时会跳转到 fileview 的预览页（路径 `fileview/onlinePreview?url=<base64 文件地址>`），返回浏览器内可滚动、可缩放、可翻页的页面。

## 关键属性
- **只读**：仅查看，不可编辑（编辑能力需 OnlyOffice 插件）
- **入口**：点击文件即可，主程序对超出本地预览范围的文件自动 301 到 fileview 路径
- **格式覆盖**：远超主程序自带能力，包括 Office 全家桶、PDF、各类压缩包、3D 模型、CAD 等（详见 [[fileview.supported.concept]]）
- **加载机制**：fileview 后端拉取文件 → 转码或解析 → 推送渲染结果到浏览器
- **依赖**：依赖独立 fileview 容器持续运行

## 与其他预览能力的关系
- **图片**（jpg/jpeg/png/gif/bmp 等）：主程序内置直接渲染，不走 fileview
- **代码/文本**（2MB 内、扩展名属于 `codeExt` 列表）：主程序内嵌 AceEditor 渲染，不走 fileview
- **Word/Excel/PPT**：装了 OnlyOffice 时走 OnlyOffice 编辑器（可编辑）；没装但装了 fileview 则走 fileview 只读预览
- **思维导图（mind）/ 流程图（drawio）**：DooTask 自己的图形文件，由 minder/drawio 插件渲染，不走 fileview

## 相关
- 插件元信息：[[fileview.plugin.concept]]
- 支持的文件类型：[[fileview.supported.concept]]
- 在线编辑 Office 文档：[[office.concept]]
