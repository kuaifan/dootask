---
id: approve.history.concept
title: 审批流程历史 History
type: concept
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批历史
  - 审批操作记录
  - 流程留痕
  - 谁审过
  - 流程节点历史
  - ProcInstHistory
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
negative:
  - 历史记录不可手动编辑，只由系统在节点流转时写入
  - 删除审批（process/delById）会连带删除该流程的全部历史
  - 历史不会单独导出，导出走 admin 的 approve/export 数据导出
last_verified: v1.7.90
---

# 审批流程历史 History

## 定义
流程历史（`ApproveProcInstHistory`，表 `approve_proc_inst_history`）是每个流程实例（[[approve.process-inst.concept]]）在节点流转时留下的快照，记录谁发起、当前节点、最近意见、整体状态，用于已结束审批的留档查询和驱动「已办」「抄送我（已结束）」两类列表。

## 关键字段
- **proc_def_id / proc_def_name**：所属模板 ID 和名称
- **title**：审批标题
- **start_user_id / start_user_name**：发起人
- **department_id / department / company**：发起部门和公司
- **node_id / candidate / task_id**：当前节点、候选人 userid 串、task ID
- **start_time / end_time / duration**：开始/结束/持续时长
- **state**：0 待审批 / 1 审批中 / 2 通过 / 3 拒绝 / 4 撤回
- **is_finished / var**：是否结束 / 表单数据 JSON
- **latest_comment / global_comment**：最近一次意见 / 全文评论汇总

## 谁会读取
- 「已办」：`approve/procHistory/findTask`
- 「抄送我」已结束：`approve/procHistory/findProcNotify`
- 「已发起」已结束：`approve/procHistory/startByMyself`
- 详情页流程图：合并 `node_infos` 与历史渲染

## 副作用：用户请假/外出状态
静态方法 `getUserApprovalStatus(userid)` 按当前时间是否落在某条已通过的「请假/外出」表单时段内，决定用户在系统其他地方的「请假中」标签。1 分钟缓存。
