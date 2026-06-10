---
id: ai-assistant.session-list.howto
title: 查看 AI 助手历史会话
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 历史会话
  - 我之前问过 AI 什么
  - 找回 AI 对话
  - AI 聊天记录
  - 看 AI 历史
related_tools: []
related_pages: []
prerequisites:
  - 当前桶至少有 1 条已保存的会话
negative:
  - 历史只显示当前用户、当前桶的会话
  - 嵌入入口的会话不出现在浮窗历史里
  - 历史按更新时间倒序，最近的在上面
last_verified: v1.7.90
---

# 查看 AI 助手历史会话

## 入口
1. 打开 AI 助手浮窗
2. 浮窗**右上角操作区**有「历史」图标
3. 点击展开下拉，列出当前桶内所有已保存会话

仅当桶内有 ≥ 1 条会话时该图标才出现。

## 显示内容
每条会话显示三行：
- 标题：取首条用户消息前 20 字
- 删除按钮（右上角小垃圾桶）
- 更新时间：今天 / 昨天 / MM-DD HH:mm / YYYY-MM-DD HH:mm

排序：按更新时间倒序，最近聊的在最上面。

## 操作步骤

### 切换到某条历史会话
1. 在历史下拉里点要打开的会话标题
2. 当前对话内容自动保存
3. 浮窗输出区切换为该会话的消息流

### 删除单条会话
1. 鼠标移到会话条目上
2. 点条目右侧小垃圾桶图标
3. 立即删除（无二次确认）；同时清掉服务端会话图片

### 清空全部历史
1. 历史下拉最底部「清空历史记录」
2. 弹出确认弹窗 → 「确定」
3. 当前桶所有会话被删，图片缓存一并清空

## 数据来源
通过 `POST api/assistant/session/list` 拉取，按当前用户 + 当前 `session_key` 过滤。返回会话最多 20 条（受 `maxSessionsPerKey` 限制）。

## 不支持
- 不支持按关键词搜索历史
- 不支持给会话改名（标题永远是首条提问截取）
- 不支持把历史导出 CSV / 文本

## 相关
- [[ai-assistant.session.concept]]
- [[ai-assistant.session-delete.howto]]
- [[ai-assistant.new-chat.howto]]
