---
id: drawio.concept
title: 流程图（drawio）是什么
type: concept
feature: drawio
scope: end-user
locale: zh
aliases:
  - drawio
  - 流程图
  - 画流程图
  - draw.io
  - DooTask 流程图
  - UML 图
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 drawio 插件
negative:
  - 流程图编辑能力不是主程序内置，未装 drawio 插件无法新建 `drawio` 类型文件
  - 不支持把 drawio 图形直接嵌入到任务详情/讨论消息中（只能作为文件链接发送）
  - 大图（千级节点）渲染会变慢，建议拆图
last_verified: v1.7.90
---

# 流程图（drawio）是什么

## 定义
流程图是 DooTask 文件系统中的一种文件类型（`type=drawio`），用于绘制流程图、UML、ER 图、网络拓扑、思维导图等通用图形。它由独立的 drawio 插件提供编辑器（基于开源 jgraph/drawio），主程序通过 iframe 嵌入 `drawio/webapp/index.html`，文件内容存到 DooTask 自己的文件库里。

## 关键属性
- **文件类型**：`drawio`（与 `mind` 思维导图、`document` 文本、`word/excel/ppt` 平行）
- **存储位置**：DooTask 文件系统（个人文件 / 项目文件 / 共享）
- **编辑器**：嵌入式 drawio 完整编辑器，含图形库、画布、属性面板
- **导出**：支持导出 PNG / PDF（依赖插件内置的 export-server，缺它则导出失效）
- **历史版本**：随 DooTask 文件历史一同保存

## 支持的图形类型
- 流程图、泳道图
- UML 类图、时序图、用例图
- 网络拓扑、机柜图
- 实体关系（ER）图
- 思维导图（更建议用专用的 minder 插件）
- 各类业务建模、原型草图

## 与其他文件类型的关系
- **vs minder 思维导图**：思维导图专注放射结构，节点编辑更轻；drawio 是通用图形，自由度高
- **vs OnlyOffice Word/Excel**：OnlyOffice 支持多人实时；drawio 单人编辑
- **vs DooTask 内置 document**：document 是富文本/Markdown，没有矢量图形能力

## 相关
- 插件元信息：[[drawio.plugin.concept]]
- 创建流程图：[[drawio.create.howto]]
- 入口在哪：[[drawio.entry.menu-map]]
- 内置模板：[[drawio.template.concept]]
