---
id: ai-assistant.project-init.howto
title: 让 AI 生成项目结构
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 建项目
  - AI 生成项目模板
  - 项目结构 AI
  - AI 帮我搭项目
  - 新项目让 AI 写
  - 创建项目时的 AI 按钮
related_tools: [create_project, create_task]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已开启至少一个支持 tool call 的模型
negative:
  - 仅生成项目骨架（列 + 初始任务），不生成成员/权限设置
  - 一次最多生成 5 个列 + 每列 6 条任务，超出请二次追加
  - 不会自动创建已存在同名项目（会提示重命名）
last_verified: v1.7.90
---

# 让 AI 生成项目结构

## 这是什么
在「创建项目」弹窗里，项目名输入框右侧有 AI 按钮（仅 ai 插件已装且模型已配置时显示）。点击后描述项目目标，AI 会调 `create_project` 创建项目，再用 `create_task` 批量写入推荐的列和初始任务。

## 入口
- 桌面端：右上角全局「+」→「创建项目」→ 项目名输入框右侧 AI 图标
- 桌面端：左侧栏「项目」→「+」→「创建项目」→ 同上
- 移动端：「+」→「创建项目」→ AI 按钮

## 操作步骤
1. 打开创建项目弹窗，点项目名旁 AI 按钮
2. 输入项目描述（如"3 个月官网改版，含 UI/前端/后端"）
3. AI 生成项目名 + 列结构 + 每列初始任务预览
4. 点「采用并创建」批量建

## AI 输出示例
- **项目名**：官网改版 2026Q1
- **列**：待办 / 设计中 / 开发中 / 测试中 / 已完成
- **任务**：每列 3-6 条按角色分组

## 后续动作
项目创建完成后可继续：

- "把这些任务都分给小王" → 调 `update_task` 批量改负责人
- "再加一个『设计评审』列" → 调列管理工具
- "把项目讨论组拉一下" → AI 暂不能自动建群

## 不支持
- 不能基于其他项目复制（要复制项目请用模板功能）
- AI 不会自动设置任务截止时间，需后续追加
- 创建后想撤销只能手动删项目（无 undo）

## 相关
- 浮窗里建单个任务：[[ai-assistant.create-task.howto]]
- 入口总览：[[ai-assistant.entry.howto]]
