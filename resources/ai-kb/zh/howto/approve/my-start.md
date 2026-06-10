---
id: approve.my-start.howto
title: 查看我发起的审批
type: howto
feature: approve
scope: end-user
locale: zh
aliases:
  - 我发起的审批
  - 我提交的审批
  - 已发起列表
  - 查我的审批
  - 我提的请假到哪了
  - 看我自己的申请
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
negative:
  - 不能在此列表里改字段，提交后表单只读
  - 只能撤回未结束的审批；已通过/拒绝/撤回的只能删除或追加评论
  - 列表只显示自己发起的；要看别人发起的需要去「待办」「已办」或「抄送我」
last_verified: v1.7.90
---

# 查看我发起的审批

## 入口
- 桌面端 / 移动端：审批中心 → Tab「已发起」

## 筛选条件
列表上方四个控件，任改其一即触发刷新：
- **流程分类**：全部审批 / 各模板名（如「请假申请」「报销申请」）
- **状态**：全部 / 审批中 / 已通过 / 已拒绝 / 已撤回
- **用户名**：按发起人模糊搜索（自己发的也可以搜，多用于管理员视角，普通用户一般留空）
- 「搜索」按钮触发查询

## 列表项显示
每行卡片显示：模板名、状态 Tag（颜色对应：青-审批中、绿-通过、红-拒绝/撤回）、提交时间、发起人头像与昵称、事由摘要。点击进入右侧详情。

## 详情与操作
- 详情结构与处理面板见 [[approve.detail.howto]]
- 状态为「审批中」且自己是发起人：右下角有「撤销」按钮（前提是后续审批人均未行动）
- 状态为「已通过/已拒绝/已撤回」：可见「删除」按钮（仅发起人或管理员）
- 任何状态均可点「+ 添加评论」追加 [[approve.comment.howto]]

## 接口
- 入口列表：`approve/process/startByMyselfAll`（按筛选）
- 旧版仅审批中：`approve/process/startByMyself`
