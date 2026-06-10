---
id: report.read-unread.howto
title: 标记报告已读或未读
type: howto
feature: report
scope: end-user
locale: zh
aliases:
  - 报告已读
  - 标记已读
  - 报告标未读
  - 重新标未读
  - 清掉报告小红点
  - 批量已读
related_tools: [mark_reports_read]
related_pages: []
prerequisites: []
negative:
  - 只有接收人能改自己侧的已读状态，提交人不能代替接收人改
  - 单次批量操作上限 100 条，超过会报「最多只能操作100条数据」
  - 已读状态是按接收人独立维护的，A 标已读不影响 B 的未读
last_verified: v1.7.90
---

# 标记报告已读或未读

## 三种触发方式

### 1. 自动标已读（最常用）
打开「我收到的」→ 点开任一报告详情，系统检测到当前用户是未读接收人时自动置已读。无需手动操作。

### 2. 手动批量标已读
- 「我收到的」列表多选 → 顶部「标记已读」按钮
- 一次最多 100 条

### 3. 手动标回未读
- 详情页右上角操作菜单「标为未读」
- 对应接口 `api/report/mark` 传 `action=unread`

## 接口对照
| 操作 | 接口 | 限制 |
|---|---|---|
| 获取未读总数 | `api/report/unread` | 仅查询自己侧 |
| 批量标已读 | `api/report/read` 或 `api/report/mark?action=read` | 单次 ≤ 100 |
| 标回未读 | `api/report/mark?action=unread` | 单次 ≤ 100 |

## 已读如何存储
每条 `report_receives` 行（每个接收人一条）有独立的 `read` 字段：
- `0` = 未读
- `1` = 已读

不是报告本身的属性，而是「接收人 × 报告」的关联属性。

## 未读数显示在哪
- 桌面端：头像下拉菜单「工作报告」右侧红点
- 桌面端：「我收到的」Tab 标题旁数字
- 移动端：Tabbar「我的」红点

## 不支持
- 把别人侧的报告改成已读 / 未读（只能操作自己的）
- 不支持批量操作超过 100 条
- 已读时间戳（系统只记 read=0/1，不存时间）
