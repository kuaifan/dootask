---
id: common-faq.account-locked.faq
title: 账号被锁定 / 频繁报「登录次数过多」
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 账号锁定
  - 登录次数过多
  - 账号被冻结
  - 被禁用
  - 输错密码被锁
  - 解锁账号
  - 登录受限
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 普通用户没有「解锁」自助按钮，要等冷却或联系管理员
  - 锁定不区分 IP，是按账号锁
  - LDAP 账号锁定可能源自 LDAP 服务器侧，不是 DooTask 锁的
last_verified: v1.7.90
---

# 账号被锁定 / 频繁报「登录次数过多」

## 问题
登录时报：「登录失败次数过多，请稍后再试」「账号已被锁定」「账号已被禁用」。

## 锁定的两种类型

**频次限制（自动锁定）**
后端对登录接口有频次保护：短时间多次密码错误触发临时锁定。
- 一般冷却 5-15 分钟后自动解除
- 输入正确密码也暂时拒登
- 全平台生效（换浏览器没用）

**管理员主动禁用**
管理员在「成员管理」把账号标记为禁用。
- 永久生效，直到管理员手动启用
- 提示通常是「账号已被禁用」或「无权登录」

## 解决

**频次锁定**
1. 等待 5-15 分钟
2. 建议先用「忘记密码」重置确认密码正确 → [[common-faq.account-forget-password.faq]]
3. 用正确密码登录

**管理员禁用**
- 联系系统管理员，说明账号 / 提示语 / 是否触发风控
- 管理员在「成员管理」点「启用」即可恢复

**LDAP 用户**
- 域账号可能是 LDAP 服务器侧锁了，找公司 IT 解锁

**联系不到管理员**
- 超级管理员（首位注册用户）可登服务器走 artisan 改库
- 普通用户没有兜底，只能等管理员

## 不支持
- 没有「立刻解锁」自助入口
- 没有按 IP 分桶（不能换 IP 绕开）
- 锁定时长不可配

## 相关
- 登不进去：[[common-faq.account-cant-login.faq]]
- 多端冲突：[[common-faq.account-multi-device-conflict.faq]]
