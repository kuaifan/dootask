---
id: user-settings.theme.howto
title: 切换深色/浅色主题
type: howto
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 改主题
  - 深色模式
  - 暗黑模式
  - dark mode
  - 跟随系统
  - 切换主题
  - 浅色模式
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 浏览器 Web 端仅 Chrome 系（含 Edge）才支持深色切换；Safari / Firefox 老版本无效
  - iOS EEUI 端不支持手动切换主题（按系统设置走，提示「仅 Android 设置支持主题功能」）
  - 「跟随系统」依赖系统 / 浏览器 `prefers-color-scheme`，浏览器不支持时退化为浅色
last_verified: v1.7.90
---

# 切换深色/浅色主题

## 入口

- 桌面端：右上角头像 →「设置」→「主题设置」
- 移动端（Android EEUI）：「我的」→「设置」→「主题设置」
- 客户端：Electron 通过 IPC 同步主题，浏览器通过 localStorage 缓存

## 三种模式

| 模式 | 含义 |
|---|---|
| auto / 跟随系统 | 根据系统或浏览器 `prefers-color-scheme` 自动切换 |
| light / 浅色 | 强制浅色主题 |
| dark / 深色 | 强制深色主题 |

state 中存为 `themeConf`（用户选择）+ `themeName`（实际生效）。

## 操作步骤

1. 进入「主题设置」子页
2. 在「选择主题」下拉选 `auto` / `light` / `dark`
3. 点击「提交」后立即切换；缓存到 localStorage `__system:themeConf__`
4. Electron 客户端会 IPC 通知 preload 池重建，确保新窗口主题一致

## 不支持的环境

- Safari / 旧版 Firefox：无 `prefers-color-scheme` 或不响应主题切换，会弹「仅客户端或 Chrome 浏览器支持主题功能」
- iOS EEUI：不支持手动选主题，提示「仅 Android 设置支持主题功能」
- 部分插件 / 微应用未做深色样式适配，会显示与主站不一致的颜色

## 与高亮 / 颜色字段的关系

主题只影响界面整体配色，不会改：

- 任务卡片颜色（见 [[task.field.color.concept]] 之类的字段）
- 看板列颜色
- 用户自定义头像 / 头像背景
