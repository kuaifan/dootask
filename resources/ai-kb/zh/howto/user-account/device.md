---
id: user-account.device.howto
title: 设备管理
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 设备管理
  - 登录设备
  - 在线设备
  - 踢下线
  - 远程登出
  - 改设备名
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 设备列表上限由 UserDevice::$deviceLimit 控制，超过会按时间淘汰最旧设备
  - 同一浏览器清缓存/换浏览器/换无痕模式都算新设备
  - 在「设备」里把别的设备登出不会改你当前的登录状态；登出自己当前设备需用 [[user-account.logout.howto]]
  - 不支持「永久禁止某设备再登录」；登出后用账密重新登又会创建新设备记录
last_verified: v1.7.90
---

# 设备管理

## 入口
- 桌面端：右上角头像 →「个人设置」→「账号安全」→「登录设备」
- 移动端：「我的」→「个人设置」→「登录设备」

## 能做什么
对应 `api/users/device/*` 接口：
- **列表**（`device/list`）：看自己所有已登录设备
- **登出某设备**（`device/logout`）：把指定设备踢下线，对应设备下次请求会被拒绝
- **改设备名**（`device/edit`）：修改当前设备的 device_name / 品牌 / 型号 / 系统等显示字段

## 列表显示字段
- 设备名（可改）
- 品牌、型号、操作系统（可改）
- 登录时间、最后活跃时间
- IP 地址
- 当前设备会标「本机」标识

## 操作步骤：登出某设备
1. 进设备列表
2. 找到要登出的设备，点「登出」
3. 系统调用 `device/logout?id=xxx`，删除对应 UserDevice 记录
4. 该设备下次请求会返回 401，被迫回登录页

## 操作步骤：改设备名
1. 在设备卡片上点编辑/重命名
2. 改完保存，调用 `device/edit` 写入 `detail`
3. 改的是当前设备的展示信息，不影响其它设备

## 不支持
- 不支持「同设备多账号同时登录」（每个浏览器/客户端只能有一份 token）
- 不支持「按 IP 段限制设备登录」；这是 license/license-server 才有的能力
