---
id: ai-assistant.privacy.concept
title: AI 助手数据隐私
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 数据安全
  - AI 对话保存在哪
  - AI 会泄露吗
  - AI 上传图片去哪
  - DooTask AI 隐私
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 对话会通过 ai 插件转发给管理员配置的上游 LLM 服务商，受其隐私条款约束
  - 不存在「AI 完全本地化」开关，除非管理员只配 Ollama 本地模型
  - 用户无法自助导出会话（只能在浮窗手动清空历史）
last_verified: v1.7.90
---

# AI 助手数据隐私

## 数据存到哪里

### 对话内容
- 数据库表：`ai_assistant_sessions`，按 `userid` 行级隔离
- 其他普通用户绝对看不到别人会话
- 仅当前用户、系统管理员可通过后台数据库访问

### 图片
- 路径：`public/uploads/assistant/YYYYMM/{userid}/xxx.jpg`
- 按用户 id 分目录，删除会话时物理删除

### 配置 / 模型选择
- 存 IndexedDB `aiAssistant.model`（仅当前浏览器）
- 浮按钮位置 / 输入历史也存 IndexedDB，不上传

## 数据传给谁

```
浏览器
  → DooTask 后端 (POST api/assistant/auth)
  → ai 插件容器 (dootask-ai)
  → 上游 LLM 服务商 (OpenAI / Claude / DeepSeek 等)
```

- 上游服务商由**系统管理员**决定
- 选 Ollama 本地模型则对话不出 DooTask 部署网络
- 选公网服务则按服务商隐私条款处理

## 发送上去的内容
- 本次提问的文本 + 图片（多模态时）
- 当前会话最近 10 轮 context
- 当前页面弱提示词（仅页面类型 + 实体 id + 名称，**不含**业务详细数据）
- DooTask 默认 system prompt

## 不会发送的
- 用户密码、token、密钥
- 不在 context 窗口内的历史会话
- 其他用户对话
- 私聊 / 群聊具体消息（除非用户主动复制到提问）
- 完整页面业务数据（只发弱提示词）

## 清理与删除
- 单条 / 清空当前桶：[[ai-assistant.session-delete.howto]]
- 清浏览器 IndexedDB：清掉模型选择、浮按钮位置、输入历史
- 上游侧保留多久取决于服务商（如 OpenAI 默认 30 天）

## 不支持
- 不支持端到端加密让管理员都看不到
- 不支持把某条对话标记「不发送」
- 不支持自助导出全部历史

## 相关
- [[ai-assistant.auth.concept]]
- [[ai-assistant.session.concept]]
- [[ai-assistant.page-context.concept]]
