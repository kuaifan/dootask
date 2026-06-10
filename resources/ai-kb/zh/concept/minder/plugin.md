---
id: minder.plugin.concept
title: minder 插件元信息
type: concept
feature: minder
scope: admin
locale: zh
aliases:
  - minder 插件
  - 思维导图插件
  - 思维导图怎么安装
  - 安装 minder
  - minder 插件版本
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 主程序不内置思维导图编辑器，未装插件时新建文件菜单不会显示「思维导图」
  - 插件升级不通过 git pull，需要在应用市场更新
  - 不能离线安装到不联网的环境（需访问应用市场镜像源）
last_verified: v1.7.90
---

# minder 插件元信息

## 定义
思维导图能力由独立插件 `minder` 提供（应用市场 app id 为 `minder`，当前主版本 0.1.3）。主程序通过 iframe 内嵌插件提供的编辑器页面，文件内容仍保存在 DooTask 主库；卸载插件后已有 `mind` 文件无法继续编辑/预览，但文件本身不会被删除。

## 关键属性
- **作者**：DooTask 官方
- **分类**：微应用（在「应用中心」中列出，作为微应用菜单项；不在管理员应用区）
- **包大小**：约 20MB（安装较慢，请通过应用市场的「安装日志」查看进度）
- **运行形态**：随主程序部署的静态资源 + iframe 嵌入，无独立后端进程
- **数据存储**：思维导图内容存到 DooTask 主程序文件表（`type=mind`），不在插件容器单独存
- **触发场景**：用户在文件页「新建 → 思维导图」时由主程序加载插件 iframe

## 安装与启用
1. 在应用市场（管理员入口）搜索「Minder」或「思维导图」
2. 点击安装，等待资源加载（约 20MB）
3. 安装完成后插件自动启用，文件新建菜单立即出现「思维导图」选项

## 卸载影响
- 卸载后菜单消失，原有 `mind` 文件保留在文件库中但无法打开
- 重新安装后旧文件继续可用

## 相关
- 是什么：[[minder.concept]]
- 入口在哪：[[minder.entry.menu-map]]
