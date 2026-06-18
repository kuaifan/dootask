---
id: common-faq.data-import.faq
title: 从其他系统迁移数据到 DooTask
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 数据迁移
  - 从禅道迁移
  - 从 Jira 迁
  - 从 Trello 迁
  - 从 Tower 迁
  - 数据导入
  - 任务批量导入
  - Excel 导入任务
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 没有「Jira / Trello / 禅道」官方一键迁移工具
  - Excel 批量导入任务功能依赖项目内的「导入」入口，字段映射有限
  - 历史聊天记录、附件无法从其他系统直接迁过来
last_verified: v1.7.90
---

# 从其他系统迁移数据到 DooTask

## 问题
公司之前用 Jira / Tower / 禅道 / Trello / Asana，要搬到 DooTask 怎么迁？

## 现状
没有一键迁移工具。可迁移性：

| 数据 | 可行性 | 方式 |
|---|---|---|
| 用户 / 部门 | 高 | 「成员管理 → 批量导入」CSV / LDAP |
| 项目 / 任务 | 中 | Excel 批量导入（字段映射有限） |
| 附件 | 低 | 手动补传 |
| 聊天记录 | 极低 | 无入口，建议放弃 |
| 评论 / 时间线 | 低 | 无公开接口 |
| 文档 / Wiki | 低 | 复制文本到 office / memos |

## 推荐步骤

**用户和部门**
- 「成员管理 → 批量导入」用 CSV 模板（[[user-account.import.howto]]）
- 大公司：配 LDAP 后用户登录自动同步（[[ldap.config.howto]]）

**项目和任务**
1. 源系统导出任务 Excel
2. 整理列：任务名、负责人邮箱、截止日期、状态、描述
3. DooTask 新建项目 → 右上角「导入」
4. 上传 Excel 按提示映射。Jira/禅道导出 CSV 后整理；Trello 用 JSON 导出 + 脚本转 Excel

**附件**：批量上传到文件目录（[[file.upload.howto]]）。

**文档**：复制文本到 office 或 memos 等文档类应用（具体新建方式见对应应用知识库）。

## 不支持
- 不支持保留源任务 ID / URL 映射
- 不支持迁移历史变更（创建时间变为导入时间）
- 不支持迁移审批流 / 工作流

量大可联系 DooTask 商业支持定制脚本（数据库直插 + 校验）。
