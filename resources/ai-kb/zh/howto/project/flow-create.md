---
id: project.flow.howto.create
title: 在项目设置中启用 / 创建工作流
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 项目工作流
  - 启用工作流
  - 自定义任务流程
  - 给项目加流程
  - 配置流程节点
related_tools: [update_project]
related_pages: [project_settings, project_flow]
prerequisites:
  - 是项目拥有者或管理员
  - 项目不是个人项目（[[project.personal.concept]] 不支持工作流）
negative:
  - 单个工作流最多 10 个节点
  - 工作流启用后无法回退到"无流程"模式
  - 个人项目（personal=1）不支持工作流
last_verified: v1.7.90
---

# 在项目设置中启用 / 创建工作流

## 是什么
DooTask 工作流（ProjectFlow + ProjectFlowItem）让任务在「待办 / 开发 / 测试 / 完成」等自定义状态间流转。比裸用「列」（[[project.column.howto.add]]）更适合"按工序推进"的场景。任务详情见 [[task.flow.concept]]。

## 入口
- 桌面端：项目设置 → 「工作流」标签 → 「启用工作流」
- 创建项目时也可勾选「启用工作流」直接套默认 5 节点（见 [[project.flow.concept.default]]）

## 创建步骤
1. 点「启用工作流」（如未启用）或「+ 新增节点」
2. 填节点：
   - 名称（≤20 字符）
   - 状态类型：`start` 起点 / `end` 终点 / 普通节点
   - 颜色（看板色条用）
   - 可流转到的节点（turns，多选）
   - 节点负责人（userids，限制只有这些人能让任务进此节点）
   - 流转模式（usertype：add 追加 / replace 替换 / merge 合并负责人）
   - 绑定列（columnid，可选）
3. 排序（拖动调整 sort）
4. 保存

## 启用后的行为
- 项目自动切到「工作流」视图
- 拖动任务到 `status=end` 节点 → 任务 `complete_at` 自动写入（完成）
- 拖到非 end 节点 → 仅改 flow_item_id

## 不支持
- 单流程不能超过 10 个节点（最多 10 节点）
- 工作流不能跨项目共享
- 启用后无法关闭工作流，但可改节点
