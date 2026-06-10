---
id: approve.cc.howto
title: 抄送审批（抄送我 / 抄送他人）
type: howto
feature: approve
scope: end-user
locale: zh
aliases:
  - 抄送我的审批
  - cc 给我的审批
  - 看别人发的审批
  - 怎么抄送给别人
  - 抄送在哪
  - 知会
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
negative:
  - 普通用户不能在发起时手动指定抄送人，由所选模板的 notifier 节点决定
  - 抄送人不能审批（同意/拒绝按钮不可见），只能查看和评论
  - 抄送只在流程「到达 notifier 节点」或「整条流程已通过」时实际发送，被拒绝的流程不抄送
last_verified: v1.7.90
---

# 抄送审批（抄送我 / 抄送他人）

## 抄送我（接收方）
**入口**：审批中心 → Tab「抄送我」

**列表内容**：流程模板里 notifier 节点候选人包含我的审批，无论审批中还是已结束。卡片含模板名、状态 Tag、发起人头像与昵称、提交时间、关键字段摘要。

**筛选**：流程分类（全部审批 / 各模板名）、用户名（按发起人模糊搜）、点「搜索」触发。

**详情**：点行进入右侧详情；可见所有节点和审批意见，但底部「同意/拒绝」按钮不显示（不是我的任务）。可点「+ 添加评论」补充意见（[[approve.comment.howto]]）。

**接口**：审批中 `approve/process/findProcNotify`；已结束 `approve/procHistory/findProcNotify`。

## 抄送他人（怎么让别人收到抄送）
普通用户**无法**在发起表单里指定抄送人。抄送由所选模板的 `notifier` 节点固化，管理员在「流程设置」配模板时挂同事/部门/角色。普通用户只能选用**已包含 notifier 节点**的模板，抄送人会在两个时机收到机器人卡片：
- **启动即抄送**：notifier 放在 starter 之后
- **通过才抄送**：notifier 放在末节点；被拒绝则不抄送

## 不支持
- 普通用户在发起时无「添加抄送人」字段
- 抄送卡片仅知会，不会主动催办
