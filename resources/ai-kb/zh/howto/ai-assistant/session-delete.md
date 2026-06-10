---
id: ai-assistant.session-delete.howto
title: 删除 AI 助手历史会话
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 删除 AI 历史
  - 清空 AI 对话
  - 删除 AI 会话
  - AI 历史不要了
  - 删除 AI 聊天记录
related_tools: []
related_pages: []
prerequisites:
  - 当前桶有 ≥ 1 条会话
negative:
  - 删除不可恢复，相关图片也会从 public/uploads 物理删除
  - 删除单条无二次确认；清空全部有确认弹窗
  - 删除当前桶不会影响其他桶
last_verified: v1.7.90
---

# 删除 AI 助手历史会话

## 入口
1. 打开 AI 助手浮窗
2. 浮窗右上角点「历史」图标展开下拉

## 删除单条
1. 鼠标移到目标会话条目上
2. 点条目右侧小垃圾桶图标
3. **立即删除**（无确认）：
   - 后端调 `POST api/assistant/session/delete`，参数 `{session_key, session_id}`
   - 服务端删除会话记录及对应 `public/uploads/assistant/YYYYMM/{userid}/` 图片
   - 如果删的是当前打开的会话，自动新建一个空会话替代

## 清空整个桶（全部历史）
1. 历史下拉最底部「清空历史记录」
2. 弹出确认弹窗 → 「确定」
3. 执行：
   - 调 `POST api/assistant/session/delete`，参数 `{session_key, clear_all: true}`
   - 后端清空当前桶所有会话 + 物理删除所有图片
   - 前端清空内存 `imageCache` 和 `serverImageMap`
   - 自动新建一个空会话进入

## 不影响其他桶
- 浮窗用 `session_key = global`，清空只删 `global` 桶
- 嵌入入口（`project-create`、`task-add` 等）的会话**不受影响**

## 误删可恢复吗？
**不能。** 删除即写库 `DELETE`，物理删除关联图片，**无回收站、无 30 天保留**。要保留的对话请提前复制到笔记。

## 不支持
- 不支持选择多条批量删除（只能单删或清空全部）
- 不支持「软删除 / 30 天回收」
- 不支持按时间范围筛选删除

## 相关
- [[ai-assistant.session-list.howto]]
- [[ai-assistant.session.concept]]
- [[ai-assistant.new-chat.howto]]
