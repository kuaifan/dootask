---
id: common-faq.account-forget-password.faq
title: 忘记密码怎么办
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 忘记密码
  - 重置密码
  - 找回密码
  - 密码忘了
  - 没法登录怎么改密码
  - 密码找回邮件
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 找回密码必须能收到邮件，邮箱未验证或邮件服务未配则只能找管理员
  - LDAP / 域账号的密码在域控修改，DooTask 自助找回无效
  - 超级管理员（id=1）忘密码不能用前端找回，必须走服务器命令重置
last_verified: v1.7.90
---

# 忘记密码怎么办

## 问题
忘了 DooTask 登录密码，要如何找回 / 重置。

## 普通用户找回
前提：注册时已验证邮箱、管理员已配 SMTP（[[email-notice.config.howto]]）。

流程：
1. 登录页点「忘记密码」
2. 输入账号邮箱
3. 系统发带验证码 / 链接的邮件
4. 填验证码、设新密码、用新密码登录

收不到邮件：检查垃圾邮件、等几分钟、确认邮箱拼写。详见 [[common-faq.account-email-not-verified.faq]]。

## LDAP / 域账号
DooTask 不存域账号密码，找回入口对域账号无效。找公司 IT 重置域密码，回 DooTask 用新密码登录即可。

## 管理员侧重置（兜底）
1. 进「成员管理」找到该用户
2. 点「重置密码」→ 设新密码或自动生成
3. 私下告知，登录后让用户立即改

## 超级管理员忘密码
不能用前端「忘记密码」，走服务器：

```bash
sudo ./cmd artisan tinker
# 在 tinker 内
\App\Models\User::whereUserid(1)->first()->update(['userpass' => \App\Models\User::md5s('新密码')]);
```

参考 `app/Models/User.php` 的 md5s 加密方式。

## 不要做
- 不要直接改库 `users.userpass`，必须用 `md5s()` 加密
- 不要给所有用户用同一个临时密码

## 相关
- 登不进去：[[common-faq.account-cant-login.faq]]
- 邮件收不到：[[common-faq.account-email-not-verified.faq]]
