---
id: common-faq.plugin-not-loaded.faq
title: 插件 / 微应用装了但用不了
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 插件装了用不了
  - 微应用打不开
  - 插件菜单不显示
  - 应用安装了找不到
  - 插件白屏
  - 装完没反应
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 部分插件需要管理员授权可见范围，普通用户才能看到入口
  - 插件依赖独立 Docker 容器，容器没起来即使「已安装」也用不了
  - 插件可能与主程序版本不兼容，需升级主程序或换插件版本
last_verified: v1.7.90
---

# 插件 / 微应用装了但用不了

## 问题
- AppStore 显示已安装，但应用菜单看不到入口
- 点开是白屏 / 404 / 「服务不可用」

## 排查（按概率排）

**可见范围未开（最常见）**
管理员装插件后默认仅自己 / 全员可见。普通用户看不到时联系管理员到「应用 → 管理菜单」配置。详见 [[app-system.visibility.concept]] 或 [[micro-app.permission.concept]]。

**插件容器没起**
插件靠独立 Docker 容器，crash 时白屏。`docker ps | grep <插件名>` 没看到就 AppStore 点「重启」，或 `docker logs <容器名>` 看报错。

**前端缓存旧菜单**：F5 强刷、桌面端 / 移动端杀进程重开。

**版本不兼容**
AppStore 提示「需要主程序 vX.Y.Z+」时升级主程序（[[common-faq.deploy-update.faq]]）或回退插件版本。

**浏览器拦截 iframe**
微应用多用 iframe，广告拦截扩展可能阻止：试无痕。HTTPS 部署时插件必须用 https。

**端口冲突**：容器端口被占，`docker logs` 看报错，改插件 docker-compose 端口映射。

## 重装
1. AppStore 该插件 → 「卸载」等容器停
2. 重新「安装」→ 看进度条到 100%

## 不要做
- 不要 `rm -rf` 插件目录，AppStore 状态会错乱
- 不要直接改 `docker/appstore/apps/<name>/` 下的 docker-compose（升级会被覆盖）

## 相关
- AppStore 装不上：[[appstore.cannot-install.faq]]
- 插件升级：[[appstore.update.howto]]
