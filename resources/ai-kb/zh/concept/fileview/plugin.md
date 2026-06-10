---
id: fileview.plugin.concept
title: fileview 插件元信息
type: concept
feature: fileview
scope: admin
locale: zh
aliases:
  - fileview 插件
  - kkfileview 插件
  - 安装文件预览
  - 文件预览插件版本
  - 预览插件多大
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 未安装 fileview 插件时，PDF/Office 等文件点击后会报错或下载，无法在线预览
  - 主程序不内置 kkfileview，本插件是唯一来源
  - 不能离线安装到不联网的环境（需访问应用市场镜像源）
last_verified: v1.7.90
---

# fileview 插件元信息

## 定义
文件在线预览由 `fileview` 插件提供（应用市场 app id 为 `fileview`，当前主版本 4.4.0，基于开源 kkfileview 引擎）。主程序在用户点击文件时判断扩展名，若属于 fileview 范畴则 301 跳转到 `fileview/onlinePreview?url=<base64>`，由 fileview 容器渲染。

## 关键属性
- **作者**：社区维护（Community），上游 https://kkview.cn/
- **分类**：微应用（不在管理员应用区，但行为更像「基础设施」——不直接出现在「应用中心」用户菜单，由文件页自动触发）
- **包大小**：约 630MB（含 LibreOffice + 各类转码工具）
- **运行形态**：独立 Docker 容器，对接主程序 nginx 上的 `/fileview/` 反代
- **渲染方式**：服务器侧用 LibreOffice 把 docx/pptx 转 PDF 再推到浏览器；纯 PDF 直接 PDF.js 渲染；图片格式化预览
- **跨设备**：桌面端、移动端均能预览

## 安装与启用
1. 在应用市场（管理员入口）搜索「FileView」或「文件预览」
2. 点击安装，等待镜像下载（约 630MB，按「安装日志」判断进度）
3. 安装完成后插件自动启用，所有未被主程序原生预览覆盖的文件点击后会自动用 fileview 渲染

## 卸载影响
- 卸载后点击 PDF/Office 等文件会失败（路径仍指向 fileview 但容器不存在）
- 重装后自动恢复

## 与 OnlyOffice 共存
- 同时装了 office 和 fileview：Word/Excel/PPT 优先走 OnlyOffice 编辑器（可编辑）
- 只装 fileview：Word/Excel/PPT 只能用 fileview 只读预览
- 只装 office：fileview 才能预览的格式（如 PDF、Visio、CAD 等）无法在线预览

## 相关
- 是什么：[[fileview.concept]]
- 支持的文件类型：[[fileview.supported.concept]]
