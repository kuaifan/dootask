---
id: checkin.wifi.howto
title: WiFi 自动签到怎么用
type: howto
feature: checkin
scope: end-user
locale: zh
aliases:
  - WiFi 签到
  - MAC 签到
  - 连 WiFi 自动打卡
  - 路由器签到
  - 自动签到怎么设置
  - 上班连 WiFi 就打卡
related_tools: []
related_pages: [user-settings]
prerequisites:
  - 管理员已在「签到设置 → 签到方式」勾选「WiFi 签到」
  - 办公网路由器是 OpenWrt 系统并已执行管理员提供的安装命令
last_verified: v1.7.90
---

# WiFi 自动签到怎么用

## 原理
WiFi 签到由办公网路由器（OpenWrt）安装一个定时脚本，每隔约 1 分钟扫描局域网内已连接设备的 MAC 地址，上报到 DooTask 服务端。服务端匹配到「该 MAC 已被某成员登记」即自动生成一条签到记录。所以并不是「员工手机连上 WiFi 就立刻打卡」，而是「连上后路由器下一次上报时打卡」，存在 ±1 分钟延迟。

## 操作步骤（成员侧）
1. 打开「应用」→「签到打卡」抽屉 → 「签到设置」Tab → 切到「WiFi 签到」
2. 填入设备 MAC 地址（格式 `XX:XX:XX:XX:XX:XX`，大小写均可）
3. 可填「备注」（如「工作笔记本」「办公手机」）
4. 点「添加设备」可再加一条
5. 点「提交」保存

每位成员最多绑定 **3 个** MAC 地址。同一 MAC 不能被多个成员绑定（提示「已被其他成员设置」）。

## 操作步骤（管理员侧）
1. 「签到设置 → WiFi 签到 → 安装说明」处复制 `curl ... | sh` 一行命令
2. 在 OpenWrt 路由器 SSH 终端执行该命令完成安装
3. 关闭再开启签到功能后需要重新安装

## 不支持
- 不支持非 OpenWrt 路由器（如普通家用 TP-Link 原厂固件）
- 单成员不能登记超过 3 个 MAC（超过会被拒）
- 单 MAC 只能归属一名成员
