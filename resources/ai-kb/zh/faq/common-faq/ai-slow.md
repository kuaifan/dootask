---
id: common-faq.ai-slow.faq
title: AI 回复很慢
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - AI 慢
  - AI 卡
  - AI 反应慢
  - 模型很慢
  - AI 半天不出
  - 思考很久
related_tools: []
related_pages: [ai_assistant_panel]
prerequisites: []
negative:
  - DooTask 不缓存 AI 回复，每次问都是真请求
  - 流式延迟主要在模型供应商侧，主程序无能为力
  - 没有「换更快模型」按钮——需管理员在 AI 机器人换默认模型
last_verified: v1.7.90
---

# AI 回复很慢

## 问题
AI 助手发问后等很久才出第一个字，或一句话流式吐字过慢，整体感受卡顿。

## 原因
AI 回复时延受多重因素影响：

1. **模型本身**：GPT-4 / Claude Opus / Gemini Ultra 等大模型首 token 延迟天然较长（2-10 秒），思考模型可达 30 秒以上
2. **供应商网络**：国内访问海外 API 走代理 / 国外节点，加上 5-20 秒
3. **上下文太长**：会话越长 token 越多，处理时间线性增加
4. **工具调用串行**：一次回复触发多次工具调用会逐次执行，叠加时延
5. **dootask-ai 容器负载**：插件容器 CPU 满 / 内存吃紧
6. **流式断开重连**：网络抖动让 SSE 中断，前端自动重连看着像卡

## 解决
1. 等待，部分模型「思考」阶段就是慢
2. 简化问题，避免长上下文
3. 让管理员换更快模型（如 GPT-4 mini、Claude Haiku、deepseek-chat）：[[system-setting.ai-bot.howto]]
4. 让管理员排查 ai 插件容器：`docker stats dootask-ai`、`docker logs dootask-ai`
5. 改供应商代理 / Base URL，使用更近的中转节点
6. 网络抖动 → 切到稳定网络重试

## 不支持
- 用户端无法在请求中途强制切模型
- 无内置「响应慢自动降级」机制
- 中断回复只能整体停止本轮，不能保留已生成的文本继续

更多 AI 助手说明随 ai 插件知识库提供。
