---
id: approve.concept
title: 审批中心是什么
type: concept
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批是什么
  - 审批流程是什么
  - approve 插件
  - 什么是流程审批
  - 工作流
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
negative:
  - 审批是独立插件，未安装时整个功能不可用
  - 不支持条件分支（按表单值路由到不同审批人）
  - 不支持嵌套子审批
  - 表单不支持公式计算字段
last_verified: v1.7.90
---

# 审批中心是什么

## 定义
审批中心（approve）是 DooTask 的内置流程审批应用，用于在企业内提交、流转、处理表单类申请。典型场景：请假、出差、报销、用印、采购、合同会签。它是独立插件（独立 docker 服务 + 独立数据库），主程序通过 `ApproveController` 代理调用并把消息回写到聊天会话。

## 三个核心对象
- **流程模板 ProcDef**：管理员预先定义的审批样板，含表单字段、审批人节点、抄送人节点。普通用户只能选用，不能修改
- **流程实例 ProcInst**：一次具体的审批运行；用户每提交一次就生成一条，详见 [[approve.process-inst.concept]]
- **节点 Node**：模板里的步骤；常见类型 `starter`（发起）、`approver`（审批人）、`notifier`（抄送）、`end`（结束）

## 三类角色
- **发起人**：提交审批的人，能撤回未结束的审批、能给已结束的审批补评论
- **审批人**：在「待办」看到任务，可同意或拒绝
- **抄送人**：在「抄送我」看到知会，无须操作

## 状态机
流程实例的 `state`：1 审批中、2 已通过、3 已拒绝、4 已撤回。详情页右上角用 Tag 显示，列表也按这个色区分。

## 与项目/任务/聊天的关系
- 审批不属于任何项目，是独立工作流
- 所有通知通过「审批助手」机器人发到 1 对 1 聊天（[[approve.notify.concept]]）
