---
id: user-account.email-verify.howto
title: 邮箱验证
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 邮箱验证
  - 验证邮箱
  - 收不到验证邮件
  - 邮箱激活
  - 链接失效
  - 验证链接过期
related_tools: []
related_pages: []
prerequisites:
  - 管理员已开启「注册需邮箱验证」（emailSetting.reg_verify = open）
  - 管理员已配置可用的 SMTP 邮件服务
negative:
  - 验证链接 30 分钟内有效，过期需重新登录/注册触发新邮件
  - 验证链接是一次性的，已使用过的链接会提示「链接已经使用过」
  - 没开启 reg_verify 时，注册后无需验证邮箱
  - 注销账号确认（[[user-account.delete.howto]]）也走邮箱验证，但用的是不同 type 的验证码
last_verified: v1.7.90
---

# 邮箱验证

## 触发场景
- 注册时管理员开启了「注册需邮箱验证」
- 已注册但 `email_verity = 0` 的账号尝试登录
- 注销账号确认（type=3）

## 操作步骤
1. 注册/登录后系统调用 `UserEmailVerification::userEmailSend` 给你邮箱发一封验证邮件
2. 打开邮件，点击「验证邮箱」链接
3. 链接会带 `code` 参数，跳到验证页自动调 `api/users/email/verification?code=xxx`
4. 成功提示「绑定邮箱成功」，可返回登录页正常登录

## 收不到邮件怎么办
- 检查垃圾邮件/广告邮件分类
- 确认登录邮箱拼写正确
- 等待 1-2 分钟（SMTP 投递延迟）
- 联系管理员检查系统 SMTP 配置和发件箱可用性

## 错误码
- **无效连接,请重新注册**：code 不存在或已被篡改
- **链接已经使用过**（data.code=2）：同一个 code 只能用一次，重新触发邮件
- **链接已失效，请重新登录/注册**：超过 30 分钟，重新触发

## 不支持
- 不支持自助修改注册邮箱；如确需更换邮箱，按 [[user-account.delete.howto]] 注销后用新邮箱重新注册
- 不支持短信验证码替代邮箱验证
