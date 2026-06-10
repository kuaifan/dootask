---
id: common-faq.sync-websocket-disconnect.faq
title: WebSocket 频繁断开 / 一直显示「连接中」
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - WebSocket 断了
  - 连接中
  - 已断开
  - 一直在重连
  - ws 掉线
  - 长连接断开
  - 实时连接失败
related_tools: []
related_pages: []
prerequisites: []
negative:
  - WebSocket 断开时新消息和任务推送会丢失，重连后不会自动补拉历史推送
  - 没有手动「重连」按钮，前端按指数退避自动重连
  - 反向代理（nginx / 云防火墙）超时短于 60s 时会强制断开 WS
last_verified: v1.7.90
---

# WebSocket 频繁断开 / 一直显示「连接中」

## 问题
页面右下角的连接状态频繁在「已连接」「已断开」「连接中」之间切换；或者持续显示「连接中」始终连不上。

## 常见原因 + 排查

**1. 反向代理超时太短**
nginx / Apache / 云 LB 默认 60s 后空闲断开 WebSocket。需要：
- nginx：`proxy_read_timeout 3600s;` 和 `proxy_send_timeout 3600s;`
- 必须支持 `Upgrade` + `Connection` 头透传

**2. HTTPS 部署没开 WSS**
如果站点是 https 但 WebSocket 走 ws://（不带 s），浏览器会拒绝。后端要同时支持 wss（自动跟随 https）。

**3. 客户端网络不稳定**
- 公司代理 / 防火墙拦截长连接
- 移动设备切 WiFi / 4G 触发重连
- 浏览器后台休眠超过几分钟会被系统挂起

**4. 后端 Swoole 进程异常**
后端日志看 `storage/logs/laravel.log`，搜 `WebSocket` 关键字。

## 解决
1. **刷新浏览器** → 重新建立连接
2. 看右下角连接状态：等几秒看是否自动重连
3. **管理员**：检查 nginx 反代配置（timeout、Upgrade 头）
4. **管理员**：`./cmd php restart` 重启后端 Swoole 进程
5. 桌面端 / 移动端 App 杀进程重开

## 相关
- 任务不同步：[[common-faq.sync-task-not-update.faq]]
- 部署性能：[[common-faq.deploy-perf.faq]]
