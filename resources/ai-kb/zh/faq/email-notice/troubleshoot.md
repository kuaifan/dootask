---
id: email-notice.troubleshoot.faq
title: 邮件收不到怎么办
type: faq
feature: email-notice
scope: admin
locale: zh
aliases:
  - 邮件收不到
  - 邮件没收到
  - 发不出邮件
  - 邮件失败
  - SMTP 超时
  - 邮件被拒
  - 邮箱验证邮件没来
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 邮件发送失败不会自动重试
  - 系统日志只记录发送结果，不抓取 SMTP 原始报文
  - 没有 web 界面查询「这封邮件发到哪了」
last_verified: v1.7.90
---

# 邮件收不到怎么办

## 问题
DooTask 用户没收到注册验证 / 改邮箱验证码 / 未读消息汇总等系统邮件。

## 排查顺序
1. **管理员有没有配 SMTP？**
   - 进「系统设置 → 邮箱」检查 SMTP 服务器/端口/账号/密码非空
   - 未配置时所有邮件链路根本不发出
2. **SMTP 配置对不对？**
   - 点「邮件发送测试」给自己的可用邮箱发一封测试，详见 [[email-notice.send-test.howto]]
   - 失败按弹窗的提示定位（超时 = 服务器/网络；550 = 收件方拒收）
3. **收件人在不在忽略列表？**
   - 检查「系统设置 → 邮箱 → 忽略邮箱地址」
   - 列表中的邮箱**所有**系统邮件都不发，包括验证码
4. **是不是未读消息邮件？**
   - 是的话，确认 `notice_msg` 已开启
   - 当前时间在 `msg_unread_time_ranges` 配置的时间段内
   - 未读分钟数已超过 `msg_unread_user_minute` / `msg_unread_group_minute`
5. **收件方有没有拦截？**
   - 检查收件邮箱的「垃圾邮件」「广告」「订阅」文件夹
   - 用企业邮箱时联系邮箱管理员看反垃圾日志
6. **SMTP 端口被云厂商封了？**
   - 部分云服务商默认封 25 端口出方向；换 465/587 + 对应授权码

## 必看 SMTP 服务商配置
- QQ / 微信邮箱：需在邮箱设置中开启 SMTP 并生成授权码作为密码
- Gmail：需开启「应用专用密码」，不能用账号密码
- 阿里云 / 腾讯企业邮：通常使用 465（SSL）或 587（STARTTLS）

## 如果仍然收不到
- 在表单点「邮件发送测试」抓取最后一次错误
- 查看 Docker 容器日志：`docker logs dootask-php` 搜邮件相关报错
- 收件方可能屏蔽了 DooTask 的发件域名 / IP
