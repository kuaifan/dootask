---
id: user-account.login.howto
title: 登录账号
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 怎么登录
  - 登录 DooTask
  - 登录方式
  - 怎么进系统
  - 登不上
  - 密码登录
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 邮箱、密码各自最长 32 字符，超过提示「帐号或密码错误」
  - 账号被停用（disable_at 非空）会提示「帐号已停用」，需联系管理员
  - 开启「注册需邮箱验证」时，未验证邮箱的账号无法登录，必须先完成验证（[[user-account.email-verify.howto]]）
  - 多次失败后系统会强制要求填验证码（[[user-account.login-codeimg.howto]]）
last_verified: v1.7.90
---

# 登录账号

## 入口
- 登录页：`/login`
- 客户端启动时自动跳转

## 支持的登录方式
DooTask 同时支持以下登录方式（在登录页可切换）：

1. **邮箱 + 密码**：默认方式
2. **扫码登录**：客户端/App 已登录后扫码登录另一端，详见 [[user-account.login-qrcode.howto]]
3. **LDAP**：管理员启用 LDAP 时，邮箱+LDAP 密码也可登录，系统自动同步用户
4. **SSO**：管理员配置 OAuth/SAML 后，登录页会出现对应按钮（具体取决于插件）

## 邮箱密码登录步骤
1. 填邮箱、密码
2. 若系统判定需要验证码（API `login/needcode` 返回 need），填图形验证码
3. 提交，成功返回 token 和用户信息

## 登录后行为
- 写入 `last_ip`、`last_at`、`line_ip`、`line_at`
- 生成 token（默认 30 天有效，可在系统设置 token_valid_days 调整）
- 首次登录会自动创建「📝 个人项目」

## 常见错误
- **帐号或密码错误**：邮箱不存在或密码不对；连续错误后会触发验证码
- **请输入验证码 / 请输入正确的验证码**：触发风控，需填图形验证码
- **您还没有验证邮箱**：需先按 [[user-account.email-verify.howto]] 完成验证
- **帐号已停用**：账号被管理员禁用，联系管理员恢复
