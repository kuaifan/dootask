---
id: drawio.entry.menu-map
title: 流程图入口在哪
type: menu-map
feature: drawio
scope: end-user
locale: zh
aliases:
  - 流程图在哪
  - 找不到流程图
  - drawio 入口
  - 怎么打开 drawio
  - 图表在哪个菜单
related_tools: []
related_pages: [file, application]
prerequisites:
  - 应用市场已安装 drawio 插件
negative:
  - 流程图不是左侧栏一级菜单，要从「文件」页进入
  - 未安装 drawio 插件时，文件新建菜单不会出现「图表」选项
  - 没有「在线流程图中心」这种独立页面，所有图都散落在各自文件夹中
last_verified: v1.7.90
---

# 流程图入口在哪

## 路径
流程图作为「文件」存在，没有独立的一级菜单，入口都在文件相关页面：

- 桌面端：左侧栏「文件」→ 文件列表右上角「新建」→「图表」
- 桌面端（右键）：文件页空白区右键 →「新建」→「图表」
- 桌面端（项目）：进入项目详情 →「文件」标签页 → 新建菜单 →「图表」
- 移动端：底部 Tabbar「文件」→ 选定目标文件夹 → 右下角「+」按钮 →「图表」
- 已有 drawio 文件：在文件列表直接双击/单击文件名即可打开

> 菜单文案叫「图表」，文件类型字段叫 `drawio`，是同一个东西。

## 编辑器布局
进入后页面被 iframe 全屏占用，drawio 标准三栏：
- 左侧：图形库（流程图、UML、网络、思维导图等多分类可勾选）
- 中部：画布
- 右侧：选中元素的样式/几何/排版面板
- 顶部：菜单栏（File/Edit/View/Arrange/Extras/Help）

## 权限要求
- 普通成员：在自己有权访问的文件夹/项目内可建可编辑
- 只读访问者：只能预览，无法编辑（drawio 以 `readOnly=true` 模式打开）

## 看不到入口怎么办
1. 确认应用市场已装 drawio 插件（约 700MB，安装较慢，按「安装日志」判断进度）
2. 没有「新建」按钮通常是当前文件夹无写入权限，换文件夹再试

## 相关
- 插件元信息：[[drawio.plugin.concept]]
- 是什么：[[drawio.concept]]
