---
id: ai-assistant.modal.concept
title: AI 助手弹窗形态
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 浮窗
  - AI 弹窗
  - AI 全屏
  - AI 窗口
  - AI 模态框
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 移动端浮窗一律全屏，不能调整大小
  - 桌面端浮窗最小 380×400，最大 800×900
  - 弹窗 z-index 每 5/20 秒自动调高确保覆盖其他模态
last_verified: v1.7.90
---

# AI 助手弹窗形态

## 定义
AI 助手有两种渲染形态，由打开时的 `displayMode` 决定：
- `chat`：**浮窗**（默认，可拖动 / 调整大小 / 桌面右下角）
- `modal`：**居中模态**（默认 600px 宽，移动端自动全屏）

## chat 浮窗（桌面端默认）
- 通过 `v-transfer-dom` 挂到 `<body>`，避免被父容器裁切
- 自带 8 个 resize 控制点（四边 + 四角），可拖拽改大小
- 标题栏可拖动整窗，双击切换全屏
- 位置 / 尺寸记忆到 IndexedDB（`aiAssistant.chatPosition` / `aiAssistant.chatSize`）

## 尺寸约束（chat 浮窗）
| 维度 | 最小 | 最大 | 默认 |
|---|---|---|---|
| 宽度 | 380 | 800 | 460 |
| 高度 | 400 | 900 | 600 |

## modal 模态（业务嵌入入口默认）
- iView `Modal` 组件渲染，居中遮罩
- 宽度：新建会话 440，已有对话 600
- 桌面端遮罩点击**不**关闭（`mask-closable: false`）
- 移动端强制全屏

## 移动端
- 不论 displayMode 是什么都全屏
- 顶部有标题栏、关闭、历史下拉
- 关闭按钮 `Icon type="ios-close"` 位于右上
- 输入区固定在底部

## z-index 自适应
- 初始 `topZIndex = max(modalTransferIndex, 1000) + 1000`
- 弹窗打开期间每 5 秒重新评估（高于新模态）
- 关闭后每 20 秒评估

## 不支持
- 不支持最小化为小球（要小球用浮按钮）
- 不支持多窗口同时打开多个对话
- 不支持自定义 z-index

## 相关
- [[ai-assistant.float-button.concept]]
- [[ai-assistant.close.howto]]
- [[ai-assistant.mobile-entry.concept]]
