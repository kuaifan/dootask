---
id: common-faq.upload-disk-full.faq
title: 服务器存储满
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 磁盘满
  - 存储空间不足
  - 服务器满了
  - No space left
  - 文件写不进
  - 上传失败磁盘
related_tools: []
related_pages: []
prerequisites:
  - 需要服务器 shell 权限
negative:
  - DooTask 主程序无「按用户配额」机制，任何用户都可用满全部存储
  - 系统不会自动清理已删除文件——回收站 30 天后真删才释放空间
  - 没有 web 界面查询「谁占用最多」，需要在服务器命令行统计
last_verified: v1.7.90
---

# 服务器存储满

## 问题
所有用户都报上传失败，错误日志包含 `No space left on device`、`disk full`、`ENOSPC`。聊天图片发不出，文件中心打不开新文件。

## 原因
DooTask 把所有上传写到容器内的 `public/uploads/`（默认通过宿主机 bind mount 挂出），同时 MySQL data、Redis dump、容器层日志、AI 插件缓存也占空间。当任一卷写满，PHP/Swoole 立即写失败。

常见膨胀源：
- 文件中心累积多年的大附件
- 聊天图片 + 视频未清理
- Office / 白板的历史版本（每改一次留一份）
- 容器日志：`docker logs` 长期不轮转
- MySQL 慢日志 / binlog 未限制

## 解决
1. `df -h` 看是宿主机根盘满还是 DooTask 数据盘满
2. `du -sh /path/to/dootask/public/uploads/* | sort -h` 找最大目录
3. 临时清理：
   - `public/uploads/tmp/` 可放心清空（缓存）
   - `docker system prune -af --volumes`（注意：会清未挂的镜像/卷）
   - 截断容器日志：`truncate -s 0 $(docker inspect --format '{{.LogPath}}' <container>)`
4. 长期：
   - 进管理后台「数据导出 / 文件中心」清理超大老文件
   - 系统设置「文件设置」限制打包下载（[[system-setting.file.howto]]）
   - 给 docker 配 `log-opts max-size`，给 MySQL 配 binlog 轮转

## 不支持
- DooTask 没有按用户配额功能
- 无内置「自动清理 N 天前」策略；要做需写定时脚本
- 写满时已收到的消息和任务不会丢，只是新上传暂时失败；恢复空间后立即可写

清理后无需重启容器，PHP 进程会在下一次请求自动恢复。
