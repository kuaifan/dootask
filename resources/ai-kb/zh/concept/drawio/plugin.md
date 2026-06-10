---
id: drawio.plugin.concept
title: drawio 插件元信息
type: concept
feature: drawio
scope: admin
locale: zh
aliases:
  - drawio 插件
  - 流程图插件
  - 安装 drawio
  - drawio 插件版本
  - draw.io 集成
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 主程序不内置流程图编辑器，未装插件时新建文件菜单不会显示「图表」
  - 插件体积大约 700MB，下载较慢，需要稳定网络
  - 缺少同包的 export-server 时，PNG/PDF 导出会失败
last_verified: v1.7.90
---

# drawio 插件元信息

## 定义
流程图能力由 `drawio` 插件提供（应用市场 app id 为 `drawio`，当前主版本 30.0.4，基于开源 jgraph/drawio）。插件包内除了 drawio 前端，还包含一个 export-server（镜像 `kuaifan/export-server`）负责把图形导出为 PNG/PDF。主程序仅以 iframe 加载 `drawio/webapp/index.html`，编辑器交互与文件保存全部跑在前端 + 主程序文件 API。

## 关键属性
- **作者**：社区维护（Community），上游 https://www.drawio.com/
- **分类**：微应用（不在管理员应用区）
- **包大小**：约 700MB（含 drawio 资源 + export-server 镜像）
- **运行形态**：静态资源 + 一个导出服务容器
- **关键依赖**：`webapp/index.html` 的 `EXPORT_URL` 指向插件内的 `/drawio/export/`，删 export-server 会导致导出 PNG/PDF 失效
- **数据存储**：图形内容存到主程序文件表（`type=drawio`），不在插件容器单独存

## 安装与启用
1. 在应用市场（管理员入口）搜索「Drawio」或「流程图」
2. 点击安装，等待资源下载（约 700MB，请通过「安装日志」查看进度）
3. 安装完成后插件自动启用，文件新建菜单立即出现「图表」选项

## 卸载影响
- 卸载后菜单消失，原有 `drawio` 文件保留在文件库中但无法继续编辑/预览
- 重新安装后旧文件继续可用

## 已知限制
- 网络不畅时首次加载图形库较慢
- 离线环境需提前推到内网镜像源

## 相关
- 是什么：[[drawio.concept]]
- 入口在哪：[[drawio.entry.menu-map]]
- 内置模板：[[drawio.template.concept]]
