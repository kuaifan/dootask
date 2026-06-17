---
id: ai-assistant.page-action.howto
title: 让 AI 帮我跳页面/打开任务
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 打开任务
  - AI 切换项目
  - AI 跳到仪表盘
  - 让 AI 帮我开 X
  - AI 跳转
  - AI 帮我打开
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 浏览器/桌面端会话所在页面已加载
negative:
  - 只在当前浏览器标签内导航，不会新开标签
  - AI 不能打开你没权限访问的资源（会被后端拒绝）
  - 若任务被删，AI 跳转后会落到 404 / 提示「任务不存在」
last_verified: v1.7.91
---

# 让 AI 帮我跳页面/打开任务

## 这是什么
在 AI 浮窗用自然语言让 AI 把当前页跳转到任务详情、对话、项目、文件预览或功能页。AI 助手会在你当前的浏览器/桌面端页面上执行真实路由跳转。

## 怎么问
- "打开任务 1234"
- "切到项目『官网改版』"
- "打开和小王的对话"
- "帮我打开仪表盘"
- "跳到日历"
- "打开文件 ID 5678"
- "关闭这个应用" / "把当前应用关掉"（打开微应用插件后，可让 AI 直接关闭当前应用窗口，无需自己点关闭按钮）

## 支持的跳转目标
- 任务详情（open_task / goto_task / navigate_to_task）
- 对话（open_dialog，可附带 msg_id 跳到指定消息）
- 项目主页（open_project）
- 文件预览（open_file）/ 文件夹（open_folder）
- 功能页：仪表盘 / 消息 / 日历 / 文件管理

## AI 怎么找到目标
- 你给 ID 时直接跳
- 你给名字时 AI 会先调 `list_tasks` / `search_dialogs` / `list_projects` / `search_files` 找候选
- 同名多选时列候选让你确认

## 跳转后还能继续
跳到目标页后，浮窗保持打开，可继续：

- "把它标完成" → 调 `complete_task`
- "看下讨论" → 调 `get_message_list`
- "拉到底部" → AI 在你的页面上滚动到底部

## 不支持
- 不能跳到外部网址（如 google.com）
- 不能在新标签打开
- 不能切换到其他用户的视图

## 相关
- 操作页面元素：[[ai-assistant.element-action.howto]]
- 页面操作机制：[[ai-assistant.page-action.concept]]
