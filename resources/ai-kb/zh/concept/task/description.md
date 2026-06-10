---
id: task.field.description.concept
title: 任务描述（富文本）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务描述
  - 任务详情
  - 任务正文
  - 富文本任务
  - 任务备注
related_tools: [update_task]
related_pages: [task_detail]
prerequisites: []
negative:
  - 描述富文本上限 50000 字符（含 HTML 标签）
  - 描述不能直接附文件，需要单独走任务附件区
  - 描述无版本回滚 UI，仅服务端保留历史（ProjectTaskContent 表）
last_verified: v1.7.90
---

# 任务描述（富文本）

## 定义
DooTask 任务描述（`desc` 字段 + `ProjectTaskContent` 表）支持富文本编辑，包括加粗、列表、链接、代码块、表格、图片、@ 提及成员等。短描述存 `desc`（500 字符快照），完整富文本存 `ProjectTaskContent` 表，每次保存生成新版本记录。

## 在哪里能看到
- 任务详情页正中央富文本区
- 卡片视图鼠标悬停显示描述前 500 字符
- 搜索结果按 desc 做文本匹配

## 编辑器能力
- 文本：粗体、斜体、下划线、删除线、代码、行内代码、引用
- 块级：标题、有序 / 无序 / 任务列表、代码块、引用、分割线
- 媒体：图片粘贴 / 拖入 → 自动上传 → 内嵌
- 互动：@ 成员（输入 @ 唤起选人面板，会同时通知）
- 链接：URL、内部任务链接（点击直接跳转）

## 自动保存
- 失焦自动保存
- 实时通过 WebSocket 同步给其他正在看这个任务的人
- 保存生成 ProjectTaskContent 版本，但目前没有 UI 让用户回滚

## 不支持
- 描述不能超过 50000 字符（HTML 含标签算），超长会被截断保存
- 不能直接挂附件，附件走独立的「任务附件」区
- 没有协同光标 / 实时同步光标位置（只是双方各自看到最新版本）
