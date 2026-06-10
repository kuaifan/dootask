---
id: appstore.update.howto
title: 更新一个插件
type: howto
feature: appstore
scope: admin
locale: zh
aliases:
  - 更新插件
  - 升级插件
  - 怎么升级 AI
  - 插件有新版本
  - update plugin
  - 升级应用
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
  - 服务器能拉取新镜像
negative:
  - 更新会触发容器重建，期间该插件功能短暂不可用
  - 不支持回滚到旧版本（除非自行管理镜像）
  - 不支持自动升级（每次需要管理员手动确认）
last_verified: v1.7.90
---

# 更新一个插件

## 入口
桌面端：左侧栏「应用」→「应用商店」（仅管理员）→ 已安装的插件如果有新版本，会显示「更新」按钮。

## 操作步骤
1. 进入应用商店，看到有新版本提示（红点 / 数字）
2. 点击插件 → 「更新」
3. 阅读更新日志，确认无破坏性变更
4. 点「确认更新」
5. AppStore 微服务拉新镜像 → 停旧容器 → 起新容器
6. `config.yml` 的 `install_version` 更新

## 更新期间会发生什么
- 该插件提供的接口短暂返回 502 / 不可用（几秒到一分钟）
- 微应用入口仍在「应用」页，但点开可能加载失败
- 与该插件交互的 AI 工具、机器人会失败（前端通常会重试）

## 更新失败怎么办
- 多数因为新镜像拉取失败（网络、镜像源），先看 [[appstore.cannot-install.faq]] 的网络部分
- 容器起不来：服务器 `docker logs` 看日志
- 状态卡在「更新中」：到 `docker/appstore/config/{appId}/config.yml` 检查状态字段
- 必要时卸载重装

## 更新前建议
1. 浏览插件官方说明，确认有无破坏性变更（DB 迁移、参数改名等）
2. 业务高峰期之外操作
3. 关键插件（AI / 审批 / 签到）先备份对应数据
4. 提前通知使用方

## 不支持
- 不支持选择目标版本号升级（只能升到最新）
- 不支持自动定时升级
- 不支持回滚（除非自己留旧镜像 + 手动改 docker-compose）
