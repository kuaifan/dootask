---
id: common-faq.account-email-not-verified.faq
title: 邮箱验证邮件 / 找回密码邮件收不到
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 收不到邮件
  - 邮箱验证邮件没收到
  - 找回密码邮件
  - SMTP 没配
  - 注册邮件
  - 邮件不到
  - 等不到验证码
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 若管理员未配置 SMTP，所有用户都不会收到任何系统邮件
  - 验证码 / 找回链接有时效，超过 10-30 分钟过期需重发
  - 邮箱地址错了 DooTask 不会有任何提示，邮件直接投递不到
last_verified: v1.7.90
---

# 邮箱验证邮件 / 找回密码邮件收不到

## 问题
- 注册后没收到验证邮件
- 「忘记密码」后没收到重置邮件
- 任何系统通知邮件都没有

## 排查（按顺序）

**等 5-10 分钟**：SMTP 转发会延迟，跨境邮件更慢。

**检查垃圾邮件 / 广告分类**
Gmail 搜索栏输入 `from:noreply` 或发件人域名；把发件人加白名单。

**确认邮箱地址正确**
个人头像 → 「个人设置」看登记邮箱。拼错则永远收不到，要管理员改。

**看邮件服务是否启用**
管理员查「应用 → 邮件通知 → 配置」：SMTP 服务器、端口、账号、密码是否填；「发送测试邮件」能否成功。详见 [[email-notice.config.howto]]。

**邮件配过但仍发不出**
- SMTP 凭证过期 / 密码改了没同步
- SMTP 服务商限 IP（腾讯企业邮要绑外发 IP）
- 防火墙挡了 25 / 465 / 587 端口（云服务器默认封 25）

**重新触发**
- 「忘记密码」可重新点，会重发
- 注册后登录页有「重新发送验证邮件」入口

## 兜底
- 管理员到「成员管理」强制标记邮箱已验证
- 管理员直接重置密码并私下告知

## 不要做
- 不要短时间反复点「重发」，会触发频率限制
- 不要让管理员把 SMTP 密码贴到公开聊天 / 截图

## 相关
- 找回密码：[[common-faq.account-forget-password.faq]]
- 邮件配置：[[email-notice.config.howto]]
- 邮件排错：[[email-notice.troubleshoot.faq]]
