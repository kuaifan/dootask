---
id: common-faq.deploy-update.faq
title: 升级 DooTask 失败 / cmd update 报错
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - 升级失败
  - cmd update 报错
  - 更新不上去
  - 拉代码冲突
  - 数据库迁移失败
  - 升级后启动不了
  - composer 安装失败
related_tools: []
related_pages: []
prerequisites:
  - 部署机能 root 执行 sudo ./cmd
negative:
  - ./cmd update 会自动备份数据库到 docker/mysql/backup/，失败可还原
  - 本地有未提交改动时 update 会停下来，不强制覆盖
  - 不要直接 git pull + composer install，绕过 cmd 会漏掉迁移和重启
last_verified: v1.7.90
---

# 升级 DooTask 失败 / cmd update 报错

## 问题
跑 `sudo ./cmd update` 中途失败：拉代码冲突、composer 装不上、migrate 报错、升级完容器起不来。

## 升级做了什么
`./cmd update` 串行执行：git pull → 备份数据库到 `docker/mysql/backup/` → `composer install` → `artisan migrate` → 重启 Swoole。本地有改动会停下。

## 解决

**拉代码冲突**
- 推荐：`git stash` 暂存改动 → `sudo ./cmd update` → `git stash pop`
- 强制覆盖（慎用，会丢改动）：`sudo ./cmd update --force`

**composer 装不上**
- 多半网络问题，配国内镜像后重试：
  `./cmd composer config repo.packagist composer https://mirrors.aliyun.com/composer/`

**migrate 失败**
- 看 `storage/logs/laravel.log` 找具体 SQL 错误
- 用 `./cmd mysql restore` 选最新备份还原，修复后再 `./cmd artisan migrate`

**起不来**
- `./cmd logs` 看容器日志
- 端口被占 / 内存不足 / Swoole 报错都会导致起不来

## 不要做
- 不要跳过 cmd 直接 git pull + composer install，会漏掉迁移和重启
- 改了 .env / docker-compose.yml 关键配置后必须 `./cmd php restart`

## 相关
- 备份：[[common-faq.deploy-backup.faq]]
- 性能：[[common-faq.deploy-perf.faq]]
