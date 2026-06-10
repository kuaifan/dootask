---
id: system-setting.video-process.howto
title: 视频转码与压缩设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 视频转换怎么开
  - 视频压缩开关
  - convert_video
  - compress_video
  - MOV 转 MP4
  - 视频自动压缩
prerequisites:
  - 需要系统管理员权限
  - 服务器已具备 ffmpeg 等转码工具（容器化部署默认包含）
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 不会回溯处理历史上传的视频，只对新上传文件生效
  - 转码 / 压缩失败时回退保留原文件，不会丢消息
  - 不能选目标分辨率 / 码率，参数固定
last_verified: v1.7.90
---

# 视频转码与压缩设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「消息相关」→「视频转换」/「视频压缩」。

涉及字段：
- `convert_video` — 视频转码开关，默认 `close`
- `compress_video` — 视频压缩开关，默认 `close`

## 各自作用

| 字段 | 作用 | 触发条件 |
|---|---|---|
| `convert_video` | 把 **MOV、WEBM** 格式视频转成 **MP4**，提升跨端兼容性 | 上传的视频后缀为 mov 或 webm |
| `compress_video` | 对 **MP4** 视频做压缩，减小体积 | 上传 / 转码后产物为 MP4 |

两者可独立开关，组合关系：
- 只开 `convert_video`：MOV/WEBM → MP4（原始码率，体积不变）
- 只开 `compress_video`：MP4 直接压缩；MOV/WEBM 不动
- 都开：MOV/WEBM 先转 MP4 再压缩
- 都关：原文件直接保存，不做任何处理

## 开启代价
- 服务端需要 ffmpeg；转码会占 CPU 与临时磁盘
- 用户上传后会有一段"处理中"的等待时间，处理完才能预览或发送

## 对原视频影响
原文件被处理后产物（转码后 MP4 / 压缩后 MP4）替代原文件入库，**原始 MOV / WEBM 不保留**。开关推送给前端后会在聊天预览处显示「视频处理中」气泡。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「消息相关」
2. 「视频转换」「视频压缩」分别选「开启」或「关闭」
3. 「提交」保存，立即对后续上传生效

## 不支持
- 不能按文件大小阈值触发（小视频也会被压）
- 不支持自定义分辨率 / 码率 / 编码器
- 不能针对单个会话或单条消息关闭
