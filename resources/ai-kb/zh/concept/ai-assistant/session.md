---
id: ai-assistant.session.concept
title: AI 助手会话
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 会话
  - AI 历史
  - AI 对话记录
  - AI session
  - session_key
  - 会话桶
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 单个桶最多 20 条会话，超过自动淘汰最早
  - 嵌入式入口的会话不出现在浮窗历史里
  - 删除会话不可恢复，对应图片也会从 public/uploads 物理删除
last_verified: v1.7.90
---

# AI 助手会话

## 定义
会话（session）是 AI 助手中一组连续的用户提问 + AI 回答集合，按「场景桶」隔离持久化到 `ai_assistant_sessions` 表，归当前用户所有。

## 关键属性

| 字段 | 含义 |
|---|---|
| `session_key` | **场景桶**，浮窗用 `global`，嵌入入口用各自 key |
| `session_id` | 唯一 id，格式 `session-{时间戳}-{随机串}` |
| `title` | 自动取首条用户消息前 20 字 |
| `data` | JSON 数组，存所有 user/assistant/system 消息 |
| `images` | JSON 对象 `{imageId: 服务端路径}` |
| `updated_at` | 历史列表按其倒序 |

## 持久化时机
- 流式回答完成后立即保存
- 编辑历史问题、删除消息后保存
- 普通编辑期间有 2 秒防抖

## 桶（session_key）的作用
不同业务入口走不同桶：
- `global`：浮窗 + 快捷键 + 顶部「+」菜单
- `project-create`：新建项目 AI 助手
- `task-add`：新建任务 AI 助手
- `report-edit`：工作汇报 AI
- `chat-message`：消息输入 AI

切桶时先保存当前会话再载入目标桶历史列表，避免互相串扰。

## 数量限制
- 每个桶最多保留 20 条（`maxSessionsPerKey`）
- 超过从最旧的开始淘汰
- 单会话内最多 50 条 message（`maxResponses`）

## 不支持
- 不支持跨用户 / 跨桶搜索；只查当前用户当前桶
- 不支持把某条会话从一个桶导到另一个桶
- 不支持手动改会话标题

## 相关
- [[ai-assistant.session-list.howto]]
- [[ai-assistant.new-chat.howto]]
- [[ai-assistant.session-delete.howto]]
- [[ai-assistant.embed-entry.concept]]
