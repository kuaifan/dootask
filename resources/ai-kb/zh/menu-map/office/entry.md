---
id: office.entry.menu-map
title: 在线文档入口在哪
type: menu-map
feature: office
scope: end-user
locale: zh
aliases:
  - 在线文档在哪
  - 在线 Word 入口
  - 在哪建在线表格
  - office 在哪
  - 找不到在线 PPT
related_tools: []
related_pages: [file, application]
prerequisites:
  - 应用市场已安装 office（OnlyOffice）插件
negative:
  - 在线文档不是左侧栏一级菜单，要从「文件」页进入
  - 未安装 office 插件时，双击 Word/Excel/PPT 文件不会进入编辑器，只显示静态预览（依赖 fileview 插件）
  - 没有「在线文档中心」这种独立页面，所有 office 文档散落在各自文件夹中
last_verified: v1.7.90
---

# 在线文档入口在哪

## 路径
在线 Word/Excel/PPT 作为「文件」存在，没有独立的一级菜单，入口都在文件相关页面：

- 桌面端：左侧栏「文件」→ 文件列表右上角「新建」→「Word 文档 / Excel 工作表 / PPT 演示文稿」
- 桌面端（右键）：文件页空白区右键 →「新建」→ 同上三选一
- 桌面端（项目）：进入项目详情 →「文件」标签页 → 新建菜单 → 同上三选一
- 移动端：底部 Tabbar「文件」→ 选定目标文件夹 → 右下角「+」按钮 → 同上三选一
- 已有文档：在文件列表直接双击/单击文件名即可打开编辑

## 上传后自动可编辑
扩展名在以下列表内的上传文件，双击即可在线编辑（无需先转格式）：
- Word 系：doc, docx, dot, dotx, odt, ott, rtf
- Excel 系：xls, xlsx, xlsm, xlt, xltx, ods, ots, csv, tsv
- PPT 系：ppt, pptx, pps, ppsx, pot, potx, odp, otp

## 编辑器布局
进入后页面被 iframe 全屏占用，由 OnlyOffice 提供完整菜单：
- 顶部：标题栏（含协作者头像）+ 菜单栏（文件、首页、插入、布局、引用、协作、视图等）
- 中部：文档编辑区
- 右侧：批注、聊天、协作状态浮层

## 权限要求
- 普通成员：在自己有权访问的文件夹/项目内可建可编辑
- 只读访问者：只能预览，无法编辑

## 看不到入口怎么办
1. 确认应用市场已装 office 插件（约 1.3GB，安装较慢）
2. 没有「新建」按钮通常是当前文件夹无写入权限，换文件夹再试

## 相关
- 插件元信息：[[office.plugin.concept]]
- 是什么：[[office.concept]]
