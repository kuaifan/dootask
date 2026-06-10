---
id: appstore.cannot-install.faq
title: 插件装不上怎么办
type: faq
feature: appstore
scope: admin
locale: zh
aliases:
  - 插件装不上
  - 应用安装失败
  - 装插件超时
  - 拉镜像失败
  - 应用商店打不开
  - 应用商店空白
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限和服务器 SSH 权限
negative:
  - 不是所有错误都能在 UI 里看到，多数要 SSH 上服务器看 docker 日志
  - 没有「自动重试」按钮，要手动卸载重装或修问题
last_verified: v1.7.90
---

# 插件装不上怎么办

## 问题
点击「安装」后长时间停在「安装中」，或直接报错「安装失败」「拉取镜像失败」「容器启动失败」。

## 常见原因
1. 网络问题：服务器拉不到 Docker 镜像（Docker Hub 限速 / 网络不通）
2. 镜像源问题：境内服务器没配镜像加速器
3. 磁盘不足：宿主机 `df -h` 看是否 100%
4. 端口冲突：插件想用的端口被占
5. 权限问题：`docker/appstore/` 目录不可写
6. AppStore 微服务异常：appstore 容器本身挂了
7. 已有同名 / 旧版本容器残留没清干净

## 解决
按顺序排查：

1. **看 AppStore 容器日志**：服务器 `docker logs appstore` 看具体错误
2. **测试外网**：服务器 `docker pull hello-world` 验证 Docker 能拉镜像
3. **配镜像加速器**：境内服务器在 `/etc/docker/daemon.json` 加 `registry-mirrors`，重启 docker
4. **看磁盘**：`df -h` 和 `docker system df`，必要时 `docker system prune -af` 清旧镜像
5. **看端口**：插件 `docker-compose.yml` 中端口和 `netstat -tlnp` 对照
6. **看权限**：`docker/appstore/` 目录应能被 docker 容器写入；按项目里 `dootask-fix-permission` 流程修
7. **手动清理**：`docker ps -a | grep <appId>` 找残留容器 → `docker rm -f` 删 → 再装
8. **重启 AppStore**：`docker restart appstore`，再试

## 仍然不行
- 看插件官方 README 是否有特殊要求（如最低主版本、特定环境变量）
- 联系插件作者 / 社区
- 提 issue 时带上 `docker logs appstore` 和插件 `docker-compose.yml`

## 不支持
- 不支持纯 UI 操作排查（必须 SSH）
- 不支持安装与主程序版本不兼容的旧插件

## 相关
- 安装流程：[[appstore.install.howto]]
- 入口：[[appstore.entry.menu-map]]
