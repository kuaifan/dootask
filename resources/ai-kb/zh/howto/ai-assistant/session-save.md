---
id: ai-assistant.session-save.howto
title: AI 助手会话自动保存
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 会话保存
  - AI 对话留底
  - AI 会话不保留
  - AI 没保存
  - 手动保存 AI
related_tools: []
related_pages: []
prerequisites:
  - 会话管理已启用（`sessionEnabled = true`，浮窗自动开启）
negative:
  - 没有「立即保存」按钮，全部自动；不会丢失最新一条
  - 嵌入入口未传 sessionKey 时不会启用保存（一次性对话）
  - streaming / waiting 状态会被归一为 error 再写库，避免重启卡 loading
last_verified: v1.7.90
---

# AI 助手会话自动保存

## 是否需要手动保存
**不需要。** AI 助手浮窗的会话保存全自动。以下时机触发保存：

1. **流式回答完成**：AI 最后一个 chunk 到达，标记 `completed` 后立即保存
2. **流式失败 / 用户中断**：归一未完成态为 `error` 后保存
3. **手动新建会话 / 切换会话 / 切桶**：先保存当前会话再切换
4. **常规编辑（删消息、编辑提问）**：2 秒防抖批量写入

## 保存到哪
- 数据库表：`ai_assistant_sessions`，按 `userid` 隔离
- 接口：`POST api/assistant/session/save`
- 字段：`session_key`、`session_id`、`title`、`data`（JSON 消息流）、`new_images`（新增图片 base64 列表）

## 标题如何生成
- 取首条 `role !== 'system'` 的 `prompt` 前 20 字
- 无任何用户提问时显示「新会话」
- 标题不可手动改

## 图片同步上传
- 保存时仅传**本次新增**的图片 base64
- 后端写入 `public/uploads/assistant/YYYYMM/{userid}/xxx.jpg`
- 返回 `image_urls` 映射，前端缓存到 `serverImageMap`，下次打开会话用 URL 显示

## 防抖与容错
- 普通编辑 2 秒防抖
- 流式结束 / 失败立即写入
- 写入失败仅 console.warn，下次保存补上

## 什么时候不保存
- 嵌入入口未传 sessionKey → 该入口走「一次性对话」，关闭后不留底
- 当前会话内一条消息都没有 → 不创建空会话记录

## 不支持
- 不支持云端跨用户 / 跨工作区同步
- 不支持「保存到本地文件」选项
- 不支持暂停自动保存

## 相关
- [[ai-assistant.session.concept]]
- [[ai-assistant.session-list.howto]]
- [[ai-assistant.stop.howto]]
