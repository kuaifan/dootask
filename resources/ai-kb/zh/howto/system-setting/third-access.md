---
id: system-setting.third-access.howto
title: 第三方接入（LDAP 入口）
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 第三方接入
  - LDAP 设置
  - AD 接入
  - SSO
  - 单点登录
  - 配置 LDAP
  - 同步本地用户
  - 测试 LDAP
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - LDAP / AD 服务可达
negative:
  - 当前「第三方接入」仅集成 LDAP，没有 OAuth / SAML / 钉钉 / 飞书的同义入口
  - SYSTEM_SETTING=disabled 时禁止保存
  - 测试连接（testldap）若管理员 DN 或密码错误，前端只会显示「验证失败」，不会带具体 LDAP 错误码
last_verified: v1.7.90
---

# 第三方接入（LDAP 入口）

## 入口
桌面端：左上角头像 →「系统设置」→「第三方接入」。
对应后端：`POST api/system/setting/thirdaccess`，参数 `type` 可取 `save` / `testldap`。

## 字段说明

- **ldap_open** — LDAP 总开关（`open` / `close`）
- **ldap_host** — LDAP 主机地址（无 schema，如 `ldap.example.com`）
- **ldap_port** — 端口（默认 389，LDAPS 一般 636）
- **ldap_user_dn** — 管理员绑定 DN（如 `cn=admin,dc=example,dc=com`）
- **ldap_password** — 管理员密码
- **ldap_base_dn** — 搜索基准 DN（如 `ou=users,dc=example,dc=com`）
- **ldap_login_attr** — 登录用的属性名，枚举 `cn` / `uid` / `mail` / `sAMAccountName` / `userPrincipalName`，默认 `cn`
- **ldap_sync_local** — 本地用户反向同步到 LDAP（`open` / `close`，默认关）

服务端把这些字段存到 `setting` 表 `thirdAccessSetting` 分组。其他键自动剔除。

## 操作步骤（保存配置）
1. 进入「第三方接入」页
2. 「LDAP 开关」选 `open`
3. 依次填写 host / port / 管理员 DN / 密码 / Base DN
4. 选择登录属性（与 AD 对接通常用 `sAMAccountName`）
5. 点「测试」按钮触发 `testldap` 类型请求（不入库，仅试探绑定）
6. 测试通过后点「保存」

## 测试连接（testldap）
后端用提交的字段动态构造连接 → 尝试 `bind(user_dn, password)`：
- 成功返回「验证通过」
- 失败返回「验证失败」+ LDAP 异常信息（如有）

详细配置语义见 [[ldap.config.howto]]，使用细节见 [[ldap.concept]]。

## 不支持
- 没有 OAuth / SAML / OIDC 入口
- 不支持多 LDAP 域（只有一个 Default Connection）
- 「同步本地用户到 LDAP」仅在本地用户登录后触发，不主动批量推送
