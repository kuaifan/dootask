---
id: system-setting.file-upload-limit.howto
title: 单文件上传大小限制
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 上传文件大小限制
  - 文件上传限制怎么改
  - file_upload_limit
  - 文件最大多大
  - 改上传上限
  - 默认不限制
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 该限制只控制单个文件大小，不是磁盘配额或总量上限
  - 留空 = 不限制；后端仍受 PHP / Nginx 层 client_max_body_size 等服务级限制
  - 不能按用户 / 部门设差异化阈值，全局生效
last_verified: v1.7.90
---

# 单文件上传大小限制

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「其他设置」→「文件上传限制」。

字段名：`file_upload_limit`，整数，单位 **MB**，默认留空（= 不限制）。

## 生效范围
后端 `Base::uploadFile` 在每次接收上传时都会读取该值，作用于：

- **聊天消息中发送的文件 / 图片附件**
- **任务详情里的附件上传**
- **「文件」应用中的文档上传**
- 各种自定义上传入口（凡是走 `Base::uploadFile` 的接口）

逻辑：调用方未显式传 `size` 参数时，取 `file_upload_limit * 1024 KB` 作为单文件上限。超过则报错 `文件大小超限，最大限制：N KB`。

## 字段默认值

| 字段 | 默认 | 单位 |
|---|---|---|
| `file_upload_limit` | 空（不限制） | MB |

## 操作步骤
1. 进入「系统设置」→「系统设置」→「其他设置」
2. 在「文件上传限制」输入框填正整数（如 `100` = 100MB）或留空
3. 「提交」保存，立即生效

## 不支持
- 不区分图片 / 视频 / 文档：所有类型走同一阈值
- 不能按文件后缀黑白名单（黑白名单走「文件设置」标签，不在这里）
- 留空只是"应用层不限制"，仍受 Nginx / PHP 等 web 服务器层面的上传上限制约
