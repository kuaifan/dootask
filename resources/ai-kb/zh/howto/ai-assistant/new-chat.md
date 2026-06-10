---
id: ai-assistant.new-chat.howto
title: 新建一个 AI 助手会话
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 新建 AI 会话
  - 重新开始 AI
  - 开一段新对话
  - 清空当前对话
  - 不要带上下文
  - AI 重新聊
related_tools: []
related_pages: []
prerequisites:
  - AI 助手浮窗已打开
negative:
  - 新建会话不会删除旧会话，只是把旧会话归档到历史列表
  - 新建会话只清空输出区与上下文，不影响模型选择 / 浮按钮位置
  - 嵌入入口的「新建」按钮不出现在浮窗，由各业务入口控制
last_verified: v1.7.90
---

# 新建一个 AI 助手会话

## 何时需要新建
- 想换话题，又不希望 AI 受当前会话历史影响
- 当前会话上下文太长，回答变慢或质量下降
- 不想让接下来的提问污染历史

## 入口
1. 打开 AI 助手浮窗
2. 浮窗**右上角操作区**有一个「新建会话」图标
3. 仅当 `sessionEnabled = true` **且**（当前会话有消息 **或** 历史列表非空）时显示

## 行为
点击「新建会话」按钮：
1. 当前会话**自动保存**到历史列表（标题取首条提问前 20 字）
2. 生成新 `session_id`（格式 `session-{时间戳}-{随机串}`）
3. 清空 `responses`，页面输出区清空
4. 上下文 context 归零，下一次提问只发当前消息 + 默认 system prompt
5. 当前模型选择保留

## 不会影响什么
- 模型下拉的当前选择
- 浮按钮位置 / 收起状态
- 历史会话列表（仅多一条）
- 图片缓存（已发送过的图片仍可被历史引用）

## 其他「新建」的等价路径
- **删除当前会话**：不想留底就直接删除当前会话，会自动建一个空会话进入（见 [[ai-assistant.session-delete.howto]]）
- **切到其他历史会话**：会切走当前会话，但当前会话仍保留在历史里

## 不支持
- 不支持「新建会话同时换模型」（先切模型再新建）
- 不支持「新建空会话但保留前一轮上下文」
- 不支持指定新会话标题（永远自动生成）

## 相关
- [[ai-assistant.session.concept]]
- [[ai-assistant.session-list.howto]]
- [[ai-assistant.session-save.howto]]
