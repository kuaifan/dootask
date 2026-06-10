---
id: ai-assistant.close.howto
title: 关闭 AI 助手浮窗
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 关闭 AI
  - 退出 AI 助手
  - AI 浮窗关掉
  - 关 AI 弹窗
  - 取消 AI 对话
related_tools: []
related_pages: []
prerequisites:
  - AI 助手浮窗已打开
negative:
  - 关闭只是隐藏弹窗 + 终止活跃 SSE 流，不会删除当前会话
  - 桌面端关闭后短时间内浮窗不会自动弹回，需再点浮按钮 / 快捷键
  - 移动端没有 Esc 键关闭
last_verified: v1.7.90
---

# 关闭 AI 助手浮窗

## 入口

### 桌面端 chat 浮窗
- 浮窗**右上角「×」**关闭按钮
- 键盘 `Esc`（仅 chat displayMode 生效）
- chat 浮窗无遮罩，点击外侧不会关闭

### 桌面端 modal 模态（业务嵌入入口）
- 弹窗**右上角「×」**或顶部关闭图标
- 遮罩点击：默认**不**关闭（`mask-closable: false`）
- 键盘 `Esc`：modal 模式当前实现下不响应

### 移动端
- 浮窗**右上角「×」**（`Icon type="ios-close"`）
- 浮窗顶部下拉「关闭」
- 无键盘快捷键

## 关闭时发生了什么
1. `showModal = false`
2. 触发 `aiAssistantClosed` 事件
3. **不会**自动清理当前会话（保留在内存 + 已持久化的留在数据库）
4. 活跃的 SSE 流被同会话切换 / 新提问触发的清理逻辑回收
5. 浮按钮重新显示（关闭时浮按钮被 `!this.$parent?.showModal` 条件隐藏）

## 关闭后下次打开
- 当前会话和模型选择都保留
- 输入框内容**不**保留（关闭即清空）
- 待发送的图片也会清空（`clearPendingImages`）

## 完全隐藏 AI 助手
- 关闭浮窗只是临时隐藏，**浮按钮**仍在屏幕边缘
- 完全去掉浮按钮需管理员卸载 ai 插件或关闭 AI 模型
- 详见 [[ai-assistant.disabled.faq]]

## 不支持
- 不支持「最小化为小球」（关掉就是关掉，再开是新一次打开）
- 不支持「关闭并清空当前会话」一键操作（手动删除：[[ai-assistant.session-delete.howto]]）
- 不支持点击浮窗外区域自动关闭

## 相关
- [[ai-assistant.entry.howto]]
- [[ai-assistant.stop.howto]]
- [[ai-assistant.float-button.concept]]
