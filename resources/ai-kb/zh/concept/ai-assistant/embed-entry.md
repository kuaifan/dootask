---
id: ai-assistant.embed-entry.concept
title: 业务嵌入式 AI 入口
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 嵌入业务
  - 任务里 AI
  - 项目 AI 生成
  - 工作报告 AI
  - 消息里 AI
  - AI 业务入口
related_tools: []
related_pages: [task_detail, project_create, report_edit, dialog]
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已配置至少一个 AI 模型
negative:
  - 嵌入式入口与浮窗对话不共用同一会话池（按 sessionKey 隔离）
  - 嵌入入口大多有自定义 system 提示词，输出格式可能强约束（如 JSON）
  - 不是所有页面都有 AI 按钮，只有这里列出的业务场景才内嵌
last_verified: v1.7.90
---

# 业务嵌入式 AI 入口

## 定义
除浮按钮和快捷键外，DooTask 在多个业务表单内嵌入了「上下文相关」的 AI 入口。它们与全局浮窗调的是同一个 AI 助手组件，但通过 `sessionKey` + `onBeforeSend` + `onApply` 等参数定制行为，让 AI 直接产出可应用到表单的结构化内容。

## 已知嵌入入口

| 入口 | 触发位置 | sessionKey | 典型用途 |
|---|---|---|---|
| 项目创建 | 新建项目弹窗 AI 按钮 | `project-create` | 生成项目名 + 看板列 |
| 任务创建 | 添加任务弹窗 AI 按钮 | `task-add` | 生成任务标题 / 描述 / 子任务 |
| 工作汇报 | 汇报编辑器 AI 工具 | `report-edit` | 基于近期任务生成草稿 |
| 消息输入 | 群 / 私聊输入栏 AI 图标 | `chat-message` | 帮你写消息草稿 |
| 任务讨论 | 任务详情 @AI | — | 机器人方式触发 |

## 与浮窗的区别
- **会话池隔离**：不同 `sessionKey` 走不同桶；项目创建会话不会和任务创建混在一起
- **上下文重建**：嵌入入口通过 `onBeforeSend` 重写 context（不走默认助手 system 提示）
- **应用按钮**：回答下方有「应用此内容」按钮，自动回填原表单
- **加载提示**：可自定义「正在生成项目结构…」之类提示

## 应用此内容
回答完成后弹出 `Button`：
1. 点击触发 `onApply` 回调
2. 解析 AI 输出（通常 JSON）并填入原表单
3. 应用成功后嵌入弹窗自动关闭

## 不支持
- 嵌入会话不出现在浮窗历史下拉里
- 不能在嵌入入口切回浮窗系统提示词
- 没有 AI 按钮的页面（日历 / 文件 / 个人设置）不能内嵌唤起

## 相关
- [[ai-assistant.entry.howto]]
- [[ai-assistant.session.concept]]
