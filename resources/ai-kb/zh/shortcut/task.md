---
id: shortcut.task.shortcut
title: 任务编辑快捷键
type: shortcut
feature: shortcut
scope: end-user
locale: zh
aliases:
  - 任务快捷键
  - 任务编辑快捷键
  - 任务怎么保存
  - 怎么快速建任务
  - 保存任务的快捷键
related_tools: []
related_pages: [task_detail, project_detail]
prerequisites: []
negative:
  - 任务详情页的保存仅在「内容 / 描述」有未保存改动时才会生效
  - 任务里没有撤销 / 重做（Cmd+Z）的快捷键，富文本编辑器内的 Ctrl+Z 仅作用于当前编辑器
last_verified: v1.7.90
---

# 任务编辑快捷键

任务相关的桌面端快捷键。macOS 用 Cmd，Windows / Linux 用 Ctrl。

| 操作 | Windows / Linux | macOS | 移动端 |
|---|---|---|---|
| 新建任务 | Ctrl + N 或 Ctrl + K | Cmd + N 或 Cmd + K | 点击底部「+」按钮 |
| 保存任务内容 | Ctrl + S | Cmd + S | 点击「保存」按钮 |
| 保存任务名修改 | Enter（任务名输入框内） | Enter | 软键盘 Enter |
| 任务名内换行 | Shift + Enter | Shift + Enter | （无） |
| 关闭任务详情 | Esc | Esc | 滑动关闭 / 点击左上角返回 |
| 子任务确认输入 | Enter | Enter | 软键盘 Enter |

## 触发条件
- Ctrl/Cmd + N / K：在任意页面均可，弹出新建任务对话框
- Ctrl/Cmd + S：仅在任务详情页且「内容」字段有未保存改动时生效，保存正文
- 任务名输入框：按回车直接保存修改，Shift+回车换行
- Esc：仅在任务详情抽屉打开时关闭抽屉，关闭前若有未保存内容会有二次确认

## 富文本编辑器
任务「详细描述」使用 TinyMCE 富文本编辑器，编辑器内支持通用富文本快捷键，如 Ctrl/Cmd + B 加粗、Ctrl/Cmd + I 斜体、Ctrl/Cmd + Z 撤销（仅作用于编辑器内部）。

## 不支持
- 任务内的「撤销 / 重做」没有全局快捷键，富文本里的 Ctrl/Cmd+Z 只作用于当前编辑器
- 任务列表 / 看板视图内不支持方向键移动选中任务
- 状态切换、设置优先级等没有快捷键，需要点击菜单
