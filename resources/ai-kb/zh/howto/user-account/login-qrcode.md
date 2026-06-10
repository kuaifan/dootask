---
id: user-account.login-qrcode.howto
title: 扫码登录
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 扫码登录
  - 二维码登录
  - 扫码登陆
  - 怎么扫码登录
  - 网页扫码
  - App 扫码登 PC
related_tools: []
related_pages: []
prerequisites:
  - 一端（网页/客户端/App）已登录，作为「扫码端」
  - 另一端处于登录页，作为「被登录端」
negative:
  - 二维码 code 30 秒内有效；过期需刷新登录页重新生成
  - 二维码 code 必须 ≥ 32 字符，被篡改/截断会报「参数错误」
  - 被登录端轮询拿到的是用户 token，会创建一条新登录记录（不是会话共享）
  - 不支持「同一二维码多端复用」，扫码成功并被消费后立即失效
last_verified: v1.7.90
---

# 扫码登录

## 是什么
DooTask 客户端/App/网页在登录页生成一个二维码，已登录的另一端扫码后即可让登录页直接登入对应账号。底层接口 `api/users/login/qrcode`。

## 操作步骤
1. **被登录端**：打开登录页，切到「扫码登录」tab，自动生成二维码
2. **扫码端**：用已登录的 App 扫一扫，弹「确认登录到 XX 端」
3. 确认后被登录端自动跳转到首页，登录完成

## 接口流程（了解原理用）
- 被登录端：周期性调用 `login/qrcode?type=status&code=xxx`，返回 `success` + user 即代表扫码端确认
- 扫码端：扫码后调用 `login/qrcode?type=login&code=xxx`，把当前用户与该 code 绑定
- code 在 Redis 缓存中，TTL 30 秒；超时未确认需刷新二维码

## 不支持
- 二维码不能截图给别人扫；扫码即视为本人授权登录
- 不能用「未登录设备」反向扫「已登录设备」生成的码，方向必须是已登录端扫被登录端

## 相关
- 普通密码登录：[[user-account.login.howto]]
- 图形验证码：[[user-account.login-codeimg.howto]]
