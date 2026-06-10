---
id: electron-client.auto-start.howto
title: 桌面端开机自启
type: howto
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 开机自启
  - 自动启动
  - 开机就打开
  - 开机自动运行
  - 登录时启动
  - 启动项
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 桌面端在客户端内**没有**「开机自启」开关,需在操作系统侧配置
  - Linux 不同发行版的自启配置方式差异较大,需自行写 desktop / systemd 配置
  - 开机自启不代表自动登录,首次启动仍需输入账号
last_verified: v1.7.90
---

# 桌面端开机自启

## 总览
DooTask 桌面端**没有内置「开机自启」开关**,需要在操作系统的启动项里手动添加。以下分平台说明。

## macOS

### 方式 1:通过 Dock
1. App 运行起来后,在 Dock 上右键 DooTask 图标
2. 「选项」→「登录时打开」打钩

### 方式 2:系统设置
1. 系统设置 → 「通用」→「登录项」
2. 「在登录时打开」点 `+`
3. 选 Applications 里的 DooTask.app

## Windows

### 方式 1:启动文件夹
1. `Win + R` 输入 `shell:startup` 回车
2. 把桌面的 DooTask 快捷方式复制 / 拖入打开的文件夹

### 方式 2:任务管理器
1. `Ctrl + Shift + Esc` 打开任务管理器
2. 切到「启动应用」标签
3. 找到 DooTask 设为「已启用」(适用于已被系统识别为启动项的情况)

### 方式 3:注册表(进阶)
HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Run 下新增字符串值指向 DooTask.exe 路径。

## Linux

### Ubuntu / GNOME
1. 打开「Startup Applications」(命令 `gnome-session-properties`)
2. 「Add」→ Name 填 DooTask,Command 填 `/opt/DooTask/dootask`(或安装路径)

### 通用 systemd user service
在 `~/.config/systemd/user/dootask.service` 写 unit,然后 `systemctl --user enable dootask`。

## 关闭开机自启
按上述路径反向操作(取消勾选 / 从启动文件夹删除 / 注册表删除)。

## 不支持
- 客户端内无开机自启按钮(future 可能加 `app.setLoginItemSettings` 接口)
- 不支持「开机静默后台启动」(开机会弹出主窗口)
- Linux 各发行版无统一方案
