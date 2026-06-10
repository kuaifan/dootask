---
id: ai-assistant.guide.concept
title: 什么是页面深链（带我去）
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 操作引导
  - 带我去
  - 带我操作
  - 页面深链
  - 快捷跳转
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
negative:
  - 深链只负责把你送到对应页面/面板，不会自动帮你点击页面内的具体按钮或开关
  - 需要运行时定位的目标（某条具体任务/对话）不通过深链，由 AI 直接帮你打开
last_verified: v1.7.90
---

# 什么是页面深链（带我去）

## 这是什么
当你问 AI「X 在哪里设置 / 怎么去 X」时，AI 的回答里会把可定位的页面或面板（如「系统设置」「个人设置」「日历」）渲染成**蓝色可点击的链接**。点一下就**直接跳转到那一屏**，省去自己翻菜单。

## 怎么用
- 在 AI 助手浮窗里问操作类/“在哪”类问题
- 回答正文里出现蓝色链接词，点击即跳转（AI 浮窗会自动收起，避免遮挡）
- 到达目标页后，按回答里的文字说明完成剩下的操作

## 边界
- 深链把你送到**正确的页面/面板**；页面内具体那一行开关/按钮在哪，需要你按说明自己看一眼
- 不是每个名词都可点：只有命中系统内已登记目的地的词才会变成链接，其余按普通文字显示
- 打开某条具体任务/对话（需要具体 ID）不走深链，AI 会直接帮你打开

## 相关
- 如何使用：[[ai-assistant.start-guide.howto]]
- AI 操作页面的底层能力：[[ai-assistant.page-action.concept]]
