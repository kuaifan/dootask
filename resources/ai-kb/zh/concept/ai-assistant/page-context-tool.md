---
id: ai-assistant.page-context-tool.concept
title: AI 怎么知道你在哪个页面
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 看页面
  - 页面上下文
  - AI 怎么知道当前页
  - AI 读取页面
  - AI 上下文采集
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 仅当用户在 AI 浮窗当前会话所在的浏览器/桌面端窗口时才能采集
  - 不能跨标签 / 跨设备同步采集，每个 socket 只对应一个页面
  - 不读取密码框/被遮挡元素/隐藏元素
  - 跨源（外部站点）微应用 iframe 的内部无法采集，会提示改用数据命令
last_verified: v1.7.90
---

# AI 怎么知道你在哪个页面

## 定义
当你在 AI 浮窗里问"这页有什么操作""帮我点这页的某个按钮"时，AI 助手会向你当前的浏览器/桌面端页面请求页面上下文，包括：当前路由名（如 `manage-project`）、URL、标题、可交互元素清单（带 ref / name / role）、该页可用的高层动作。结果由前端 `page-context-collector.js` 实时收集后回传给 AI。

## 返回字段
- `page_type`：路由名（如 `manage-task`）
- `page_url` / `page_title`
- `elements`：可交互元素（ref / name / role）
- `total_count` / `has_more` / `offset`：分页
- `available_actions`：该页可用的高层动作（如项目页可 `open_task`）
- `ref_map`：ref → 定位信息
- `frame`：本次采集所在上下文——`scope`（`main` 主界面 / `app` 微应用）、`app_name`（微应用时的应用名）、`operable`（是否可操作）

## 调用模式
- **轻量**：`interactive_only=true` + `max_elements=20`
- **完整**：默认前 100 个（含内容）
- **搜索**：传 `query` 先关键词后向量匹配
- **采集范围**：默认采集"用户最前面看到的"页面——有同源微应用插件在最前打开时采集其 iframe 内部，否则采集主界面；也可指定只采主界面。跨源微应用读不到内部，会返回 `operable:false` 并提示降级。

## 隐式触发
用户在浮窗里问"这个页面有什么操作"、"帮我点这页的某按钮"、"切到下一项目"时，AI 都会先采集当前页面上下文再决定下一步。

## 不支持
- 不返回每个元素的位置坐标（仅 selector）
- 不会返回 input 框的当前值（不读取用户私有输入）
- 不能在弹窗/抽屉之外拿到全屏快照（仅 DOM 节点）

## 相关
- 元素匹配：[[ai-assistant.match-elements.concept]]
- 操作元素：[[ai-assistant.element-action.howto]]
- 页面操作机制：[[ai-assistant.page-action.concept]]
