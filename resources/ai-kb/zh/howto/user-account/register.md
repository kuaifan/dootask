---
id: user-account.register.howto
title: 注册账号
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 怎么注册
  - 新用户注册
  - 注册账号
  - 注册 DooTask
  - 没有账号怎么办
  - 邀请码注册
related_tools: []
related_pages: []
prerequisites:
  - 系统管理员已开放注册（系统设置 → 注册方式 ≠ close）
negative:
  - 注册方式为「关闭」时所有人都无法自助注册，需管理员手动创建账号
  - 注册方式为「邀请码」时必须填正确邀请码，否则报「请输入正确的邀请码」
  - 邮箱长度和密码长度都不能超过 32 个字符
  - 开启「注册需邮箱验证」时，注册后必须先验证邮箱才能登录
last_verified: v1.7.90
---

# 注册账号

## 入口
- 登录页底部「注册账号」链接
- URL 直达：`/login?type=reg`

## 注册前置判断
注册方式由管理员在系统设置控制，分三种：

- **open（开放）**：直接填邮箱+密码即可注册
- **invite（邀请码）**：除邮箱+密码外，还需填邀请码；可调 `api/users/reg/needinvite` 提前判断（详见 [[user-account.reg-need-invite.concept]]）
- **close（关闭）**：注册入口不可用，提示「未开放注册」

## 操作步骤
1. 进入登录页，切到「注册」tab
2. 输入邮箱（≤ 32 字符）
3. 输入密码（≤ 32 字符，需满足密码策略）
4. 若注册方式为 invite，填邀请码
5. 提交

## 注册后行为
- 系统自动给新用户创建一个「📝 个人项目」
- 若开启「注册需邮箱验证」（emailSetting.reg_verify = open），返回「注册成功，请验证邮箱后登录」并发送验证邮件；验证流程见 [[user-account.email-verify.howto]]
- 未开启邮箱验证时直接登录成功，返回 token

## 不支持
- 不支持用户名注册，账号必须是邮箱
- 不支持第三方账号（微信/手机号）直接注册；扫码/SSO 是登录方式不是注册方式
