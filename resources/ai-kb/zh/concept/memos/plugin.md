---
id: memos.plugin.concept
title: Memos 插件元信息
type: concept
feature: memos
scope: end-user
locale: zh
aliases:
  - Memos 插件
  - memos 怎么装
  - memos 应用市场
  - 笔记插件
  - community_kuaifan_memos
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - Memos 不是主程序内置功能，未装插件时入口不会出现
  - 插件升级不通过 git pull，需要在应用市场更新
  - 插件不能离线安装到不联网的环境
last_verified: v1.7.90
---

# Memos 插件元信息

## 定义
Memos 在 DooTask 中由社区插件提供，应用市场 app id 为 `community_kuaifan_memos`（当前版本 0.29.0），feature 短名 `memos`。主程序不内置任何 Memos 代码，所有笔记逻辑都跑在独立容器中，通过 nginx 反向代理 `/apps/memos/` 子路径挂载到 DooTask 界面。

## 关键属性
- **作者**：DooTask 官方（基于开源项目 usememos/memos）
- **运行形态**：两个 Docker 容器
  - `memos-server`：自构建 Memos 镜像（`dootask/memos-server:0.29.0`），前端打补丁支持子路径
  - `memos-proxy`：鉴权代理（`dootask/memos:<version>`），校验 DooTask 用户令牌、自动建号 / 免密登录
- **数据存储**：SQLite，本地卷 `memos-data` 挂载到 `/var/opt/memos`，不入主库
- **菜单注入**：安装后在「应用中心」注册「Memos 笔记」入口
- **会话有效期**：访问令牌过期后通过 `memos_refresh` cookie 自动续期，最长 30 天

## 安装配置项
- **管理员**：在安装界面选择若干 DooTask 用户作为 Memos 管理员，其余用户以普通成员身份登录
- **内部密钥**：用于派生账号密码与签名会话，安装后请勿修改

## 单点登录机制
- 用户打开插件时，代理自动在 Memos 内建号、用确定性密码登录
- 屏蔽 Memos 原生的直接登录与自助注册接口（防止绕过 DooTask 鉴权）

## 不支持
- 不能用 Memos 原生账号密码登录（仅支持 DooTask SSO）
- 不能选择不安装 `memos-proxy`（鉴权必经路径）

## 相关
- 是什么：[[memos.concept]]
- 入口在哪：[[memos.entry.menu-map]]
