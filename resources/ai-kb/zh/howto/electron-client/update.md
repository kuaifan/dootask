---
id: electron-client.update.howto
title: 升级桌面端
type: howto
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 桌面端升级
  - 客户端更新
  - 怎么升级桌面端
  - 升级新版本
  - 自动更新
  - 手动更新
  - 桌面端有新版本
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 自动更新需要服务器侧配置了更新源(electron-updater feed URL),否则只能手动
  - 自动更新下载完成后必须重启 App 才生效,不会热替换
  - 客户端版本独立于服务器主程序版本,两者各有发布节奏
last_verified: v1.7.90
---

# 升级桌面端

## 升级方式概览
桌面端基于 `electron-updater`,支持「自动检查 + 手动确认下载 + 退出时安装」三段流程,也允许完全手动重装。

## 自动更新(推荐)
默认情况下:
1. App 启动后向更新源查询是否有新版
2. 有新版会在系统通知或客户端右上角提示「发现新版本」
3. 用户点「立即下载」开始下载(默认不自动下载,避免占带宽)
4. 下载完成后弹窗提示「是否立即重启安装」
5. 选「重启」立即装并重开;选「稍后」会在下次退出时自动装

## 手动检查更新
- 客户端菜单 → 「检查更新」(macOS 通常在「DooTask」菜单,Windows 通常在右上角菜单)
- 客户端右上角头像 → 「关于 DooTask」→「检查更新」

## 完全手动升级
当自动更新源不可达(企业内网封了 GitHub):
1. 从 [[electron-client.download.howto]] 中提到的渠道下载新版安装包
2. macOS:直接覆盖拖到 Applications,会替换旧版本
3. Windows:运行新版安装程序,会自动覆盖
4. Linux:`sudo dpkg -i`(deb)或 `sudo rpm -Uvh`(rpm)

## 看当前版本
- macOS:菜单 → 「DooTask」→「关于 DooTask」
- Windows / Linux:右上角头像下拉 → 「关于 DooTask」
- 个人设置页底部也会显示

## 升级后数据
- 配置 / 登录态保留(存放在 `electron-store` 用户目录)
- 不会覆盖个人聊天记录、文件(本地无业务数据,都在服务器)

## 不支持
- 不支持「跨大版本回退」(降级需手动重装旧包,不保证账号兼容)
- 不支持「不重启热更新」
- 自动更新失败不会反复重试,需手动再点检查
