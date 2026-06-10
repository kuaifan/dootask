---
id: minder.concept
title: 思维导图（minder）是什么
type: concept
feature: minder
scope: end-user
locale: zh
aliases:
  - 思维导图
  - 脑图
  - mind map
  - minder 是什么
  - DooTask 思维导图
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 minder 插件
negative:
  - 思维导图功能不是主程序内置，未安装 minder 插件无法新建 `mind` 类型文件
  - 不能在思维导图节点上直接附加 DooTask 任务/评论（节点只是图形元素）
  - 节点数量过多（数千节点）可能影响渲染性能
last_verified: v1.7.90
---

# 思维导图（minder）是什么

## 定义
思维导图是 DooTask 文件系统中的一种文件类型（`type=mind`），用于以放射状结构表达想法、计划、知识结构。它由独立的 minder 插件提供编辑器，主程序通过 iframe 嵌入插件页面，文件内容存到 DooTask 自己的文件库里。

## 关键属性
- **文件类型**：`mind`（区别于 `document` 文本、`drawio` 图表、`word/excel/ppt`）
- **存储位置**：与其他文件一样存在 DooTask 文件系统（个人文件 / 项目文件 / 共享）
- **编辑器**：嵌入的 KityMinder 风格编辑器，支持多种结构（默认、组织结构、文件树、右展开、鱼骨图、天盘）和多套主题
- **操作支持**：拖拽、缩放、节点折叠/展开、键盘快捷键、导出图片
- **历史版本**：随 DooTask 文件历史一同保存

## 与其他文件类型的关系
- **vs drawio**：drawio 适合流程图/UML/网络拓扑等任意图形；思维导图专注放射状层级结构，节点编辑更轻
- **vs 文档（document）**：文档是富文本/Markdown；思维导图是图形化结构

## 相关
- 插件元信息：[[minder.plugin.concept]]
- 创建一张思维导图：[[minder.create.howto]]
- 入口在哪：[[minder.entry.menu-map]]
- 是否支持协作编辑：[[minder.collaboration.concept]]
