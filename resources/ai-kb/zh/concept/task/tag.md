---
id: task.field.tag.concept
title: 任务标签（项目内独立）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务标签
  - 任务 tag
  - 任务分类
  - 怎么给任务打标签
  - 项目标签管理
related_tools: [update_task]
related_pages: [task_detail, project_settings]
prerequisites: []
negative:
  - 任务标签按项目隔离，不能跨项目共享
  - 单个任务最大标签数 10 个
  - 标签名最长 20 字符
last_verified: v1.7.90
---

# 任务标签（项目内独立）

## 定义
DooTask 任务标签分两类：
- **临时标签**（ProjectTaskTag 表）：在任务详情页直接输入产生，记录 `name + color`，无独立 id，按 project_id 绑定
- **项目级预定义标签**（ProjectTag 表）：在项目设置里统一管理的标签，有 id、可排序、可重命名，所有任务共用同一份

两者都按 project_id 隔离，**不跨项目共享**。

## 在哪里能看到
- 任务卡片底部彩色小圆点 / 标签条
- 任务详情页「标签」字段
- 看板视图任务卡上的标签色块

## 添加方式
- 任务详情页 → 「标签」字段 → 输入名称：
  - 命中项目级标签 → 复用
  - 未命中 → 作为临时标签新建
- 项目设置 → 「标签管理」可统一新增 / 改色 / 排序

## 项目级 vs 临时
| 维度 | 项目级标签 | 临时标签 |
|---|---|---|
| 有独立 id | 是 | 否 |
| 可排序、改色 | 是 | 否 |
| 删除影响 | 解除关联但任务标签不消失（会降级为临时标签） | 仅当前任务 |
| 跨项目复用 | 否 | 否 |

## 不支持
- 标签不跨项目共享（每个项目独立维护）
- 单个任务不能超过 10 个标签
- 标签名不能超过 20 字符
- 没有全局标签，没有用户私人标签
