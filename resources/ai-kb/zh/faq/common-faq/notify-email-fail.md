---
id: common-faq.notify-email-fail.faq
title: 邮件通知收不到
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 邮件没收到
  - 收不到邮件
  - 验证码邮件
  - 未读邮件汇总
  - 重置密码邮件
  - 邮件丢了
related_tools: []
related_pages: [user_settings_email]
prerequisites: []
negative:
  - DooTask 邮件发送失败不会自动重试
  - 邮件被忽略邮箱拦下时不发任何通知
  - 部分公邮 ISP 会延迟数小时甚至直接归入广告，DooTask 无控制权
last_verified: v1.7.90
---

# 邮件通知收不到

## 问题
DooTask 没发来注册验证邮件、改邮箱验证码邮件、未读消息汇总邮件、密码重置邮件，等了很久收件箱空空。

## 原因
邮件链路要走通需要四个条件：

1. **管理员配了 SMTP**：`系统设置 → 邮箱` 填了服务器/端口/账号/密码
2. **SMTP 凭证有效**：账号没改密、IP 没被反垃圾封
3. **收件人不在忽略列表**：管理后台「忽略邮箱地址」列表里的地址所有 DooTask 邮件都不发
4. **收件方不拦截**：垃圾邮箱、企业网关、域名信誉过滤都可能丢

未读消息邮件还要额外满足：
- 个人开了 `notice_msg`
- 当前时间在 `msg_unread_time_ranges` 配置时段内
- 未读分钟数超过 `msg_unread_user_minute` / `msg_unread_group_minute`

## 解决
1. 个人设置 → 邮箱 → 「绑定 / 修改邮箱」时点「发送验证码」抓到第一手错误
2. 检查垃圾邮件 / 广告 / 订阅文件夹
3. 改用主流邮箱测试（Gmail / QQ / Outlook），排除企业邮箱反垃圾
4. 让管理员进 [[email-notice.troubleshoot.faq]] 列出的「邮件发送测试」抓详细错误
5. 让管理员从「忽略邮箱地址」移除你的邮箱
6. 管理员级问题：SMTP 端口 25 被云厂商封 → 换 465（SSL）或 587（STARTTLS）+ 授权码

## 不支持
- DooTask 无「邮件发送队列查询」界面，发不出去就是发不出去
- 不支持配置多个 SMTP 做主备切换
- 不支持自定义邮件模板（仅可改发件人显示名）

[[email-notice.config.howto]] 是管理员配置入口；[[email-notice.troubleshoot.faq]] 给管理员更详细的排查。
