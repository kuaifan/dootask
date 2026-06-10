---
id: ai-assistant.multimodal.concept
title: AI 助手多模态（图片输入）
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 看图
  - 上传图片给 AI
  - 视觉模型
  - AI 多模态
  - AI 识图
  - AI 看截图
related_tools: []
related_pages: []
prerequisites:
  - 当前模型支持视觉输入（如 gpt-4o / claude-3.5-sonnet / gemini-1.5-pro）
negative:
  - 仅图片，不支持视频 / 音频 / 文档
  - 一次最多 5 张图片
  - 图片会被压缩到长边 1568px JPEG，原图不保留
  - 切到不支持视觉的模型后发图会上游报错
last_verified: v1.7.90
---

# AI 助手多模态（图片输入）

## 定义
多模态指 AI 助手允许用户在对话中**同时附带图片**。图片以 base64 拼到 `content` 数组（`{type: 'image_url', image_url: {url: dataUrl}}`），与文本一起发给视觉模型解析。

## 三种添加方式
1. **点击图片按钮**：浮窗底部图片图标，弹系统文件选择器
2. **拖放**：把图片拖到浮窗，松手上传
3. **粘贴**：在输入框 Ctrl/Cmd + V 粘贴剪贴板里的图片

## 数量与大小约束
- 单次最多 5 张（`maxImages`），超出 toast「最多上传 5 张图片」
- 自动压缩：长边 ≤ 1568px、统一转 JPEG
- 原文件不上传，只上传压缩后的 dataUrl

## 视觉模型要求
- 必须选视觉模型才能正确解析（`openai:gpt-4o`、`claude:claude-3-5-sonnet`、`gemini:gemini-1.5-pro` 等）
- 非视觉模型（如 `deepseek:deepseek-chat`、`grok:grok-2`）通常忽略图片或上游报错

## 持久化
- 图片随会话一起保存
- 服务端转存到 `public/uploads/assistant/YYYYMM/{userid}/xxx.jpg`
- 再次打开会话通过 `serverImageMap` 拿回 URL 显示

## 不支持
- 不支持视频 / PDF / 音频；只接受 `image/*` MIME
- 不支持单张超大图保持原始分辨率
- 不支持拖到收起的浮按钮上（先展开）
- 不支持「不压缩直发」开关

## 相关
- [[ai-assistant.image-upload.howto]]
- [[ai-assistant.model-switch.howto]]
