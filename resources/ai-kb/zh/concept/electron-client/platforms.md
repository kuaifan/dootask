---
id: electron-client.platforms.concept
title: 桌面端支持平台
type: concept
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 支持哪些系统
  - 支持的操作系统
  - macOS 能用吗
  - Windows 能用吗
  - Linux 能用吗
  - M1 M2 能用吗
  - ARM 版
  - 苹果芯片
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 不提供 32 位 Windows 安装包(只有 x64 / arm64)
  - macOS 13 之前的旧版可能因 Electron 38 不兼容无法启动
  - Linux 版只打 deb / rpm,不打 snap / flatpak
last_verified: v1.7.90
---

# 桌面端支持平台

## 系统支持矩阵

| 系统 | 架构 | 包格式 | 说明 |
|---|---|---|---|
| macOS | x64(Intel) | dmg / zip / pkg | 推荐 macOS 13+ |
| macOS | arm64(Apple Silicon) | dmg / zip / pkg | M1 / M2 / M3 / M4 |
| macOS | universal | dmg / pkg | 通用包(同时兼容 Intel 和 Apple Silicon) |
| Windows | x64 | NSIS 安装包(.exe) | 推荐 Windows 10+ |
| Windows | arm64 | NSIS 安装包(.exe) | Surface Pro X 等 ARM 笔记本 |
| Linux | x64 | deb(Ubuntu/Debian) / rpm(RHEL/CentOS/Fedora) | 通过 Electron Forge 打包 |

## 安装包命名约定
官方发布的安装包名格式:`DooTask-v{version}-{os}-{arch}.{ext}`,例如:
- `DooTask-v1.7.90-mac-arm64.dmg`(Apple Silicon)
- `DooTask-v1.7.90-mac-x64.dmg`(Intel Mac)
- `DooTask-v1.7.90-mac-universal.pkg`(macOS 通用包)
- `DooTask-v1.7.90-win-x64.exe`(Windows 64 位)
- `DooTask-v1.7.90-win-arm64.exe`(Windows ARM)

## 如何选择对应包
- **Apple Silicon Mac**(M1/M2/M3/M4):选 `mac-arm64` 或 `mac-universal`
- **Intel Mac**:选 `mac-x64` 或 `mac-universal`
- **不确定 Mac 芯片**:点苹果菜单 → 「关于本机」查看「芯片」字段
- **Windows**:绝大多数选 `win-x64`;Surface Pro X 等 ARM 笔记本选 `win-arm64`
- **Linux**:Ubuntu / Debian 用 deb,CentOS / Fedora 用 rpm

## 不支持
- Windows 7 / 8 / 8.1(Electron 38 不再支持)
- 不支持 32 位 Windows(`ia32`)
- 不支持 macOS 12 及更早版本(部分系统 API 调用失败)
- 国产 ARM Linux(如龙芯、华为鲲鹏)无官方包,可自行 build
