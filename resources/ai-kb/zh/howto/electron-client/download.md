---
id: electron-client.download.howto
title: 下载安装桌面端
type: howto
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 下载桌面端
  - 安装客户端
  - 桌面端去哪下
  - 怎么装客户端
  - 客户端下载
  - 桌面版安装包在哪
related_tools: []
related_pages: []
prerequisites:
  - 已知服务器地址(管理员告知或公司内网约定)
negative:
  - 安装包并非内置在服务器,通常由 GitHub Releases 或运维提供下载链接
  - macOS 首次启动遇「无法打开,因为它来自身份不明的开发者」需在系统设置允许
  - Windows 装到非默认目录需勾选「允许更改安装目录」
last_verified: v1.7.90
---

# 下载安装桌面端

## 下载渠道
桌面端安装包通常通过以下渠道获取:
- **GitHub Releases**:`https://github.com/kuaifan/dootask/releases` 找最新版的 `Assets`
- **公司内网**:有的部署方会自建下载页或挂在公司云盘
- **服务器侧链接**:部分私有部署会在登录页底部加「下载客户端」链接

## 选择安装包
按 [[electron-client.platforms.concept]] 确认对应架构,常见匹配:
- Mac(Apple Silicon)→ `DooTask-v*-mac-arm64.dmg`
- Mac(Intel)→ `DooTask-v*-mac-x64.dmg`
- Windows → `DooTask-v*-win-x64.exe`

## 安装步骤

### macOS(.dmg)
1. 双击 dmg 文件挂载
2. 将 DooTask 图标拖到 Applications 文件夹
3. 首次启动若提示「无法打开」:系统设置 → 「隐私与安全性」→ 底部「仍要打开」

### macOS(.pkg)
1. 双击 pkg 一路点「继续」即可,适合需要部署到 `/Applications` 全局位置的场景

### Windows(.exe / NSIS)
1. 双击安装包
2. 接受协议
3. 选安装位置(默认 `C:\Program Files\DooTask`)
4. 完成后桌面会有 DooTask 快捷方式

### Linux(.deb)
```bash
sudo dpkg -i DooTask-v*-linux-amd64.deb
```

### Linux(.rpm)
```bash
sudo rpm -ivh DooTask-v*-linux-x86_64.rpm
```

## 首次启动配置
1. 启动 DooTask 后输入**服务器地址**(如 `https://dootask.公司域名/`)
2. 输入账号密码登录
3. 后续启动会自动登录到上次的服务器

## 不支持
- 安装包内不带服务器,必须有可访问的 DooTask 后端
- 不支持同一台机器同时安装多个版本(会冲突,需先卸载旧版)
