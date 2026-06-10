---
id: ai-assistant.subtask-suggest.howto
title: 让 AI 帮我拆子任务
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 拆任务
  - AI 建议子任务
  - 拆解任务
  - AI 帮我分解
  - 任务太大怎么拆
  - 子任务建议
related_tools: [create_sub_task]
related_pages: [task_detail]
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 当前用户能修改该任务（负责人/协作者/项目管理员）
negative:
  - AI 建议不会自动落地，需用户在回复里点「全部创建」或逐条确认
  - 一次最多建议 10 条子任务，复杂任务可分多次
  - AI 拆分依赖任务标题 + 描述，描述过短时建议会很泛
last_verified: v1.7.90
---

# 让 AI 帮我拆子任务

## 这是什么
在任务讨论区 @AI 让它根据当前任务的标题、描述自动建议一组子任务清单，确认后通过 `create_sub_task` 工具一次性建到当前任务下。

## 怎么触发
- **任务讨论区**：@AI 说"帮我拆 5 个子任务" / "把这个任务分解成执行步骤"
- **浮窗**：先 `open_task` 或带上任务 ID，然后说"按这个任务拆子任务"

## AI 通常会返回
1. 一组建议条目（如「需求评审 / 设计稿 / 前端开发 / 后端开发 / 联调测试 / 上线」）
2. 每条带预估负责人角色提示
3. 末尾问"是否一键创建"

## 操作步骤
1. 打开任务详情 → 讨论区 @AI
2. 提示词如"按当前任务拆 5 个子任务，包括负责人建议"
3. AI 列出建议
4. 回复"全部创建"，AI 调 `create_sub_task` 逐条建
5. 若想改某条 → 回复"第 2 条改成 X"，AI 重发建议
6. 创建完成后讨论区会出现"已新建 N 条子任务"提示

## 不支持
- 子任务仅支持 1 层（AI 不会再为子任务建孙任务，建会被后端拒绝）
- 拆分不会自动设置截止时间，需后续追加"第 1 条截止 X 日"
- AI 不会自动分配人选，需追加"分给前端组成员"

## 相关
- 子任务概念：[[task.subtask.concept]]
- 任务讨论区 @AI：[[ai-assistant.task-mention.howto]]
- 创建任务：[[ai-assistant.create-task.howto]]
