---
id: ai-assistant.task-summary.howto
title: 让 AI 总结任务进展
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 总结任务
  - 任务进展总结
  - AI 看下进度
  - 帮我汇总任务
  - 任务状态怎么样
  - AI 复盘任务
related_tools: [get_task, get_message_list]
related_pages: [task_detail]
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 当前用户可访问该任务
negative:
  - 总结仅基于任务详情 + 讨论历史 + 子任务状态，不读外部资料
  - 不会改任务状态，只读后输出文本
  - 历史评论超 100 条时只取最近 100 条
last_verified: v1.7.90
---

# 让 AI 总结任务进展

## 这是什么
让 AI 读取一个任务的标题、描述、子任务、讨论记录，输出当前进展概要，常用于会议汇报、周报、交接。AI 调 `get_task` + `get_message_list` 两个工具取数据，再综合生成。

## 怎么触发
- **任务讨论区** @AI："总结一下这个任务到现在的进展"
- **浮窗**："帮我总结任务 ID 1234 的进展" 或先调 `open_task` 再说"总结这个"
- **批量**：浮窗里说"帮我总结项目 X 下所有进行中的任务" → AI 会先 `list_tasks` 再逐个总结

## 总结内容通常包含
- 任务当前状态（未开始 / 进行中 / 已完成 / 已逾期）
- 子任务完成度（如 3/5）
- 关键讨论结论（最近的决策点）
- 待解决/阻塞项（从评论中提取"需要"/"阻塞"/"等"等关键词）
- 下一步建议（可选）

## 让总结更准
- 模糊任务请加 ID 或唯一标题词
- 想突出某一面（"只说阻塞"、"只说进度数字"）就在提问中明示
- 想发到群/写进报告 → 让 AI 直接 `send_message` 或追加"作为周报素材"

## 不支持
- 不会自动汇总跨项目（除非显式列出任务 ID）
- 不读取任务附件正文（如 PDF 内容），需先 `fetch_file_content`
- 不能生成图表/甘特图，只输出文字

## 相关
- 任务讨论 @AI：[[ai-assistant.task-mention.howto]]
- 列任务：[[ai-assistant.list-tasks.howto]]
- 生成工作报告：[[ai-assistant.report-draft.howto]]
