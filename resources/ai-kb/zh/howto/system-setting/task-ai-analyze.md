---
id: system-setting.task-ai-analyze.howto
title: AI 任务自动分析设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - AI 任务分析怎么开
  - 自动分析任务
  - task_ai_auto_analyze
  - 关闭 AI 自动分析
  - AI 自动建议
  - 新建任务自动分析
prerequisites:
  - 需要系统管理员权限
  - 应用市场已安装 ai 插件
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 未安装 ai 插件时，分析逻辑直接跳过，不会有任何产出
  - 系统开关 = open 但项目级开关 = close 时，该项目的任务也不会被分析
  - 不会回溯分析历史任务，只对新建后进入待分析队列的任务生效
last_verified: v1.7.90
---

# AI 任务自动分析设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「任务相关」→「AI任务分析」。

字段名：`task_ai_auto_analyze`，开关枚举 `open` / `close`，默认 `open`。

## 触发时机
后台 `AiTaskLoopTask` 循环任务执行时：

1. 先检查 `ai` 插件是否安装；未装直接返回
2. 检查系统级开关 `task_ai_auto_analyze`；为 `close` 直接返回
3. 拉取待处理新任务
4. 对每个任务再检查项目级开关 `ai_auto_analyze`；为 `close` 则跳过
5. 投递异步任务 `AiTaskAnalyzeTask` 进行分析

也就是说有 **三道闸**：插件已装 → 系统开关 open → 项目开关 open，三者全满足才会真正调用 AI。

## 产出
分析完成后，AI 会在任务详情面板给出建议（如优先级、负责人、子任务拆分建议等），不会自动改任务字段，只是"建议"形式的辅助信息。

## 字段默认值

| 字段 | 默认 |
|---|---|
| `task_ai_auto_analyze` | `open` |

## 操作步骤
1. 确认应用市场已安装 ai 插件
2. 进入「系统设置」→「系统设置」→「任务相关」
3. 「AI任务分析」选「开启」或「关闭」
4. 「提交」保存，立即生效

## 不支持
- 不能选用哪个模型做分析（用 AI 插件配置的默认模型）
- 不支持对特定项目"豁免"或单独配额
- 系统开关关闭后历史已分析结果保留，但不会刷新
