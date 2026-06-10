---
id: common-faq.upload-stuck.faq
title: 上传卡住进度不动
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 上传卡住
  - 进度条不动
  - 一直转圈
  - 上传超时
  - 上传中断
  - 99% 卡死
related_tools: []
related_pages: [file_upload]
prerequisites: []
negative:
  - 没有「断点续传」按钮，卡住后只能重新上传整个文件
  - 服务端任务排队不可视化（同一用户同目录并发会排队，看着像卡住但其实在等）
  - 关浏览器标签会立刻中断当前上传
last_verified: v1.7.90
---

# 上传卡住进度不动

## 问题
拖了文件后进度条停在某个百分比不动，或者一直「上传中…」、转圈，最终超时或自动失败。

## 原因
DooTask 上传卡顿常见于：

1. **网络抖动 / VPN**：上行带宽不稳，TCP 重传拖累整个请求
2. **同一用户同目录并发**：后端用 atomic lock 排队避免数据库死锁，前一个文件没传完后一个等着
3. **反向代理超时**：Nginx 的 `proxy_read_timeout`、`client_body_timeout` 默认值偏小，超大文件传到尾段断开
4. **磁盘满 / inode 用尽**：服务端写不进文件
5. **PHP 进程超 max_execution_time**：CLI / FPM 限制了脚本执行时间

## 解决
1. 先刷新页面、关掉 VPN，换稳定网络重试小文件验证链路
2. 多文件同目录上传 → 改为分目录或串行（一次传一个），避免锁排队
3. 上传到 99% 失败大多是反向代理超时 → 让管理员调大 nginx 的 `proxy_read_timeout 600s`、`client_body_timeout 600s`
4. 服务端 `df -h` 查容量；`df -i` 查 inode；满了清 `public/uploads/tmp` 缓存
5. 改 `docker/php/php.ini` 的 `max_execution_time` 和 `max_input_time` 后重启 PHP 容器

## 不支持
- 客户端不做断点续传，卡断后必须重头传
- 浏览器关闭即上传中断，不会保留到后台继续

如果是聊天发文件卡住：先关掉所有 dootask 标签再重开，会清空前端待发队列。
