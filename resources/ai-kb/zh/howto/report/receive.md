---
id: report.receive.howto
title: 查看我收到的报告
type: howto
feature: report
scope: end-user
locale: zh
aliases:
  - 我收到的报告
  - 别人发给我的汇报
  - 查收报告
  - 下属周报在哪
  - 收到的日报
related_tools: [list_received_reports]
related_pages: []
prerequisites: []
negative:
  - 列表只显示别人发给我的报告，自己写的在「我发送的」[[report.my.howto]]
  - 没有「批量删除」操作；接收人不能删除别人写的报告
  - 部门筛选按提交人当前部门匹配，不带历史部门
last_verified: v1.7.90
---

# 查看我收到的报告

## 入口
- 桌面端：右上角头像 →「工作报告」→ 顶部 Tab「我收到的」
- 应用中心：「工作报告」→ 同上
- 移动端：「我的」→「工作报告」→「我收到的」

## 列表显示字段
- 标题（点击进入详情，详情打开会自动标已读）
- 提交人（头像 + 昵称）
- 类型（周报 / 日报）
- 接收时间（`receive_at`）
- 已读 / 未读状态徽标

## 筛选条件
顶部组合筛选：

| 筛选 | 字段 | 说明 |
|---|---|---|
| 关键词 | `keys.key` | 标题 / 提交人邮箱 / 提交人 userid |
| 部门 | `keys.department_id` | 按提交人所在部门 |
| 类型 | `keys.type` | weekly / daily |
| 状态 | `keys.status` | unread / read |
| 时间区间 | `keys.created_at` | 提交时间区间 |

## 未读提示
- 头像菜单「工作报告」右上角红点显示未读数
- 数值来自 `api/report/unread`（接收人侧 read=0 的总数）

## 自动标已读
打开详情页时，如果当前用户是接收人且记录是未读，会自动置已读。手动标记见 [[report.read-unread.howto]]。

## 不支持
- 不支持接收人删除别人写的报告
- 不支持转发并改写后再发
- 不支持跨企业接收他人报告
