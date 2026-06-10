---
id: messenger.search.howto
title: 搜索消息和会话
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么搜消息
  - 群消息搜索
  - 找历史聊天记录
  - 搜聊天
  - 找一条消息
  - 搜索会话
related_tools: [search_dialogs, intelligent_search, get_message_list]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 历史消息全文检索依赖 Manticore，未启用时仅能按会话名和联系人搜，不能命中消息内容
  - 文件消息按文件名 / 描述命中，不支持按文件正文（OCR 内容除外）
  - 搜索结果一次最多返回 20 条，超过需要更具体关键词
last_verified: v1.7.90
---

# 搜索消息和会话

「搜索」（dialog search）覆盖三类目标：会话名（含群名和单聊对方昵称）、联系人（用户）和历史消息（全文检索）。命中后可直接跳转到对应会话或定位到消息。

## 入口

- 桌面端：左侧栏「消息」顶部搜索框
- 桌面端：全局顶部搜索框（也含任务 / 项目 / 文件）
- 移动端：进入「消息」Tab → 顶部下拉搜索

## 操作步骤

1. 输入关键词（必填，可中英文混合）
2. 系统按以下顺序填充结果：
   - WebSocketDialog 会话名匹配
   - 联系人（User）昵称 / 邮箱匹配
   - 历史消息全文检索（Manticore）
3. 命中后点击直接打开对应会话；消息命中会自动滚到该条

## 仅搜会话

接口可传 `dialog_only=1`，跳过消息全文检索，只返回会话 / 联系人。常用于「找张三的聊天窗」场景。

## 搜标注消息

`/api/dialog/search/tag` 单独列出最近 50 条被标注（tag>0）的消息，按时间倒序，便于复盘重点。

## 不支持

- 不支持按时间区间筛选消息搜索结果，需在客户端再过滤
- 不支持搜索语音 / 视频内容（除非已 OCR 或转文字）
- 不支持搜索已撤回 / 已删除消息（这些已从索引剔除）
