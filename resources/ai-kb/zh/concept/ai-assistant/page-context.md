---
id: ai-assistant.page-context.concept
title: AI 助手页面上下文（弱提示词）
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 知道当前页面
  - 页面感知
  - AI 上下文
  - 弱提示词
  - 当前页面注入
  - AI 现在在哪
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 弱提示词只传「页面类型 / 实体 id / 名称 / 对话类型」四类，不传业务详细数据
  - 详细数据需要 AI 通过 MCP 工具自取
  - 嵌入入口（专用 onBeforeSend）不走弱提示词机制
last_verified: v1.7.90
---

# AI 助手页面上下文（弱提示词）

## 定义
**弱提示词**是 AI 助手浮窗对话时自动注入的极简「我现在在哪」描述，让 AI 知道用户提问发生在哪个项目 / 任务 / 对话页，能正确解析「这个项目 / 这条任务」之类指代。

## 注入格式
渲染为一行 `system` 消息穿插到 context 历史：

```
[当前页面] 项目详情页(project_id=123,名称:DooTask)
[页面切换] 任务详情页(task_id=4567,名称:修复 SSE 断流)
```

前缀：`[当前页面]` 首次注入，`[页面切换]` 后续在不同页面再提问时。

## 注入的字段
仅四类，绝不带其他业务数据：

| 字段 | 含义 |
|---|---|
| `type` | 实体类型（task / dialog / project / file / report） |
| `id` | 实体 id |
| `name` | 实体名称 |
| `dialogType` | 群聊 / 私聊，仅 dialog 有 |

## 为什么穿插历史
用户可能在不同项目 / 任务页连续提问。只放当前页会让历史里两个「这个项目」失去锚点。穿插能给历史每条用户消息打上「问题发生时所在的页面」，AI 才能正确分辨。

## 可识别的页面
- 任务详情弹窗（最高优先）→ `task:{id}`
- 对话详情弹窗 → `dialog:{id}`
- 仪表盘 → `dashboard`
- 项目列表 / 详情 → `project-list` / `project:{id}`
- 消息列表 / 会话 → `messenger` / `dialog:{id}`
- 日历 → `calendar`
- 文件列表 / 详情 → `file-list` / `file:{id}`
- 工作汇报编辑 / 详情 → `report:{id}`

## 何时不注入
- 不可识别路由（登录页、个人设置等）
- 同 `contextKey` 连续提问
- 嵌入入口（带 `onBeforeSend`）

## 详细数据怎么拿
弱提示词只告「在哪」，不告「内容」。AI 需要看任务描述、项目统计时通过 MCP 工具自取（如 `get_task`、`get_project_data`）。

## 不支持
- 不支持把整页内容塞给 AI
- 不支持用户手动关闭弱提示词
- 不支持自定义注入字段

## 相关
- [[ai-assistant.embed-entry.concept]]
- [[ai-assistant.welcome-prompts.concept]]
