---
id: appstore.install.howto
title: 安装一个插件
type: howto
feature: appstore
scope: admin
locale: zh
aliases:
  - 装插件
  - 安装应用
  - 启用功能
  - 怎么开通审批
  - 怎么开 AI
  - install plugin
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
  - 服务器能连接外网（拉取 Docker 镜像）或已预置离线镜像
negative:
  - 安装过程要拉 Docker 镜像，依赖网络速度
  - 不支持仅给单个用户安装（影响全员）
  - 安装失败一般是网络 / 镜像源问题，看 [[appstore.cannot-install.faq]]
last_verified: v1.7.90
---

# 安装一个插件

## 入口
桌面端：左侧栏「应用」→ 顶部分组「管理员」→「应用商店」→ 在列表里选要装的插件。
对应实现：微应用 `appstore`，注册位置 `application/admin`，URL `appstore/internal`。

## 操作步骤
1. 进入应用商店，浏览插件列表（左侧分类：官方 / 社区）
2. 点击想装的插件，看说明、版本号、作者、依赖资源
3. 点「安装」按钮，AppStore 微服务后台拉镜像并启动容器
4. 等待状态从「安装中」变成「已安装」（时间从十秒到数分钟，取决于镜像大小）
5. 安装完成后到「应用」页刷新，对应入口出现

## 安装做了什么
- 在 `docker/appstore/config/{appId}/` 写入 `config.yml`，包含 `status: installed`、`install_at`、`install_version`、`params`
- 把对应 `docker-compose.yml` 起动；新容器与 DooTask 共享网络
- 部分插件会通过 menu_items 注册微应用入口到普通成员的「应用」页

## 安装成功验证
- 应用商店里该插件标记为「已安装」
- 普通成员的「应用」菜单看到对应入口
- 后端 `App\Module\Apps::isInstalled('xxx')` 返回 true

## 常见可装插件
ai、approve、checkin、face、office、drawio、minder、okr、search、fileview、community_kuaifan_memos、community_kuaifan_kpi 等。

## 不支持
- 不支持手动改 `config.yml` 后立即生效（要走 AppStore 流程，否则缓存与状态不一致）
- 不支持选择历史版本安装（除非通过升级）

## 装不上时
见 [[appstore.cannot-install.faq]]。
