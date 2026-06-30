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
  - ≥10MB 文件已自动走分片上传，不需要手动拆包；超过系统设置上限时由应用层拒绝
  - 同一 hash 文件可秒传命中，但仍受系统设置的"单文件上限"约束
last_verified: v1.8.45
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
1. 默认 1G 内的文件应直接成功；≥10MB 自动走分片上传（聊天/文件柜/任务附件/编辑器/头像统一）
2. 同一 hash 文件秒传命中：上传过的视频再次发，瞬间出现在目标位置，零等待
3. 中途断网或刷新页面，重新选同一文件会跳过已上传分片继续传
4. 管理员需要更大上限：管理后台「系统设置」→「文件上传限制」填具体 MB 数（如 `5120`=5G），即可突破默认 1G
5. 仅当填的值超过 PHP/Nginx/Swoole 底层上限时才需要改 `docker/php/php.ini` 等部署配置（默认底层均为 1G，因为单分片只有 5MB 所以分片路径不受底层约束）

## 不支持
- 跨设备 / 跨浏览器续传（续传索引保存在 localStorage，仅本机本浏览器内有效）
- 完全无认证场景下分片（必须登录）
- 修改 php.ini 后必须容器重启，热加载不生效

[[file.upload.howto]] / [[system-setting.file.howto]] 给管理员看
