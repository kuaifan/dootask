---
id: data-export.report.howto
title: 工作报告能不能批量导出
type: howto
feature: data-export
scope: admin
locale: zh
aliases:
  - 工作报告导出
  - 导出汇报
  - 批量导出报告
  - 周报月报导出 Excel
  - 报告 Excel
related_tools: [list_received_reports, get_report_detail]
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（仅在替代方案中）
negative:
  - 主程序后台没有「批量导出工作报告」入口
  - 没有 `api/report/export` 这样的接口
  - 不支持把多人多份报告打包成一个 Excel
last_verified: v1.7.90
---

# 工作报告能不能批量导出

## 结论
DooTask 主程序没有批量导出工作报告的功能。管理员后台「数据导出」只支持任务统计、超期任务、审批、签到 4 类，不包含工作报告。

## 替代方案
如果只是要拿到报告内容，可走以下路径：

1. 单篇查看 / 分享
   - 在「应用 → 工作报告」收到报告后打开详情，复制富文本内容
   - 用「分享」按钮把报告以消息形式转发到群聊（API `api/report/share`）

2. AI 工具批量读取
   - 通过 MCP 工具 `list_received_reports` 列出收到的报告
   - 用 `get_report_detail` 获取单份详情
   - 让 AI 助手按时段汇总写入文档

3. 数据库导出
   - 仅 DBA：从 `report` 表按 `created_at` 区间导出（需要 SSH + MySQL 权限）
   - 不推荐普通管理员操作

## 不支持
- 不支持在产品 UI 内批量导出工作报告为 Excel
- 不支持按部门 / 成员一键拉取所有报告

## 相关
- 数据导出整体能力：[[data-export.concept]]
- 入口：[[data-export.entry.menu-map]]
