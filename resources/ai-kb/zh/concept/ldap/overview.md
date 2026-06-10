---
id: ldap.concept
title: LDAP 集成是什么
type: concept
feature: ldap
scope: admin
locale: zh
aliases:
  - LDAP
  - AD 登录
  - Active Directory
  - 域账号登录
  - 企业目录
  - LDAP 集成
  - LDAP 是什么
  - 单点登录 LDAP
related_tools: []
related_pages: []
prerequisites:
  - 已部署可达的 LDAP / AD 服务
negative:
  - 不支持 OAuth / SAML / OIDC（这页只讲 LDAP）
  - 不支持多 LDAP 域，只能配置 1 个 default connection
  - LDAP 用户没邮箱属性就无法首次登录（会抛「LDAP 用户缺少邮箱属性」）
  - LDAP 用户密码不存到本地，本地密码用随机串占位
last_verified: v1.7.90
---

# LDAP 集成是什么

## 定义
LDAP（Lightweight Directory Access Protocol）集成让 DooTask 用企业已有的 LDAP / Active Directory 账号体系做认证。开启后用户在登录页输入企业域账号 + 密码，DooTask 通过 LDAP 协议向目录服务器认证，认证成功后在本地自动创建或合并账号。

实现位于 `app/Ldap/LdapUser.php`，依赖 `directorytree/ldaprecord` 库。设置存在 `setting` 表的 `thirdAccessSetting` 分组。

## 关键属性

- **ldap_open** — 总开关；非 `open` 时所有 LDAP 调用直接 short-circuit 返回
- **ldap_host / ldap_port** — 目录服务器地址（端口默认 389）
- **ldap_user_dn / ldap_password** — 管理员 Bind DN 与密码，用于搜索用户
- **ldap_base_dn** — 搜索基准 DN，限定查找范围
- **ldap_login_attr** — 登录属性，可选 `cn` / `uid` / `mail` / `sAMAccountName` / `userPrincipalName`，默认 `cn`
- **ldap_sync_local** — 本地账号反向写入 LDAP 的开关

## 工作流程

1. 用户在 DooTask 登录页输入企业账号 + 密码
2. 后端用管理员 Bind 搜索 `loginAttr=用户名` 的 entry
3. 拿到该 entry 的真实 DN，用「DN + 用户输入的密码」二次 Bind
4. Bind 成功 → 从 entry 中提取邮箱（按 `mail / cn / uid / userPrincipalName` 顺序）
5. 本地按 email 查找用户：找不到则注册（本地密码随机），找到则合并
6. 同步昵称、头像（`jpegPhoto` 字段）到本地账号

## 与其他概念的关系

- **本地账号**：本地账号若没 `ldap` identity，被 LDAP 用户合并时会打上 `ldap` 标
- **同步本地**（`ldap_sync_local=open`）：本地用户登录或注册时反向把账号写到 LDAP，便于统一管控
- **Swoole 协程**：连接在容器中共享，认证成功后会立刻还原成管理员绑定，避免污染下一个请求

配置入口见 [[system-setting.third-access.howto]] 或 [[ldap.config.howto]]。
