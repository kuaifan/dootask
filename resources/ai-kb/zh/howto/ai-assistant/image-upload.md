---
id: ai-assistant.image-upload.howto
title: 给 AI 助手发图片
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 上传图片
  - 给 AI 发截图
  - AI 拍照分析
  - 粘贴图片给 AI
  - 拖图片给 AI
  - AI 看图
related_tools: []
related_pages: []
prerequisites:
  - 已选中支持视觉的模型（如 gpt-4o / claude-3.5-sonnet / gemini-1.5-pro）
negative:
  - 单次最多 5 张，超出会拒绝
  - 仅支持图片格式（image/*），不支持 PDF / 视频
  - 图片会被前端压缩到长边 1568px JPEG，原图不保留
  - 非视觉模型即使选了也会被上游报错或忽略图片
last_verified: v1.7.90
---

# 给 AI 助手发图片

## 三种上传方式

### 1. 点击图片按钮
1. 打开 AI 助手浮窗
2. 浮窗底部输入区**左侧**有图片图标
3. 点击 → 弹系统文件选择器
4. 选 1-5 张图片 → 确定

### 2. 拖放
1. 把图片从桌面或文件管理器拖到 AI 浮窗
2. 浮窗内部出现「松开以上传图片」遮罩
3. 在遮罩内松开鼠标完成上传

### 3. 粘贴（Ctrl/Cmd + V）
1. 截图（Mac Cmd+Shift+4、Win Snipping Tool 等）
2. 焦点放在 AI 输入框
3. 按 Ctrl/Cmd + V，剪贴板的图片直接进入预览区

## 数量与压缩规则
- 最多 5 张并存；超出 toast「最多上传 5 张图片」
- 自动压缩：长边 ≤ 1568px、统一转 JPEG
- 节省 token：上游按图片像素 / 大小计费

## 预览与移除
- 上传后缩略图出现在输入框上方
- 点缩略图右上角「×」单张移除
- 发送后图片自动从「待发送」区清空

## 发送行为
- 文本 + 图片**同时**作为多模态 content 发送
- 历史会话里图片用占位符 `[image:imageId]` 存到 `data`，实际 base64 入 `images`
- 再次打开历史会话通过 `serverImageMap` 拿 URL 渲染

## 不支持
- 不支持视频 / PDF / 音频
- 不支持把图片拖到收起的浮按钮上（先展开浮窗）
- 不支持「不压缩直发」开关

## 相关
- [[ai-assistant.multimodal.concept]]
- [[ai-assistant.model-switch.howto]]
