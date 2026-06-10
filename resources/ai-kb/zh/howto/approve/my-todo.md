---
id: approve.my-todo.howto
title: 待我审批列表
type: howto
feature: approve
scope: end-user
locale: zh
aliases:
  - 待我审批
  - 待办审批
  - 需要我审批的
  - 我要审批什么
  - 找我审批的列表
  - 有多少待办
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
  - 已被某流程模板的审批人节点包含到候选人列表
negative:
  - 待办只显示当前轮到自己处理的，未到节点的不会出现
  - 已处理的不会留在待办，会转到「已办」
  - 列表无法批量同意/拒绝，需要逐条进详情处理
last_verified: v1.7.90
---

# 待我审批列表

## 入口
- 桌面端 / 移动端：审批中心 → Tab「待办」
- Tab 名旁有未读数量徽标（如「待办(5)」），数字来自 `approve/process/doto`，由 WebSocket `approve/unread` 推送实时刷新

## 列表内容
显示当前候选人字段（`candidate`）包含我，且流程未结束的审批：
- 模板名 + 状态 Tag「审批中」
- 发起人头像与昵称
- 提交时间
- 关键字段摘要（如开始/结束时间、事由）

## 筛选条件
列表上方：
- **流程分类**：全部审批 / 各模板名
- **用户名**：按发起人模糊搜
- 点「搜索」或回车触发刷新

## 处理流程
1. 点列表任一项 → 右侧打开详情面板（窄屏抽屉打开）
2. 详情底部出现「同意」「拒绝」「+ 添加评论」按钮（按钮可见性见 [[approve.doto.howto]]）
3. 处理完该条从列表消失，未读数 -1，下一审批人收到推送

## 移动端
- 屏宽 < 426 px 时点列表会通过事件 `approveDetails` 跳到独立详情路由
- 屏宽 < 1010 px 时详情以右侧抽屉打开，不分屏

## 接口
- 列表：`approve/process/findTask`
- 数量：`approve/process/doto`
