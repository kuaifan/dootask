---
id: ai-assistant.page-action.concept
title: AI 操作页面的机制
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 控制页面
  - AI 自动操作
  - AI 怎么帮我操作页面
  - AI 跳转页面
  - 页面自动化
  - AI 点按钮
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - AI 的页面操作仅在浏览器/桌面端会话窗口内生效，无法控制其他用户的页面
  - 一次只能操作当前会话所在的页面，不能开新标签页
  - 关闭浏览器或切到别的标签页时，页面操作会断连失败
  - 跨源（外部站点）微应用 iframe 的内部不可操作，仅同源微应用插件可
last_verified: v1.7.91
---

# AI 操作页面的机制

## 定义
AI 助手通过高层导航和低层元素操作两类能力操作用户当前页面。主程序常驻 WebSocket（`/ws`）把指令派发给前端，前端的 `action-executor.js` 执行真实 DOM 行为或路由跳转，结果回传给 AI 让对话继续。这类页面操作不是 MCP 工具，由 AI 助手在你的页面上执行。

## 两层能力
- **高层导航**：语义化命名（如 `open_task`、`navigate_to_dashboard`），参数明确（任务 ID），由前端封装好 router 调用；优先用这层，稳定不易错
- **低层元素操作**：基于元素 ref 的通用动作（click/type/select/focus/scroll/hover），用于没封装好的细节操作

## 受支持的高层动作
- `open_task`、`open_dialog`、`open_project`、`open_file`、`open_folder`
- `navigate_to_dashboard / messenger / calendar / files`
- `close_app`：关闭当前打开的应用窗口（仅在有微应用打开时出现）。属外壳层动作，哪怕 AI 正停在该应用内部、甚至应用跨源读不到内部，也能直接关闭

## 受支持的低层元素动作
- `click`、`type`、`select`、`focus`、`scroll`、`hover`

## 微应用内部
当你打开了微应用插件（应用市场安装、反代到主站 `/apps/` 同源路径、以 iframe 呈现）并停在最前时，采集页面上下文与低层元素操作会**默认作用于该微应用内部**，可像操作主界面一样点按钮 / 填表 / 切菜单。多个微应用同时打开时只操作最前面那个；切换或关闭应用后，原先拿到的元素引用会失效，AI 会被提示重新获取。跨源（指向外部站点）的微应用读不到内部，AI 会提示改用数据命令或回到主界面。

## 不支持
- 不能模拟键盘组合键、不能拖拽
- 不能操作跨源（外部站点）微应用 iframe 的内部
- 不能跳转外部 URL（goForward 只走应用内路由）
- 不能伪造非用户主动触发的事件（如自动提交表单审批通过）

## 相关
- 让 AI 跳页面：[[ai-assistant.page-action.howto]]
- AI 操作元素：[[ai-assistant.element-action.howto]]
- 元素查找接口：[[ai-assistant.match-elements.concept]]
- 取页面上下文：[[ai-assistant.page-context-tool.concept]]
