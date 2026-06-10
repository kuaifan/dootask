---
id: common-faq.ai-token-cost.faq
title: AI 用量与 token 成本
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - AI 收费
  - token 成本
  - AI 用了多少钱
  - AI 配额
  - AI 限流
  - 用量统计
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
negative:
  - DooTask 本身不收费——但你接入的 OpenAI / Claude / 通义等供应商按 token 计费
  - 没有内置的精确按用户成本分摊功能
  - 不能限制单用户每天调用次数（除非自研代理层）
last_verified: v1.7.90
---

# AI 用量与 token 成本

## 问题
管理员想知道 DooTask 接入 AI 后到底花了多少钱、谁在用、能不能给用户限额。

## 计费逻辑
- DooTask 主程序和 ai 插件本身**不向用户收费**
- 调用费用直接产生在所配供应商侧——OpenAI / Anthropic / 通义 / 豆包 / 智谱 / DeepSeek 等账户余额
- 每次回复消耗 token = 输入 prompt（系统提示 + 上下文 + 用户问题 + 知识库检索）+ 输出文本
- **工具调用结果回灌**也算 token：模型读工具结果后再生成回复，结果文本会算在输入里
- 多轮会话越长 token 越多（输入 = 整段历史）

## 查询用量
1. 登录对应供应商的开发者后台（如 OpenAI Platform → Usage）看月度账单
2. ai 插件容器日志 `docker logs dootask-ai` 包含调用记录，可统计调用次数
3. 暂无 web 界面按 DooTask 用户分摊成本

## 控制成本
1. **选轻量模型**：在 [[system-setting.ai-bot.howto]] 默认模型选 GPT-4 mini / Claude Haiku / Qwen-turbo / deepseek-chat 等，比顶级模型便宜 10-100 倍
2. **限制上下文长度**：让插件层裁剪最大轮数（需修改插件配置）
3. **关闭高频自动调用**：如「自动总结报告」「自动智能拆解」等按需触发
4. **供应商侧设硬上限**：在 OpenAI 控制台设 monthly budget cap，超额自动断
5. **多供应商负载分流**：成本不同的模型分给不同场景

## 不支持
- 没有内置「单用户每天 N 次」配额
- 没有「按部门分摊账单」表
- 不能在 DooTask 内冻结单个用户的 AI 使用

## 推荐做法
- 试用阶段：用便宜模型 + 小额预算
- 重度场景：在供应商账户设硬性月度上限
- 长期：监控发票 → 不合理涨幅时让团队复盘高耗用户

供应商配置入口：[[system-setting.ai-model.howto]]
