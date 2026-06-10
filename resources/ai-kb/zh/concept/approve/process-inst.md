---
id: approve.process-inst.concept
title: 流程实例 ProcInst 是什么
type: concept
feature: approve
scope: end-user
locale: zh
aliases:
  - 流程实例
  - 审批单
  - ProcInst
  - 审批运行
  - 一次审批
  - 流程 ID
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
negative:
  - 流程实例不可改字段，提交后表单只读
  - 一旦后续节点已被任何审批人处理，发起人不能再撤销
  - 已结束的实例只有发起人或管理员能删（[[approve.doto.howto]]）
last_verified: v1.7.90
---

# 流程实例 ProcInst 是什么

## 定义
流程实例（ProcInst）是「一次具体的审批运行」的对象，由发起审批时创建。模板（ProcDef）是图纸，实例是按图纸跑出来的一条具体审批单。每提交一次就生成一个新实例，有唯一数字 ID。后端表 `approve_proc_inst`，主程序通过 `approve/process/findById` 取数据。

## 关键属性
- **id**：实例唯一 ID
- **proc_def_name**：模板名（如「请假申请」）
- **start_user_id / start_user_name**：发起人 ID 和昵称
- **start_time**：提交时间
- **state**：1 审批中 / 2 已通过 / 3 已拒绝 / 4 已撤回
- **is_finished**：是否结束（state ≠ 1 即结束）
- **task_id**：当前 task ID（处理动作要带）
- **candidate**：当前候选审批人 userid 逗号串
- **node_id / node_infos**：当前节点 ID + 所有节点状态数组，用于渲染流程图
- **var**：表单字段对象（type / start_time / end_time / description / other 等）
- **global_comments**：全文评论数组（[[approve.comment.howto]]）

## 生命周期
1. **创建**：`approve/process/start`，state=1
2. **流转**：每次 `approve/task/complete` 推进节点
3. **结束**：state 变 2/3/4
4. **清理**：仅结束态可调 `approve/process/delById` 物理删除

## 与历史记录的关系
每次节点流转写一条 [[approve.history.concept]]（`approve_proc_inst_history`），实例被删后历史也消失。
