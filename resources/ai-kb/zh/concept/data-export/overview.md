---
id: data-export.concept
title: 数据导出概览
type: concept
feature: data-export
scope: admin
locale: zh
aliases:
  - 数据导出是什么
  - 导出 Excel
  - 导出报表
  - 后台能导出哪些数据
  - 管理员导出
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
negative:
  - 导出文件不直接下载到浏览器，而是由系统机器人发到管理员的私聊
  - 不支持普通成员自助导出，全部入口仅管理员可见
  - 不支持导出原始 JSON / 数据库表，只导出预设字段的 Excel
last_verified: v1.7.90
---

# 数据导出概览

## 定义
数据导出是 DooTask 内置的管理员功能，把系统数据按预设字段整理成 Excel 文件，通过系统机器人（`system-msg`）异步发到管理员的私聊。需要系统管理员（`userIsAdmin`）才能触发。

## 支持的导出类型
后台「数据导出」菜单实际提供 4 类导出，分别对应不同的 API：

| 名称 | API | 说明 |
|---|---|---|
| 任务统计 | `api/project/task/export` | 按成员 + 时间段导出任务，含负责人/工时/状态 |
| 超期任务 | `api/project/task/exportoverdue` | 全系统未完成且已超期的任务 |
| 审批数据 | `api/approve/export` | 按流程分类 + 状态 + 时间段导出审批单 |
| 签到数据 | `api/system/checkin/export` | 按成员 + 日期段 + 时间段导出签到记录 |

## 关键属性
- **异步生成**：触发后立即返回，文件由 Swoole 协程后台生成（`go(...)`），完成后才发消息
- **下载链接限时**：消息中的下载链接（`api/.../down`）使用 `Down::cache_decode()` 解码，链接过期文件失效
- **机器人通知**：所有导出完成消息发送方都是 `system-msg` 系统机器人
- **范围限制**：每种导出都有自己的成员数 / 日期范围上限（通常单次 ≤ 35 天、≤ 50 个成员），具体在每个子类型的提示文案里说明

## 不支持
- 不支持导出工作报告、用户列表、项目列表（产品里未开放对应入口）
- 不支持自定义字段选择，导出列固定
- 不支持定时 / 周期性自动导出

## 入口
管理员侧入口和操作步骤见 [[data-export.entry.menu-map]]。
