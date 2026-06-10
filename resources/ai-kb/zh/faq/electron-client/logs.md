---
id: electron-client.logs.faq
title: 桌面端日志在哪 / 闪退排查
type: faq
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 桌面端日志
  - 客户端崩溃
  - 桌面端闪退
  - 客户端打不开
  - 客户端报错
  - 日志路径
  - log 在哪
  - 客户端卡死
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 日志文件存在用户目录的应用日志路径下,客户端不内嵌日志查看器
  - 没有「一键导出诊断包」功能,需要用户手动取日志
  - 业务侧错误(发消息失败、接口超时)写在主进程日志,而非渲染进程
last_verified: v1.7.90
---

# 桌面端日志在哪 / 闪退排查

## 问题
DooTask 桌面端启动后白屏、闪退、卡死,需要查看日志判断原因。

## 日志路径
桌面端使用 `electron-log` 写日志,默认路径(`app-name` 为 `DooTask`):

- **macOS**:`~/Library/Logs/DooTask/main.log`
- **Windows**:`%USERPROFILE%\AppData\Roaming\DooTask\logs\main.log`
- **Linux**:`~/.config/DooTask/logs/main.log`

主要日志文件:
- `main.log`:主进程 + 业务事件(网络、更新、托盘、IPC)
- `renderer.log`:渲染进程(网页内)异常(部分版本)
- 历史日志会按大小滚动归档为 `main.old.log` 等

## 常见原因与排查

### 1. 启动直接闪退
- 检查日志末尾是否有 `JavaScript exception` 或 `Uncaught`
- 升级 / 降级到对应系统兼容的版本([[electron-client.platforms.concept]])
- macOS 13 之前的旧系统可能不兼容 Electron 38

### 2. 白屏 / 卡在 loading
- 多半是无法连接服务器
- 临时退出 → 启动前先确认浏览器能打开服务器域名
- 检查 [[electron-client.proxy.concept]] 代理设置

### 3. 收不到通知 / 没有红点
- 见 [[electron-client.notify.concept]] 的授权排查
- 日志搜 `notification` 关键字

### 4. 更新失败
- 日志搜 `update` 关键字看 `electron-updater` 报错
- 通常是更新源不可达,改用 [[electron-client.update.howto]] 手动升级

## 提交反馈
向开发 / 运维提反馈时附上:
1. `main.log` 最后 200 行
2. 系统版本、客户端版本(关于页查看)
3. 复现步骤
4. 截图(若是 UI 问题)

## 不支持
- 客户端 UI 不内嵌日志查看 / 上传(需用户手动取文件)
- 日志不会自动上传到服务器(隐私考虑)
- 不支持远程实时日志(只能事后分析)
