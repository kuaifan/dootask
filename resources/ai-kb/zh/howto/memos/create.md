---
id: memos.create.howto
title: 写一条 Memo
type: howto
feature: memos
scope: end-user
locale: zh
aliases:
  - 怎么写笔记
  - 新建 memo
  - 记一条想法
  - 加一条笔记
  - 我要写个备忘
  - memos 怎么用
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 memos 插件
negative:
  - 不能用 Memos 原生账号登录（账号由 DooTask 自动建立）
  - 单条 memo 大小受 Memos 服务自身限制
  - Memos 不与 DooTask 任务 / 项目数据互通，写在这里的内容不会变成任务
last_verified: v1.7.90
---

# 写一条 Memo

## 入口
- 桌面端：左侧栏「应用」→「Memos 笔记」（对应 URL `apps/memos/`）
- 移动端：底部 Tabbar「应用」→「Memos 笔记」
- 首次打开会自动用当前 DooTask 账号登录 Memos，无需输入密码

## 操作步骤
1. 打开「Memos 笔记」页面
2. 在顶部输入框直接输入内容（支持 Markdown）
3. 可选：用 `#标签名` 语法添加标签分类（参见 [[memos.tag.concept]]）
4. 可选：调整可见性（私有 / 工作区 / 公开），默认是私有
5. 点击「保存 / Save」提交，新 memo 立即出现在时间线顶部

## 编辑与删除
- 在时间线上找到要操作的 memo，点击右上角的「···」菜单
- 可执行编辑内容、修改可见性、置顶、归档、删除等操作

## Markdown 与附件
- 支持标题、加粗、列表、代码块、链接等基础 Markdown
- 支持上传图片 / 文件附件，存储在 SQLite 数据库或本地卷里
- 不支持任意大附件，建议大文件用 DooTask 主程序的文件模块

## 不支持
- 不能跨 DooTask 用户共用同一个 memo 帐号（每个 DooTask 用户在 Memos 内是独立账号）
- 不能从 DooTask 主程序的任务 / 文档直接「转存到 Memos」

## 相关
- Memos 是什么：[[memos.concept]]
- 入口在哪：[[memos.entry.menu-map]]
- 标签 / 分类：[[memos.tag.concept]]
