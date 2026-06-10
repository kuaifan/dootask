---
id: ldap.config.howto
title: 配置 LDAP
type: howto
feature: ldap
scope: admin
locale: zh
aliases:
  - 配置 LDAP
  - 接入 LDAP
  - LDAP 怎么填
  - LDAP 参数
  - 接入 AD
  - LDAP host
  - LDAP 端口
  - 登录属性
  - 测试 LDAP 连接
  - LDAP 验证失败
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 已部署可达的 LDAP / AD
  - 已准备好管理员 Bind DN + 密码 + Base DN
negative:
  - SYSTEM_SETTING=disabled 时禁止保存配置
  - 端口未填或非法时会被强制设为 389
  - 登录属性枚举受限，不在白名单内的值会回退为 cn
  - 测试连接接口只对当前请求生效，不会落库
last_verified: v1.7.90
---

# 配置 LDAP

## 入口
桌面端：左上角头像 →「系统设置」→「第三方接入」（LDAP 配置区在此）。
对应后端：`POST api/system/setting/thirdaccess`，`type` 可取 `save` / `testldap`。

## 字段清单

| 字段 | 含义 | 备注 |
|---|---|---|
| ldap_open | 总开关 | open / close |
| ldap_host | 服务器域名/IP | 不带 schema |
| ldap_port | 端口 | 默认 389（LDAPS 一般 636）|
| ldap_user_dn | 管理员 Bind DN | 如 cn=admin,dc=example,dc=com |
| ldap_password | 管理员密码 | 不可明文展示在前端 |
| ldap_base_dn | 搜索基准 DN | 如 ou=users,dc=example,dc=com |
| ldap_login_attr | 登录用属性 | cn / uid / mail / sAMAccountName / userPrincipalName |
| ldap_sync_local | 本地→LDAP 反向同步 | open / close，默认 close |

只接收上述字段，其他键会被服务端剔除。

## 操作步骤

1. 进入「第三方接入」
2. 「LDAP 开关」选 `open`
3. 依次填写 host / port / 管理员 DN + 密码 / Base DN
4. 选择登录属性：
   - 标准 OpenLDAP：通常 `cn` 或 `uid`
   - Windows AD：通常 `sAMAccountName`（用户登录名）或 `userPrincipalName`（user@domain）
5. 是否同步本地：本地账号反向写入 LDAP 时打开（仅在登录/注册触发，不主动批量推送）
6. 点击「测试」按钮（前端调用 `type=testldap`）：
   - 不入库，只用当前表单字段尝试用管理员 DN + 密码做 Bind
   - 成功 →「验证通过」
   - 失败 →「验证失败」（带 LDAP 异常）
7. 测试通过后点击「保存」（`type=save`）

## 测试连接细节
`type=testldap` 调用 `LdapRecord` 的 `Container::getDefaultConnection()` 临时设置参数 → `auth()->attempt(user_dn, password)`：
- 成功：返回成功消息
- 失败：捕获 `LdapRecordException` 返回错误消息 + 默认连接配置（便于排查）

具体排错见 [[ldap.troubleshoot.faq]]。

## 不支持
- 不能配置多个 LDAP 域
- 测试连接只能用管理员账号验通绑定，无法替用户名验通
- 没有「指定 OU 同步过滤」更细的 LDAP filter 字段
