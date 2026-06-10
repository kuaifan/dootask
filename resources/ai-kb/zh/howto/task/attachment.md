---
id: task.attachment.howto
title: 任务附件上传与下载
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 任务附件
  - 上传文件到任务
  - 任务怎么发文件
  - 给任务加附件
  - 任务里下载文件
related_tools: [get_task_files]
related_pages: [task_detail]
prerequisites:
  - 任务已保存（创建中不能直接传）
  - 是任务可见用户，传文件还需 TASK_UPDATE 权限
negative:
  - 子任务（parent_id > 0）不能独立上传附件
  - 任务复制不会带走附件
  - 单文件大小受站点设置限制
last_verified: v1.7.90
---

# 任务附件上传与下载

## 是什么
DooTask 任务附件保存在 `ProjectTaskFile` 表，每个附件记录文件名、大小、扩展名、宿主路径、缩略图、上传人。所有任务可见用户可下载，TASK_UPDATE 权限的人可上传 / 删除。

## 入口
- 桌面端：任务详情页 → 「附件」区 → 「+ 上传」或直接把文件拖入页面
- 桌面端：在描述富文本里粘贴图片自动上传，会同时进描述内联图与附件区
- 移动端：详情页底部「附件」按钮 → 选相册 / 文件 / 拍照

## 上传方式
1. 点 + 号或拖入文件
2. 进度条显示，结束后自动生成缩略图（仅图片）
3. 文件名同名时会自动加 (1) (2) 后缀，不会覆盖

## 下载方式
- 点附件项右侧下载图标，浏览器直接下载
- 图片附件可点缩略图先预览，再下载
- 部分插件（office、fileview）能在线预览，详见 [[application.center.concept]]

## 删除
- 上传人或 TASK_UPDATE 权限的人可删除
- 删除是硬删（不进回收站）
- 删除会触发项目动态记录

## 不支持
- 子任务不能直接传附件，需挂到父任务
- 复制任务（[[task.copy.howto]]）不带走附件
- 任务删除后附件不会自动清理，物理文件仍在磁盘
