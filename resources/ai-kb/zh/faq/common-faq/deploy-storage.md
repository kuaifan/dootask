---
id: common-faq.deploy-storage.faq
title: 磁盘占满 / DooTask 数据目录变大怎么办
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 磁盘满了
  - 数据目录太大
  - public/uploads 太大
  - mysql 备份占空间
  - 日志文件过大
  - DooTask 越用越大
  - 清理空间
related_tools: []
related_pages: []
prerequisites:
  - 部署机能 root 操作主机文件系统
negative:
  - 不要手动 rm public/uploads/ 下的文件，会导致已上传附件 404
  - 不要清空数据库表，应通过任务回收站、消息删除等业务操作清理
  - mysql 日志（binlog）不在自动备份里，需另行处理
last_verified: v1.7.90
---

# 磁盘占满 / DooTask 数据目录变大怎么办

## 问题
服务器磁盘越来越满，DooTask 部署目录占了大头，想清理释放空间。

## 主要占用点
按通常大小排序：

| 目录 | 内容 | 能否清理 |
|---|---|---|
| `public/uploads/` | 附件、头像 | 不能直接删，走业务回收站 |
| `docker/mysql/data/` | 数据库 | 不能直接删 |
| `docker/mysql/backup/` | 数据库自动备份 | 可以，保留近几份 |
| `storage/logs/`、`docker/log/` | Laravel/nginx/php 日志 | 可以，按日轮转 |
| `public/uploads/tmp/` | 上传临时文件 | 可以，定时清理 |

## 解决

**清理 mysql 自动备份（保留 7 天）**
```bash
find ./docker/mysql/backup -name "*.sql.gz" -mtime +7 -delete
```

**清理临时上传、日志**
```bash
find ./public/uploads/tmp -mtime +1 -delete
find ./storage/logs ./docker/log -name "*.log" -mtime +30 -delete
```

**业务侧清理**
- 任务 / 文件回收站：超 30 天自动清理（不可恢复）
- 大附件：找到大文件先归档外存

**数据库瘦身（慎用）**
- 老旧消息归档（先备份）
- 删除离职用户的设备记录、session

## 不要做
- 不要 `rm -rf public/uploads/*`，所有附件会 404
- 不要直接清空 `users` 等核心表，依赖关系会断

## 相关
- 备份：[[common-faq.deploy-backup.faq]]
