---
id: report.create.howto
title: 撰写工作报告
type: howto
feature: report
scope: end-user
locale: zh
aliases:
  - 怎么写报告
  - 新建汇报
  - 写日报
  - 写周报
  - 提交工作总结
  - 新建工作报告
related_tools: [create_report]
related_pages: []
prerequisites: []
negative:
  - 同一周期同类型只能提交一份，重复提交报「请勿重复提交工作汇报」
  - 不能提交未来周期的报告（offset 必须 ≤ 0）
  - 标题和内容均必填，留空会报「请填写标题/汇报内容」
last_verified: v1.7.90
---

# 撰写工作报告

## 入口
- 桌面端：右上角头像 →「工作报告」→ 右上角「+」→ 选「周报」或「日报」
- 桌面端：应用中心 →「工作报告」→「+」
- 移动端：「我的」→「工作报告」→「+」

## 操作步骤
1. 选择类型：周报 / 日报 [[report.type.concept]]
2. 系统自动用模板填充内容（基于本周期内自己负责的任务）[[report.template.howto]]
3. 编辑标题（默认带用户名 + 日期，可手动改）
4. 在富文本编辑器中补充内容（支持表格、图片粘贴、链接）
5. 在「接收人」字段选择要发送给谁（可空，留空就是只保存自己看）
6. 点「提交」保存并发送 → [[report.submit.howto]]

## 必填字段
| 字段 | 校验规则 |
|---|---|
| title | 必填，单行文本 |
| type | 必填，weekly 或 daily |
| content | 必填，HTML 富文本 |
| offset | ≤ 0（不能未来） |
| receive | 可空，数组形式的接收人 userid |

## 内容富文本能力
- 表格 / 编号列表 / 引用
- 粘贴 base64 图片：保存时自动落盘到 `uploads/report/{年月}/{rid}/attached/`
- 链接、加粗、标题

## 不支持
- 不支持单周期重复提交：会报错，要改请走编辑 [[report.edit.howto]]
- 提交未来周期：offset 必须 ≤ 0
- 不支持月报 / 季报：type 只接受 weekly / daily
