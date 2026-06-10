---
id: office.plugin.concept
title: OnlyOffice 插件元信息
type: concept
feature: office
scope: admin
locale: zh
aliases:
  - OnlyOffice 插件
  - office 插件
  - 安装 OnlyOffice
  - 安装在线文档
  - onlyoffice 版本
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 主程序不内置 docx/xlsx/pptx 在线编辑，未装插件时双击 Word/Excel/PPT 只能调 fileview 预览
  - 插件体积约 1.3GB，下载较慢，需要稳定网络与充足磁盘
  - 不能离线安装到不联网的环境（需访问应用市场镜像源）
last_verified: v1.7.90
---

# OnlyOffice 插件元信息

## 定义
在线 Word/Excel/PPT 编辑由 `office` 插件提供（应用市场 app id 为 `office`，包名 OnlyOffice，当前主版本 9.4.0）。插件包含 OnlyOffice Document Server 完整容器；主程序通过 iframe 嵌入 Document Server 的编辑器页面，借助 OnlyOffice 内置 WebSocket 实现多人实时协作。

## 关键属性
- **作者**：社区维护（Community），上游 https://www.onlyoffice.com/
- **分类**：微应用（不在管理员应用区）
- **包大小**：约 1.3GB（含 Document Server，下载慢，按「安装日志」判断进度）
- **运行形态**：独立 Docker 容器（OnlyOffice Document Server）
- **数据存储**：编辑结果由 OnlyOffice 回写到 DooTask 文件表，文件本体存到主程序的文件系统，不在 Document Server 长期持久化
- **协议**：内部通过 JWT 令牌与 OnlyOffice 通信，主程序签发 documentKey 标识文件版本

## 安装与启用
1. 在应用市场（管理员入口）搜索「OnlyOffice」或「在线文档」
2. 点击安装，等待镜像下载（约 1.3GB，请通过「安装日志」查看进度）
3. 安装完成后插件自动启用，文件新建菜单立即出现「Word / Excel / PPT」选项
4. 上传/打开已有 docx/xlsx/pptx 文件即可在线编辑

## 卸载影响
- 卸载后文件库中的 Word/Excel/PPT 文件保留，但无法在线编辑，回退到 fileview 预览（若未装 fileview 则无法预览）
- 重装后所有文件自动恢复在线编辑能力

## 已知限制
- 部署在公网时建议为 Document Server 配置 HTTPS，否则浏览器可能拦截 WebSocket
- 同一文件大量并发协作（数十人）需要更强的 Document Server 资源

## 相关
- 是什么：[[office.concept]]
- 多人协作机制：[[office.collaboration.concept]]
- 入口在哪：[[office.entry.menu-map]]
