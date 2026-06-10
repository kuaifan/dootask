---
id: common-faq.upload-size-limit.faq
title: 文件超大上传失败
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 文件太大
  - 文件超过限制
  - 上传失败 413
  - 文件大小超限
  - 大文件传不上
  - 超出文件大小限制
related_tools: []
related_pages: [file_upload, dialog_send_file]
prerequisites: []
negative:
  - 单个上传请求受 PHP / Nginx 双层限制，前端的「最大尺寸」提示只是友好封装
  - 没有「拆包重传」按钮，超大文件需要事先分块或直接上传到外部存储
  - 同一文件多次重传不会绕过大小限制
last_verified: v1.7.90
---

# 文件超大上传失败

## 问题
拖文件或选文件后立即报「超出文件大小限制」「文件过大」「413 Request Entity Too Large」，或上传到 99% 突然失败。

## 原因
DooTask 文件上传受三层限制：

- **PHP**：`upload_max_filesize` 默认 1024M（容器内 `docker/php/php.ini`）
- **Nginx**：`client_max_body_size`（一般和 PHP 对齐）
- **场景层**：聊天图片、头像、富文本图片各自有更小的尺寸上限（如头像通常 2M，富文本图片 10M）

任何一层超出都会拒收。出错信息以最先拒绝的那层为准——浏览器层先校验则提示「超出文件大小限制」；后端先校验则提示 413。

## 解决
1. 直接拖文件到「文件中心」走文件上传流程，限制最大（默认 1024M）
2. 大于上限时拆分：用 zip 分卷、或上传到云盘后分享外链
3. 聊天里发大文件不要走「图片」入口（小限），用「发送文件」按钮（大限）
4. 管理员可调高限制：改 `docker/php/php.ini` 的 `upload_max_filesize` 和 `post_max_size`，同时改 nginx 的 `client_max_body_size`，重启容器生效

## 不支持
- 客户端不会自动分片重传超过 `post_max_size` 的文件
- 主程序未内置 chunked upload / 断点续传 API（除部分插件场景）
- 修改 php.ini 后必须容器重启，热加载不生效

[[file.upload.howto]] / [[system-setting.file.howto]] 给管理员看
