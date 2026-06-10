---
id: approve.detail.howto
title: 审批详情页与流程图
type: howto
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批详情
  - 看审批流程
  - 审批走到哪了
  - 流程图
  - 审批节点
  - 审批进度
  - 谁审过了
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
  - 自己是发起人 / 审批人 / 抄送人 之一才能进入对应审批的详情
negative:
  - 详情页里不能修改任何表单字段，全部只读
  - 详情不显示其他人的私聊评论，仅显示全局评论
  - 流程图按节点定义渲染，不支持手动调整节点顺序
last_verified: v1.7.90
---

# 审批详情页与流程图

## 入口
- 任一 Tab（待办/已办/抄送我/已发起）→ 点列表行：宽屏（≥1010 px）右侧分栏；中屏（426-1010）右侧抽屉；窄屏（<426）跳独立路由 `manage-approve-details?id={id}`
- 机器人卡片：在「审批助手」聊天点卡片「查看详情」

## 顶部信息
模板名 + 状态 Tag（青-审批中 / 绿-已通过 / 红-已拒绝/已撤回）、发起人头像和昵称、提交时间。

## 表单字段区
按模板渲染。请假类模板显示：假期类型、开始/结束时间（含周几）、时长（自动算秒/分/小时/天）、事由、图片（≤ 3 张可放大）。

## 审批记录（流程图）
Timeline 时间线渲染 `node_infos`，每节点显示类型、操作人头像与昵称、状态、相对/绝对时间。节点类型：
- **提交** starter：绿色，发起人
- **审批** approver：蓝-审批中 / 绿-已通过 / 红-拒绝或撤回 / 灰-待审批；有意见时显示在引号里
- **抄送** notifier：完成绿、未到灰，显示「自动抄送 张三、李四 共 2 人」
- **结束** end：到达后变绿

被拒绝/撤回后下游节点自动隐藏。

## 全文评论区
有 `global_comments` 时显示，按时间倒序，每条含头像/昵称/内容/图片/相对时间。详见 [[approve.comment.howto]]。

## 操作区
底部按钮见 [[approve.doto.howto]]。接口：详情 `approve/process/detail`；历史 [[approve.history.concept]]。
