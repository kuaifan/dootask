---
id: common-faq.sync-ldap.faq
title: LDAP 用户 / 部门同步不到位
type: faq
feature: common-faq
scope: admin
locale: zh
aliases:
  - LDAP 同步不全
  - 域账号没拉过来
  - 部门同步缺失
  - AD 用户少了
  - LDAP 改了 DooTask 没更新
  - LDAP 单向同步
related_tools: []
related_pages: []
prerequisites:
  - 已配置 LDAP 接入（系统设置 → 第三方接入）
negative:
  - LDAP 默认是「登录时同步该用户」，不是定时全量同步
  - LDAP 删除用户不会自动联动注销 DooTask 账号
  - 没有「立即全量同步」按钮，只能靠用户登录触发
last_verified: v1.7.90
---

# LDAP 用户 / 部门同步不到位

## 问题
- LDAP / AD 里加了新部门 / 新员工，DooTask 看不到
- LDAP 改了员工部门，DooTask 还是旧值
- LDAP 禁用 / 删除了用户，DooTask 这边账号还能登录

## 原因
DooTask 的 LDAP 集成是**按需拉取**模式，不是定时全量：

- 用户登录时，按账号去 LDAP 验证 → 验证通过则同步该用户基础信息
- 部门结构按用户的 DN 推断，没人登录就不会创建新部门
- LDAP 删用户后，DooTask 仍保留账号（避免误删历史数据）

这套设计目的是**避免大批量同步阻塞**和保留**离职员工的历史关联**。

## 解决

**新用户不出现**
让该用户**至少登录一次** → 自动同步进 DooTask；或管理员手动「邀请」走预占。

**部门 / 邮箱信息过期**
让用户**重新登录一次** → 后端会更新 `users` 表的 email / nickname / department。

**LDAP 删除的用户仍能登录**
- 用户下次登录时 LDAP 会拒绝，DooTask 同步失败该用户登录失败
- 想立刻封号：管理员后台「成员管理」手动禁用 / 注销
- 想批量清理：管理员通过数据库或 API 批量禁用

**想立即全量同步**
没有自带按钮。可选：
- 等用户自然登录
- 写脚本调用 LDAP + DooTask API 批量预创建
- 重启 Swoole 容器并不会触发同步，重启没用

## 相关
- LDAP 排错：[[ldap.troubleshoot.faq]]
- LDAP 配置：[[ldap.config.howto]]
