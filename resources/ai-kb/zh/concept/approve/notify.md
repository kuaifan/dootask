---
id: approve.notify.concept
title: 审批通知与「审批助手」机器人
type: concept
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批机器人
  - 审批通知
  - 审批助手
  - approval-alert
  - 怎么收到审批提醒
  - 审批卡片
related_tools: []
related_pages: [application, messenger]
prerequisites:
  - 应用市场已安装 approve 插件
  - 当前账号未屏蔽「审批助手」机器人会话
negative:
  - 不支持改成第三方 webhook 推送
  - 不支持邮件 / 短信 / APP 推送渠道（只发应用内聊天）
  - 不支持自定义模板文案
  - 机器人不能被「@」也不能直接对话指令
last_verified: v1.7.90
---

# 审批通知与「审批助手」机器人

## 定义
审批的所有通知都通过系统机器人「审批助手」（userid `approval-alert`）的 1 对 1 私聊推送，消息类型 `template`（卡片）。卡片含标题、关键字段、缩略图（请假类带图片时），底部「查看详情」跳进审批详情。

## 何时发卡片
后端 `approveMsg()` 按 `type` 投递：

| type | 触发 | 收件人 | 标题样例 |
|---|---|---|---|
| `approve_reviewer` | 到我作审批人节点 | 待审批人 | {发起人} 提交的「{模板}」待你审批 |
| `approve_notifier` | 到我所在 notifier 节点 | 抄送人 | 抄送 {发起人} 提交的「{模板}」记录 |
| `approve_comment_notifier` | 我参与的审批被加全文评论 | 其他相关方 | {评论人} 评论了 {发起人} 的「{模板}」审批 |
| `approve_submitter` | 我发起的被通过/拒绝 | 发起人 | 您发起的「{模板}」已通过 / 被 {审批人} 拒绝 |

## 卡片更新机制
审批人同意/拒绝、发起人撤销时不发新卡，而用 `msg_id` 反查原卡片调 `change-{msg_id}` 原地更新（绿-通过/红-拒绝/灰-撤回），避免聊天里堆几十条。

## 未读徽标
`approve_reviewer` 推送时附带 WebSocket 事件 `approve/unread`，「审批」入口和 Tab「待办」名后的数字立即 +1，处理后 -1。数量接口 `approve/process/doto`。
