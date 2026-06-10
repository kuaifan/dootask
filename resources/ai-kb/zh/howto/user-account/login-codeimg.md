---
id: user-account.login-codeimg.howto
title: 登录图形验证码
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 图形验证码
  - 登录验证码
  - 为什么要填验证码
  - 验证码看不清
  - 刷新验证码
  - needcode
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 验证码区分大小写；填错会反复要求重输
  - 图形验证码仅用于登录风控，不能用于注册/找回密码
  - 输入正确的账号密码后再没必要每次都填验证码；只有触发风控后才强制要求
last_verified: v1.7.90
---

# 登录图形验证码

## 是什么
DooTask 登录页在风控触发后会弹出图形验证码，要求用户填写。底层接口 `api/users/login/codeimg`（返回图片）和 `login/codejson`（返回 base64 + key）。

## 触发条件
- 同一邮箱多次密码错误（系统标记 `code::邮箱 = need`）
- 部分客户端/IP 默认就要求验证码
- 可调 `api/users/login/needcode?email=xxx`，返回 need=1 即表示当前账号需要验证码

## 操作步骤
1. 输入邮箱、密码后，登录页若出现验证码框，先填验证码
2. 看不清：点验证码图刷新，会生成新的图片+新的 code_key
3. 验证通过且密码正确后，系统自动清除 `code::邮箱` 标记，后续不再要求验证码

## 两种验证码接口的区别
- **codeimg**：返回 PNG 图片二进制，session 内验证，提交时不需要带 code_key
- **codejson**：返回 base64 图片 + key，提交时必须带 `code_key`；客户端/App 用这个（无 session）

## 不支持
- 不支持滑块/拼图验证码，目前仅文本图形码
- 不支持邮箱/短信验证码替代图形验证码

## 相关
- 登录主流程：[[user-account.login.howto]]
