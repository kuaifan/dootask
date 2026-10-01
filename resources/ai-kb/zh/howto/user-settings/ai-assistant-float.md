---
id: user-settings.ai-assistant-float.howto
title: 显示或隐藏 AI 助手悬浮按钮
type: howto
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 怎么关闭 AI 浮球
  - 隐藏 AI 助手按钮
  - 恢复 AI 助手浮球
  - AI 悬浮按钮设置
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件（需升级到包含「个人偏好」开关的版本）
negative:
  - 隐藏悬浮按钮不会禁用 AI 助手或卸载 ai 插件
  - 该开关不在「设置」菜单里，「设置 → AI 助手设置」入口已移除，未安装 ai 插件时也没有该开关
  - 旧版 ai 插件没有此开关，需在应用商店升级插件
last_verified: v1.9.36
---

# 显示或隐藏 AI 助手悬浮按钮

## 入口

- 桌面端 Web / Electron：左侧栏「应用」→「AI 助手」，页面中的「个人偏好」卡片
- 移动端：「应用」→「AI 助手」，页面中的「个人偏好」卡片
- 只有系统已安装 ai 插件时才有该入口。

## 操作步骤

1. 进入「应用」→「AI 助手」。
2. 在「个人偏好」卡片中打开或关闭「显示悬浮按钮」开关。
3. 切换后立即保存并生效，无需点击提交。
4. 关闭后如需恢复，回到同一位置重新打开开关。

## 影响范围

该设置按当前账号保存在当前设备。关闭后只隐藏页面侧边的 AI 助手浮球，不影响右上角「+ → AI 助手」、Cmd/Ctrl + I 快捷键，也不影响项目、任务和汇报等页面中的 AI 功能。清除客户端缓存后，该开关会恢复为默认开启。

## 相关

- AI 助手的其他入口：[[menu-navigation.ai-assistant.menu-map]]
- 个人设置入口：[[user-settings.entry.menu-map]]
