---
id: electron-client.proxy.concept
title: 桌面端代理设置
type: concept
feature: electron-client
scope: end-user
locale: zh
aliases:
  - 桌面端代理
  - 客户端代理
  - 桌面端走代理
  - HTTP 代理
  - SOCKS 代理
  - 公司代理
  - VPN 设置
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 桌面端**没有内置代理设置面板**,默认跟随操作系统代理配置
  - 不支持在 App 内直接填写代理服务器地址 / 鉴权
  - 全代理 / 分流策略需要在系统或网络层面解决
last_verified: v1.7.90
---

# 桌面端代理设置

## 代理来源
DooTask 桌面端基于 Electron,网络请求默认遵循**操作系统的代理设置**(从 Chromium 继承)。客户端内**没有**独立的代理配置入口。代理生效路径:
- macOS:系统设置 → 「网络」→ 当前网络 → 「详细信息」→「代理」
- Windows:设置 → 「网络和 Internet」→「代理」
- Linux:多数发行版在「设置 → 网络 → 代理」

## 适用场景
- 内网部署需走公司 HTTP 代理出公网
- 通过 VPN 客户端访问私有 DooTask 实例
- 测试环境需经过 mitm / Charles 抓包(配 PAC 或全局代理)

## 启动参数(进阶)
Electron 支持启动时通过命令行参数注入代理。例如 macOS 终端启动:
```bash
open -a DooTask --args \
  --proxy-server="http://192.168.1.10:8080" \
  --proxy-bypass-list="*.internal.com"
```
Windows 在快捷方式属性里给 `dootask.exe` 加同样参数。这种方式属于绕过系统代理,生效优先级最高。

## 鉴权代理
需要用户名 / 密码的代理:
- 操作系统代理面板里录入凭证(macOS 钥匙串、Windows 凭据管理器)
- Chromium 弹出鉴权对话框时输入

## 故障排查
- 客户端打不开 / 登录失败,先确认浏览器(系统默认浏览器)能否打开 DooTask 网址
- 抓包检查请求是否经过预期代理
- 走代理后 WebSocket 连接可能被部分企业代理阻断,需让运维放通 `wss://`

## 不支持
- 客户端 UI 无代理配置项,无法在 App 内切换代理
- 不支持代理协议自动切换(SOCKS5 / HTTP / HTTPS)
- 不支持单 App 独立代理(全系统级)
