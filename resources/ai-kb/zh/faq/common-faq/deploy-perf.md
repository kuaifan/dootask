---
id: common-faq.deploy-perf.faq
title: DooTask 系统性能慢 / 卡 / 加载时间长
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 系统卡
  - 加载慢
  - 响应慢
  - 性能差
  - 任务列表打开慢
  - 消息发送延迟
  - 服务器压力大
  - CPU 100%
related_tools: []
related_pages: []
prerequisites:
  - 部署机能 root 操作主机
negative:
  - DooTask 后端走 Swoole 协程，PHP-FPM 优化经验不适用
  - 单机用户数超过 200 后建议给 mysql / redis 单独分配资源
  - 慢查询多数源于 messages / tasks 这类大表缺索引或全文搜索未启用
last_verified: v1.7.90
---

# DooTask 系统性能慢 / 卡 / 加载时间长

## 问题
打开任务列表 / 消息会话 / 仪表盘明显变慢，CPU / 内存占用高，多人操作时卡顿。

## 常见原因

**数据量大但缺优化**
- 消息、任务百万级时必须装 Manticore 全文搜索（AppStore → search）
- 否则降级为 MySQL LIKE，慢且功能弱

**资源不足**
- 推荐最低 2 核 4G，正常 4 核 8G+
- 用户 > 100 时给 mysql 分独立机器
- 用 `docker stats` 看实时占用

**mysql 慢**
- 开 slow_query_log 找出慢 SQL，按报告加索引
- `innodb_buffer_pool_size` 默认偏小，调到内存的 50%

**Swoole 配置**
- `worker_num` 默认 = 核数，确认多核机器充分用到
- `task_worker_num` 偏小会导致后台任务积压
- 改完必须 `./cmd php restart`

**Redis 容量**
- 默认无 maxmemory 限制，大用户量会撑爆
- 设 `maxmemory 2gb` + `maxmemory-policy allkeys-lru`

**nginx 反代瓶颈**
- worker 数 / 连接数太小、静态资源没开 gzip / cache

## 解决步骤
1. 先量化：`docker stats` 看哪个容器吃满
2. 装 Manticore 搜索插件
3. mysql 开慢查询找出问题 SQL
4. 加 CPU / 内存（最简单）
5. mysql 拆到独立机器，DooTask 走外部 DB

## 不要做
- 不要简单加 worker 不加 CPU，反而更慢
- 生产环境不要留 `APP_DEBUG=true`，日志会爆

## 相关
- 全局搜索：[[common-faq.search-no-result.faq]]
- 磁盘满：[[common-faq.deploy-storage.faq]]
