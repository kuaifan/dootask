---
id: messenger.send.howto.image
title: 发送图片消息
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么发图片
  - 发图
  - 发张图
  - 群里发照片
  - 截图发出去
  - 图片消息
related_tools: [send_message]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 不支持批量打包成相册一次性发出，多张图会逐条以独立消息发送
  - 表情包（emoticon）虽然以 image 形式存储，但不会被识别为附件
  - 图片仅按文件压缩开关执行压缩；发送时不会自动抽取图中文字
last_verified: v1.7.90
---

# 发送图片消息

图片消息（mtype=image）是文件消息的特化类型。后端按扩展名识别为图片（jpg / jpeg / png / gif / webp），自动生成缩略图，并在消息流里以缩略图展示。

## 入口

- 桌面端：输入框左下角「+」→「上传图片」
- 桌面端：直接拖拽 / 粘贴图片
- 桌面端：截图工具直接 Ctrl+V 到输入框
- 移动端：底部「+」→「相册」或「相机」

## 发送方式

1. 单张图片 / 批量图片：均走 sendfile / sendfiles 接口
2. base64 图片（剪贴板截图）：以 image64 参数提交
3. 表情包 / 自定义贴图：以 emoticon 类型发出，单独标记不被当文件附件

## 字段默认值

| 字段 | 默认值 |
|---|---|
| image_attachment | 0（不把图片写入任务附件库）|
| 压缩 | 跟随系统设置 image_compress / image_quality |
| 缩略图 | 自动生成 thumb 字段 |

## 与表情包的区别

- 普通图片：mtype=image，可在「文件」筛选 / 任务附件中复用
- 表情包：mtype=emoticon，仅展示用，不在文件列表出现

## 让 AI 理解图片内容

把图片转发或上传给 AI 助手后，AI 可直接识别图中内容（多模态理解），据此回答、总结或转写其中文字，无需单独的文字提取步骤，需要 AI 插件支持。

## 不支持

- 不支持发送时直接打码 / 涂鸦，需在外部编辑后再发
- 不支持「阅后即焚」模式
