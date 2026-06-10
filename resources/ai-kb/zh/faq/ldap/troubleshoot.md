---
id: ldap.troubleshoot.faq
title: LDAP 连接 / 同步失败排查
type: faq
feature: ldap
scope: admin
locale: zh
aliases:
  - LDAP 连接失败
  - LDAP 同步失败
  - LDAP 登录不上
  - LDAP 验证失败
  - 测试 LDAP 报错
  - LDAP 用户登录不了
  - LDAP 用户没邮箱
  - LDAP 端口连不上
  - sAMAccountName 找不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 错误日志写到 PHP error_log（info 级别），不会直接弹给前端
  - 没有专门的 LDAP 测试用户接口，只能用「测试连接」验通管理员绑定
  - 部分 LDAPS 自签证书需要在容器内信任 CA，DooTask 主程序不自带证书管理
last_verified: v1.7.90
---

# LDAP 连接 / 同步失败排查

## 问题
出现以下任一现象：

- LDAP 设置页（左侧栏「应用」→ 管理员应用「LDAP」卡片）点「测试链接」提示「验证失败」
- 域账号登录报「账号或密码错误」，或提示「LDAP 用户缺少邮箱属性，请联系管理员配置」
- 后端日志出现 `[LDAP] auth fail` / `[LDAP] sync fail` / `[LDAP] update fail`

## 先检查配置（最常见）
打开 LDAP 设置页逐项核对：「LDAP 地址」（Host）、「LDAP 端口」（默认 389）、「Base DN」、「User DN」（绑定 DN）、「密码」、「登录属性」，改完点「测试链接」验证；并确认网络 / 防火墙放通 DooTask 到 LDAP 的端口。

注意：LDAP 用户在**首次登录 DooTask 时**才同步创建本地账号，没有批量预同步，成员列表暂时看不到 LDAP 用户属正常，见 [[ldap.sync.howto]]。

### 1. 管理员绑定失败（验证失败）
触发：「测试链接」报「验证失败」。
排查：
- LDAP 地址是否带了 `ldap://` 前缀？后端只接受纯域名/IP
- 端口对不对（明文 389 / TLS 636）
- User DN（管理员 DN）写完整 `cn=admin,dc=example,dc=com`（不是 `admin`）
- 防火墙是否放行 DooTask 容器到 LDAP 服务器
- LDAPS 自签证书要在容器内信任 CA

### 2. 用户搜不到（登录账号密码错误）
触发：测试连接通过但用户登录失败。
排查：
- 「登录属性」选错？AD 一般要选 `sAMAccountName`，OpenLDAP 一般 `uid` 或 `cn`
- Base DN 太窄，没覆盖到用户所在 OU
- 用户在 LDAP 实际禁用 / 锁定

### 3. 用户没邮箱（缺少邮箱属性）
触发：LDAP 认证通过但报「LDAP 用户缺少邮箱属性」。
原因：后端按顺序找 `mail / cn / uid / userPrincipalName`，至少一个须是合法邮箱格式。
解决：给用户补全 `mail` 属性，或把 `userPrincipalName` 设成 `user@domain.com` 形式。

### 4. 反向同步失败
触发：日志出现 `[LDAP] sync fail`。
排查：
- 管理员账号是否有写权限到 Base DN
- Schema 是否允许 `top + person` 这两个 objectClass（默认结构）
- 密码字段（`userPassword`）的 Hash 策略是否兼容

### 5. Swoole 协程导致连接被污染
后端用 `LdapRecord` 的全局 `Container`，认证完会主动恢复成管理员绑定。如果出现「时而成功时而失败」，执行 `./cmd php restart` 重启容器。

## 怎么看 LDAP 详细错误
后端把异常写入日志（`storage/logs/laravel.log` 或容器内 PHP error_log），过滤 `[LDAP]` 前缀即可看到 `auth fail / sync fail / update fail / delete fail` 等详细 message。

详细配置参考 [[ldap.config.howto]]，同步机制见 [[ldap.sync.howto]]。
