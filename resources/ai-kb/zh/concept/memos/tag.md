---
id: memos.tag.concept
title: Memos 标签与分类
type: concept
feature: memos
scope: end-user
locale: zh
aliases:
  - Memos 标签
  - memos 怎么分类
  - 笔记标签
  - 笔记分类
  - 标签筛选
  - "#标签"
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 memos 插件
negative:
  - 标签是每个用户私有的（不与其他 DooTask 用户共用）
  - 标签不与 DooTask 主程序的任务标签互通
last_verified: v1.7.90
---

# Memos 标签与分类

## 定义
Memos 没有独立的「分类」字段，靠 `#标签` 语法在 memo 正文中内联标记。任何写在 `#xxx` 形式的词都会被识别为标签，自动聚合到侧边栏的标签列表，便于后续筛选检索。

## 关键属性
- **写法**：在内容里直接写 `#项目A` `#读书笔记`，可一条 memo 多个标签
- **嵌套**：支持斜杠分层，例如 `#工作/会议纪要`，侧栏会折叠展示
- **私有**：每个 Memos 账号的标签彼此隔离（每个 DooTask 用户在 Memos 内是独立账号）
- **聚合方式**：Memos 自动扫描所有 memo 正文，无需手动维护「标签库」

## 使用方式
- **添加**：写 memo 时在内容里直接打 `#标签名`，保存即生效
- **筛选**：点击侧栏中对应标签，时间线只显示带该标签的 memo
- **删除**：把所有 memo 里这个 `#标签` 的字符删掉，标签自动消失（无独立删除按钮）

## 与可见性的关系
- 标签本身只是文本标记，不影响 memo 的可见性（私有 / 工作区 / 公开）
- 想限定某类笔记只自己可见，应改对应 memo 的可见性，而不是靠标签

## 适用场景
- 按项目 / 主题 / 周期分组浏览
- 给读书摘录 / 灵感速记打主题
- 搭配搜索定位历史笔记

## 不支持
- 不能改某个标签的名字（要重命名只能逐条编辑替换原文）
- 不能给标签设颜色或图标
- 不能从 DooTask 主程序任务的标签自动同步过来

## 相关
- Memos 是什么：[[memos.concept]]
- 写一条 memo：[[memos.create.howto]]
