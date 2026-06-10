---
id: report.analysis.concept
title: 工作汇报的 AI 解读
type: concept
feature: report
scope: end-user
locale: zh
aliases:
  - AI 解读工作汇报
  - 工作汇报 AI 总结
  - 周报点评
  - 工作汇报分析
  - ReportAnalysis
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
negative:
  - 解读结果按查看者独立保存，不会出现在其他查看者侧
  - 只支持工作汇报的提交人或接收人保存自己的解读，其他人无权
  - 解读是离线快照，不会随工作汇报内容修改而自动重算
last_verified: v1.7.90
---

# 工作汇报的 AI 解读

## 定义
工作汇报的 AI 解读（ReportAnalysis）是某个查看者基于一份具体工作汇报生成的 AI 总结 / 点评 / 建议文本。每个查看者（提交人或接收人）有独立的解读记录，互相不共享。

## 关键属性
- **绑定关系**：每条解读关联到 (`rid` 工作汇报, `userid` 查看者) 唯一一条
- **存储位置**：`report_ai_analyses` 表
- **解读内容**：Markdown 文本 `analysis_text`，保存时也记录使用的模型名 `model`
- **元信息 meta**：可选记录查看者角色 / 名称 / 关注点（focus），帮助 AI 后续个性化

## 触发流程
1. 工作汇报详情页右侧「AI 解读」面板首次展开，触发 AI 在线生成
2. AI 调用相应工具读取工作汇报原文 + 上下文，输出 Markdown 总结
3. 用户点「保存」走 `api/report/analysave` 落库
4. 下次打开同一工作汇报，详情接口直接带出 `ai_analysis.text`，无需重生

## 与「AI 生成草稿」的区别
- AI 草稿 [[report.ai-generate.howto]]：写之前帮你生成 **正文**
- AI 解读：工作汇报 **已存在** 后，为某位查看者总结要点 / 风险 / 进度

## 权限
- 提交人 / 接收人可保存属于自己的解读
- 非提交人非接收人调用 `analysave` 会被 `userCanAccessReport` 拒绝（返回「无权访问该工作汇报」）

## 与 [[report.concept]] 的关系
解读是工作汇报的派生数据，不影响工作汇报本体；删除工作汇报时关联解读也会失效。
