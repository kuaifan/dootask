---
id: web-client.compatibility.concept
title: 网页端浏览器兼容性
type: concept
feature: web-client
scope: end-user
locale: zh
aliases:
  - 支持什么浏览器
  - 浏览器版本要求
  - IE 能用吗
  - Safari 能用吗
  - 兼容性
  - 用什么浏览器最好
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 不支持 IE 全系列（IE 11 及以下）
  - 不支持 Edge Legacy（基于 EdgeHTML 内核的旧 Edge）
  - 不支持 QQ 浏览器 X5 内核的兼容模式（部分 ES2017+ 语法不识别）
last_verified: v1.7.90
---

# 网页端浏览器兼容性

## 推荐浏览器
DooTask 网页端基于 Vue 2 + Vite 构建，使用现代 ES 语法，**必须用支持 ES2017+ 的浏览器**。下表为推荐最低版本：

| 浏览器 | 最低版本 | 备注 |
|---|---|---|
| Chrome | 90+ | 推荐，体验最完整 |
| Edge（Chromium） | 90+ | 推荐 |
| Firefox | 88+ | 支持 |
| Safari | 14+ | macOS 11+ / iOS 14+ 自带 |
| Opera | 76+ | 支持 |
| 国产双核浏览器 | 切换到「极速 / 高速」模式 | 使用 Chromium 内核 |

## 不支持的浏览器
- **IE 11 及以下**：不支持 ES Modules，所有 ES6+ 语法都不识别
- **Edge Legacy**（2020 年前的旧 Edge，EdgeHTML 内核）
- **微信 / QQ / UC 内置浏览器**：部分功能可能异常（WebSocket、文件上传、富文本），建议改用桌面端 / App
- **QQ 浏览器 / 360 浏览器的「兼容模式」**：会强制使用 IE 内核

## 如何确认浏览器版本
- Chrome：地址栏输入 `chrome://version`
- Edge：地址栏输入 `edge://version`
- Firefox：菜单 → 「帮助」→「关于 Firefox」
- Safari：菜单栏 → 「Safari」→「关于 Safari」

## 浏览器太旧怎么办
1. 升级到最新版本（大多数浏览器自动更新）
2. 换用 Chrome / Edge
3. 使用 [[electron-client.concept]] 桌面端（自带兼容的 Chromium 内核）
