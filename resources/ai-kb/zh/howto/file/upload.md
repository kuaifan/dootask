---
id: file.upload.howto
title: 上传文件
type: howto
feature: file
scope: end-user
locale: zh
aliases:
  - 怎么传文件
  - 上传文档
  - 把文件传上去
  - 拖进来
  - 上传到网盘
related_tools: [list_files]
related_pages: [file]
prerequisites:
  - 当前所在文件夹有写权限（自己的文件夹或共享读写权限）
negative:
  - 同一用户在同一目录下并发上传会自动排队（避免数据库死锁），慢但不会丢
  - 单个文件夹直接子项上限 300，超出会报错
  - 不支持上传文件夹后保留文件夹下原有空目录（空目录会被忽略）
  - 跨设备续传不可用：续传索引存浏览器 localStorage，仅本机本浏览器内有效
last_verified: v1.8.45
---

# 上传文件

## 入口
- 桌面端 Web：进入「文件」页 → 任意文件夹内 → 顶部「+」→「上传文件」 / 「上传文件夹」
- 桌面端 Web：直接把文件拖入页面任意位置
- 桌面端 Electron：拖入应用窗口
- 移动端：「文件」页右上角「+」→「上传文件」（受系统相册 / 文件选择器限制）

## 操作步骤
1. 进入目标文件夹（顶部面包屑确认位置）
2. 选择文件或直接拖入（支持多选）
3. 系统自动按扩展名识别类型（word / excel / picture / archive 等），保存到 `uploads/file/<type>/<年月>/<id>/`
4. 若同名同后缀文件已存在，可选「保留两个」（默认）或「覆盖」（cover 参数 1）

## 上传文件夹
- 拖入整个文件夹时，系统会自动按 `webkitRelativePath` 重建文件夹结构
- 路径中每一级文件夹会自动创建（已存在则复用）
- 空目录会被忽略（只创建有文件的层级）

## 字段默认值
- 拥有者（userid）：当前文件夹的拥有者（避免共享目录混乱）
- 父目录：当前所在文件夹
- 版本：上传产生一条新的 FileContent 记录（详见 [[file.version.concept]]）

## 大文件 / 断点续传 / 秒传
- **≥10MB 自动分片**：文件切成 5MB 分片，3 路并发上传；前端先在 Web Worker 算 md5（不卡 UI）
- **断点续传**：上传中刷新页面或断网后，重新选同一文件会跳过已上传分片，从中断处继续
- **秒传**：同用户已上传过同 hash 文件（命中 `files.hash`），目标文件夹立刻出现新记录，零传输
- 状态保存：服务端 Redis `upload:*` key TTL 24h；浏览器 localStorage `chunked_upload:<hash>` 索引同样 24h
- 残留磁盘自动清理：`uploads/tmp/chunks/{userid}/{upload_id}/` 超过 24h 由 `DeleteTmpTask` 兜底清除

## 不支持
- 无法向直接子项 ≥ 300 的文件夹继续上传（会被拒绝）
- 跨设备 / 跨浏览器续传（localStorage 索引仅本机有效；服务端按 hash+userid 反查可兜底）
- 不支持移动端后台续传（应用切到后台可能中断）
