---
id: ai-assistant.element-action.howto
title: 让 AI 操作页面元素
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 点按钮
  - AI 帮我输入
  - AI 选下拉
  - AI 滚动页面
  - AI 自动填表
  - AI 点击 X
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
  - 当前页面已加载完成
negative:
  - 元素必须可见且未被遮挡才能点击，被滚出视野时需先 scroll
  - 不能模拟键盘组合键（如 Cmd+S）、不能拖拽
  - AI 找不到匹配元素时会道歉并要求换种描述，不会盲点
last_verified: v1.7.90
---

# 让 AI 操作页面元素

## 这是什么
让 AI 助手在你当前页面上直接操作具体元素，包括点击按钮、输入文本、选下拉项、聚焦、滚动、悬停。常用于完成详细表单或触发某个隐藏在多级菜单里的功能。操作由 AI 助手在你的浏览器/桌面端页面上执行。打开了微应用插件（同源）时，这些操作会默认作用于最前那个微应用的内部。

## 怎么问
- "点击『保存』按钮"
- "在标题框输入『官网首页改版』"
- "把优先级下拉选成『高』"
- "滚动到页面底部"
- "悬停在第一个项目卡片上"

## AI 的执行链路
1. 先采集当前页面的可交互元素清单
2. 用 `match_elements` 接口按描述（"保存按钮"、"标题输入框"）找到目标 ref
3. 在你的页面上触发 click / type / select / focus / scroll / hover

## 支持的动作
| 动作 | 说明 |
|---|---|
| click | 触发原生 click 事件 |
| type | 设置 input / textarea / contentEditable 的值并触发 input/change 事件 |
| select | 原生 select 或 iView 下拉，按选项文本匹配 |
| focus | 聚焦元素 |
| scroll | 平滑滚动到屏幕中央 |
| hover | 模拟 mouseenter/mouseover |

## 不支持的动作
- 不能模拟键盘按键、不能模拟组合键
- 不能拖拽元素（drag/drop）
- 不能操作跨源（外部站点）微应用 iframe 的内部元素（同源微应用插件内部可操作）
- 不能等到某个异步加载完成再点（无 wait 机制，需用户重新触发）

## 找不到元素怎么办
- 改用更具体的描述（"右上角保存按钮"代替"保存")
- 先让 AI 跳到元素所在页（`open_task` 等）
- 元素在折叠面板里，先让 AI 展开

## 相关
- 跳页面 / 打开任务：[[ai-assistant.page-action.howto]]
- match_elements 接口：[[ai-assistant.match-elements.concept]]
- 页面上下文：[[ai-assistant.page-context-tool.concept]]
