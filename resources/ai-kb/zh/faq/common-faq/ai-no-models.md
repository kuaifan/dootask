---
id: common-faq.ai-no-models.faq
title: AI 助手没有可用模型
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 没有模型
  - 模型为空
  - AI 用不了
  - 找不到 AI
  - AI 不响应
  - 模型下拉空
related_tools: []
related_pages: [ai_assistant_panel]
prerequisites: []
negative:
  - DooTask 主程序不内置任何 AI 模型，必须靠 ai 插件接外部供应商
  - 配好模型不代表立即能用——还需在「AI 机器人」选定默认模型
  - 普通用户看不到模型管理页面，只能等管理员配置
last_verified: v1.7.90
---

# AI 助手没有可用模型

## 问题
点开 AI 助手浮窗或在任务详情用「智能拆解」「AI 报告」时，模型选择下拉为空、或者直接提示「未配置 AI 模型」「AI 服务不可用」。

## 原因
DooTask 的 AI 能力由独立的 **ai 插件**（`dootask-ai`）提供。下面任一未到位都会无可用模型：

1. **应用市场没装 ai 插件**：主程序没自带 AI 容器，必须从插件市场安装
2. **插件装了但没配模型**：插件需要在「系统设置 → AI 设置」里加供应商（OpenAI / Anthropic / 通义 / 豆包 / 智谱 / DeepSeek 等）+ API Key
3. **配了供应商但没勾默认模型**：系统设置 → AI 机器人 → 默认模型为空时全员无法用
4. **API Key 过期 / 余额不足**：供应商侧失效会让请求 401/402，前端表现为「模型不可用」
5. **dootask-ai 容器异常**：插件容器 crash 或网络问题，`/ai/*` 路由 502

## 解决
1. 让管理员到 [[appstore.install.howto]] 装 ai 插件
2. 让管理员到 [[system-setting.ai-model.howto]] 配供应商和模型
3. 让管理员到 [[system-setting.ai-bot.howto]] 勾选默认模型
4. 自己试一下不行 → 管理员到对应供应商后台看 API Key 是否有效、余额够
5. 管理员排查容器：`docker ps | grep dootask-ai`；`docker logs dootask-ai`

## 不支持
- 普通用户不能自己加 API Key（只能管理员）
- 主程序不能跳过 ai 插件直连模型
- 一次只能选一个默认模型作为系统主模型

更多 AI 助手能力说明随 ai 插件知识库提供。
