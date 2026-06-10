---
id: common-faq.deploy-backup.faq
title: 怎么备份 DooTask 数据
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 数据备份
  - 怎么备份
  - 备份数据库
  - 备份附件
  - 定期备份
  - 怎么导出全部数据
  - 怎么迁移到新服务器
related_tools: []
related_pages: []
prerequisites:
  - 部署机能 root 执行 sudo ./cmd
negative:
  - 没有「一键全量备份」按钮，要分别备份数据库、附件、配置三部分
  - cmd update 自带数据库备份，但不备份 public/uploads
  - 备份文件不会自动归档到外部存储，要自行同步
last_verified: v1.7.90
---

# 怎么备份 DooTask 数据

## 完整备份包含三部分
1. **数据库**：所有任务、项目、消息、用户元数据
2. **附件目录**：`public/uploads/`（附件、头像）
3. **配置**：`.env`、`docker/appstore/config/`、SSL 证书

## 数据库
```bash
sudo ./cmd mysql backup       # 备份到 docker/mysql/backup/<db>_<时间戳>.sql.gz
sudo ./cmd mysql restore      # 列出备份让你选编号还原
```
`./cmd update` 会自动备份一次，升级前不用手动操作。

## 附件目录
没有内置命令，自己打包：
```bash
tar czf uploads-$(date +%F).tar.gz --exclude='public/uploads/tmp' public/uploads
```

## 配置
```bash
tar czf config-$(date +%F).tar.gz .env docker/appstore/config
```

## 定期自动备份
crontab 每日凌晨：
```cron
0 2 * * * cd /path/to/dootask && ./cmd mysql backup && tar czf /backups/uploads-$(date +\%F).tar.gz --exclude='public/uploads/tmp' public/uploads
```
再用 rsync / rclone 同步到 S3 / OSS / NAS 外部存储。

## 迁移到新服务器
1. 旧机：备份数据库 + 打包 uploads + 复制 `.env`
2. 新机：`./cmd install` 部署
3. 还原数据库 → 替换 `public/uploads/` → 抄 `.env` 关键项（APP_KEY、JWT_KEY 等）
4. `./cmd php restart`

## 不要做
- 不要只备份数据库不备份 uploads，附件丢了不可恢复
- 不要拷贝 `docker/mysql/data/` 替代 SQL 备份，跨版本可能不兼容
