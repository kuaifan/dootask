---
id: system-setting.auto-archive.howto
title: 任务自动归档设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 任务自动归档怎么开
  - 完成后自动归档
  - 归档天数怎么改
  - auto_archived
  - archived_day
  - 归档了能恢复吗
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 归档天数限制 1-100 天，超出范围保存时报错
  - 子任务不会被独立归档，只看顶层任务（parent_id = 0）
  - 项目自身若设了"自定义归档"（archive_method=custom），系统级开关对该项目不生效
last_verified: v1.7.90
---

# 任务自动归档设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「任务相关」→「自动归档」。

涉及字段：
- `auto_archived` — 开关：`open` / `close`，默认 `close`
- `archived_day` — 任务完成多少天后归档，默认 7，允许 1-100

## 触发条件
后台 `AutoArchivedTask` 定时跑，每次抓最多 100 条满足条件的任务进行归档。条件如下：

1. `auto_archived = 'open'`（系统开关打开）
2. 任务有 `complete_at`（已完成）
3. 完成时间距今 ≥ `archived_day` 天
4. 任务尚未归档（`archived_at` 为空、`archived_userid = 0`）
5. 任务是顶层任务（`parent_id = 0`，子任务不单独归档）
6. 所属项目的 `archive_method` 不是 `custom`（自定义归档的项目走自己的规则）

## 字段默认值

| 字段 | 默认 | 范围 |
|---|---|---|
| `auto_archived` | `close` | open / close |
| `archived_day` | 7 | 1-100，超出报错"自动归档时间不可大于100天" |

## 能不能恢复
能。归档不是删除：归档任务在项目「已归档」列表里仍可查看、撤销归档（恢复到原列）、或彻底删除。归档动作只是设置 `archived_at` 时间戳与 `archived_userid`。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「任务相关」
2. 「自动归档」选「开启」
3. 在出现的输入框填天数（默认 7，1-100 之间）
4. 「提交」保存，下次定时任务执行时按新规则归档

## 不支持
- 不支持按项目 / 任务类型差异化归档天数（除非项目自己开 custom）
- 不支持"归档后 N 天自动删除"二级规则
- 关闭开关不会自动撤销已归档任务
