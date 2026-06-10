---
id: task.field.priority.concept
title: 任务优先级
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务优先级
  - 紧急程度
  - 怎么设优先级
  - 高优先级标记
  - 重要任务怎么标
related_tools: [update_task, create_task]
related_pages: [task_detail]
prerequisites: []
negative:
  - 优先级数据是全局共享的，不能按项目独立配置（除非用 [[task.field.color.concept]] 单独覆盖）
  - 普通用户无法新增 / 修改优先级枚举，仅可选择
  - 优先级与排序无关，不会自动让高优先级冒泡到列首
last_verified: v1.7.90
---

# 任务优先级

## 定义
DooTask 任务的优先级（priority）记录任务的紧急 / 重要程度。每个任务存三个字段 `p_level`（数值）、`p_name`（名称）、`p_color`（颜色），三者冗余存储，避免全局配置变更影响历史任务。

## 数据来源
全局 `settings` 表的 `priority` JSON 数组定义可选项，每条含：
- `name`：显示名（如「紧急」「重要」「普通」「低」）
- `priority`：数值（用于 `p_level`）
- `color`：色块（用于 `p_color`）
- `is_default`：是否默认

新建任务（仅父任务）时，若未指定优先级，服务端会自动套用 `is_default=1` 的配置。

## 在哪里能看到
- 任务卡片左侧色条
- 任务详情页右上角徽标
- 列表视图的「优先级」列
- 看板视图卡片顶部色块

## 修改方式
- 任务详情页 → 优先级下拉 → 选另一个枚举
- 服务端写入 `p_level`、`p_name`、`p_color` 三字段一致

## 不支持
- 项目级不能独立定义优先级枚举
- 修改全局优先级配置后，已存在任务不会回填新名 / 新色，需手动重新选
- 子任务优先级不随父任务联动：子任务也支持优先级，但默认与父任务无关联，需单独设置
