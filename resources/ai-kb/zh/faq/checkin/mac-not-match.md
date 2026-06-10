---
id: checkin.mac-not-match.faq
title: 不在公司网络 / MAC 不匹配
type: faq
feature: checkin
scope: end-user
locale: zh
aliases:
  - MAC 不匹配
  - 不在公司网络
  - WiFi 签到没生效
  - 连了 WiFi 没打卡
  - 公司外面能签到吗
  - 在家能 WiFi 签到吗
related_tools: []
related_pages: []
prerequisites: []
last_verified: v1.7.90
---

# 不在公司网络 / MAC 不匹配

## 问题
WiFi 签到没自动生成记录，或日历显示当天未签到。设备明明开着，怀疑是 MAC 没匹配或网络问题。

## 原因
WiFi 签到依赖**办公网路由器**（OpenWrt）扫描局域网内设备 MAC 并上报。任何一环不通就不会打卡：

- **不在办公网**：在家 / 出差时设备未连办公网路由器，路由器扫不到 MAC
- **MAC 未登记**：成员没在「签到设置 → WiFi 签到」绑定当前设备 MAC
- **MAC 被别人占用**：同一 MAC 全系统唯一，被其他成员绑了，自己再绑会报「已被其他成员设置」
- **路由器脚本没装 / 失效**：管理员没在 OpenWrt 路由器执行安装命令，或重启功能后没重新安装
- **不是 OpenWrt 路由器**：脚本仅支持 OpenWrt，其他系统装不上
- **设备休眠 / 关 WiFi**：手机锁屏或休眠后 MAC 上报间隔会变长甚至消失

## 解决
1. 确认现在确实连着办公 WiFi，而不是 4G / 其他网络
2. 打开「签到打卡 → 签到设置 → WiFi 签到」核对 MAC 是否填正确
3. iOS 14+ / 部分 Android 默认开启「随机 MAC」，关闭它使用真实 MAC
4. 联系管理员确认 OpenWrt 路由器脚本已安装且正常上报
5. 临时切到 [[checkin.regular.howto]] 手动签到或人脸签到顶一下

## 不支持
- 在家办公或出差时不能用 WiFi 签到，请用手动 / 定位签到
- WiFi 签到默认延迟约 1 分钟，不要期望「秒打卡」
