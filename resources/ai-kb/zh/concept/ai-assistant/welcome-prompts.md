---
id: ai-assistant.welcome-prompts.concept
title: AI 助手欢迎语与快捷提示
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 欢迎页
  - AI 快捷提示
  - AI 推荐问法
  - AI 起手
  - AI 建议
  - AI 提示卡片
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 用户不能自定义快捷提示卡片
  - 卡片在每次场景切换时会重抽，并非固定不变
  - 卡片点击只是把文本填入输入框，不会自动发送
last_verified: v1.7.90
---

# AI 助手欢迎语与快捷提示

## 定义
打开 AI 助手浮窗、**还没有任何对话**时显示的引导界面，包含 AI 图标 + 「欢迎使用 AI 助手」标题 + 若干个**快捷提示卡片**。卡片内容根据当前页面场景动态切换。

## 卡片分类
按四类设计确保多样性：

| 类型 | 含义 | 示例 |
|---|---|---|
| `query` | 查询 / 概览 | 「我今天有哪些任务？」 |
| `action` | 推进 / 操作 | 「帮我把这条任务标记完成」 |
| `sync` | 同步 / 协作 | 「给项目组发一条进度更新」 |
| `review` | 复盘 / 总结 | 「总结一下这周的工作」 |

## 与页面场景联动
卡片基于以下数据动态生成：
- 当前路由（仪表盘 / 项目 / 任务 / 消息 / 文件 / 日历）
- 当前打开的弹窗（任务 / 对话）
- 当前项目 / 任务 / 对话 id
- 当前语言（中 / 英）

切换页面时通过 `welcomePromptsKey` watcher 触发，100ms 防抖避免闪屏。

## 点击行为
1. 点击某张卡片
2. 卡片文本被填入输入框
3. 输入框自动获焦
4. **不会**自动发送，需手动按发送或回车

## 显示条件
- AI 助手浮窗已打开
- `visibleResponses.length === 0`（当前会话无消息）
- `displayMode === 'chat'`（modal 模式由业务方控制）

## 何时刷新
- 路由变更
- 打开 / 关闭任务弹窗
- 打开 / 关闭对话弹窗
- 切换项目

均走 `welcomePromptsKey` watcher + 100ms 防抖。

## 不支持
- 不支持自定义卡片文案
- 不支持「关闭欢迎提示卡片」开关
- 不支持把某条卡片置顶或常驻

## 相关
- [[ai-assistant.modal.concept]]
- [[ai-assistant.page-context.concept]]
