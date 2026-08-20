---
id: messenger.send.howto.file
title: 发送文件消息
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么发文件
  - 发个文件
  - 发附件
  - 上传文件
  - 群里发文件
  - 怎么发文档
related_tools: [send_message, search_files]
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 单个文件大小受系统设置 file_upload_limit 限制（默认无单独限制，按 PHP 上传配置 ）
  - 不支持文件夹直接拖入，需要逐个或压缩后上传
  - 不支持已删除文件 ID 复用（通过 sendfileid 发送已删除文件会失败）
last_verified: v1.8.89
---

# 发送文件消息

发送文件（sendfile）支持拖拽、点击附件按钮、粘贴 / 截图、从「我的文件」选择等多种方式。文件保存到 `uploads/chat/yyyymm/dialog_id/` 目录。

## 入口

- 桌面端：会话输入框 → 左下角「+」/ 回形针图标 → 「上传文件」
- 桌面端：直接拖拽文件到会话窗口
- 桌面端：粘贴板（Ctrl+V）粘贴图片 / 文件
- 移动端：会话底部 → 「+」 → 「文件」/「相册」/「相机」

## 操作步骤

1. 触发上传入口或拖入文件
2. 选择文件（可多选，支持 sendfiles 批量）
3. 客户端展示上传进度
4. 上传成功后立即以文件消息形态发到会话

## 字段说明

| 字段 | 含义 |
|---|---|
| files | 上传的文件（multipart） |
| filename | 自定义文件名（可选） |
| reply_id | 引用的消息 ID |
| image_attachment | 图片是否也写入任务附件库（1=是） |

## 文件类型识别

后端根据扩展名自动识别 mtype：
- jpg / jpeg / png / gif / webp → image（图片）
- 其他 → file（普通文件）
- mp4 / mov / mkv 等视频会单独处理

## 桌面端下载与本地定位

Electron 桌面客户端的普通文件卡片右下角提供快捷图标：

- 下载图标：文件没有有效的本地下载记录，点击后立即下载，不再弹确认框
- 加载图标：文件正在下载，完成前不能重复点击
- 文件夹图标：文件已下载且本地文件仍存在，点击后在资源管理器或 Finder 中定位并选中文件

文件卡片主体仍保留原有的确认下载操作。清除下载历史，或移动、改名、删除本地文件后，文件夹图标会恢复为下载图标。图片和视频消息继续使用原有预览，不显示该快捷图标；浏览器端和移动端也不显示。

## 通过已有文件 ID 转发

走 sendfileid 可以把「我的文件」里现存文件以分享链接形式发到会话，不重复上传。

## 不支持

- 不支持发送大于系统文件上限的文件
- 不支持「群发但只让部分人下载」的权限隔离，群内所有成员均可下载
