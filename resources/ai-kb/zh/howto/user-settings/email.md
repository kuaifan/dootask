---
id: user-settings.email.howto
title: 修改邮箱
type: howto
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 改邮箱
  - 换邮箱
  - 绑定新邮箱
  - 修改登录邮箱
  - 邮箱地址更改
related_tools: []
related_pages: [setting]
prerequisites:
  - 新邮箱地址未被其他账号占用
negative:
  - LDAP 用户禁止修改邮箱（接口直接拒绝，提示「LDAP 用户禁止修改邮箱」）
  - 体验 / 演示账号禁止修改邮箱
  - 新邮箱与当前邮箱相同不会报错，但不会触发任何变更
  - 邮箱验证开关关闭时无需验证码，但仍要求邮箱地址合法
last_verified: v1.7.90
---

# 修改邮箱

## 入口

- 桌面端：右上角头像 →「设置」→「修改邮箱」
- 移动端：「我的」→「设置」→「修改邮箱」
- 后端接口：`POST api/users/email/edit`

## 操作步骤

1. 输入「新邮箱地址」
2. 如系统开启了「注册邮箱验证」（`emailSetting.reg_verify = open`）：
   - 点击邮箱栏右侧「发送验证码」
   - 在「验证码」栏输入收到的 6 位验证码（场景类型 `2` = 改邮箱）
3. 点击「提交」
4. 弹「修改成功」即生效；下次登录用新邮箱

## 验证码相关

- 验证码由 `UserEmailVerification` 表存储，按邮箱 + 场景隔离
- 收不到邮件先查系统设置 → 邮件设置是否配好 SMTP
- 同一邮箱短时间内重复发送会限流

## 不开启邮箱验证时

- 系统设置里 `reg_verify` 关闭时邮箱栏不显示「发送验证码」，直接填新邮箱即可保存
- 仍会校验邮箱格式合法（`Base::isEmail`）

## 不支持

- 不支持手机号登录改邮箱二合一流程；邮箱是唯一登录凭证
- 不支持邮箱别名 / 多邮箱
