---
id: common-faq.account-cant-login.faq
title: 登不进去 / 登录页报错
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 登不上
  - 登录失败
  - 账号密码错误
  - 登录页报错
  - 进不去
  - 提示账号不存在
  - 输入正确密码也登不上
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 没有「跳过登录」入口，所有功能必须登录后使用
  - 重置密码必须能收到邮件，否则只能找管理员
  - 普通用户没有账号注册自助开关，注册策略由管理员控制
last_verified: v1.7.90
---

# 登不进去 / 登录页报错

## 问题
输入账号密码后报错，常见提示：「账号或密码错误」「账号不存在」「账号已被禁用」「登录失败次数过多」「请输入验证码」。

## 原因 + 解决

**账号 / 密码错（最常见）**
- 确认大小写、空格、Caps Lock
- 试用密码重置：[[common-faq.account-forget-password.faq]]

**账号不存在**
- 邮箱拼错（多数情况）
- 还没注册：检查管理员是否需要邀请才能注册
- 被注销：联系管理员看 `user_deletes` 表

**账号被禁用 / 锁定**
- 多次密码错误触发风控 → [[common-faq.account-locked.faq]]
- 管理员主动禁用 → 联系系统管理员解锁

**提示需要验证码**
- 后端启用了登录验证码图片
- 没刷出来：F5 强刷；验证失败换个浏览器重试

**提示需要邮箱验证**
- 注册后未点击验证邮件 → [[common-faq.account-email-not-verified.faq]]

**LDAP 用户登录失败**
- 普通账号能登 LDAP 不行 → [[ldap.troubleshoot.faq]]

**网络 / 服务端问题**
- 浏览器开发者工具看请求是否 200
- 4xx 是参数 / 权限问题，5xx 是服务端故障

## 没法自助时找谁
- 普通账号：系统管理员（公司 IT / 首位注册用户）
- 域账号：公司 IT 重置域密码
- 超级管理员忘密码：登服务器跑 `./cmd artisan` 重置

## 相关
- 忘记密码：[[common-faq.account-forget-password.faq]]
- 账号锁定：[[common-faq.account-locked.faq]]
