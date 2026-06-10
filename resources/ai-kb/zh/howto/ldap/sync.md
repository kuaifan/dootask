---
id: ldap.sync.howto
title: 同步 LDAP 用户
type: howto
feature: ldap
scope: admin
locale: zh
aliases:
  - 同步 LDAP
  - LDAP 同步用户
  - 同步本地到 LDAP
  - 用户怎么进来
  - LDAP 怎么把人导进来
  - 双向同步
  - LDAP 同步部门
related_tools: []
related_pages: []
prerequisites:
  - 已完成 LDAP 配置并测试通过
  - 「LDAP 开关」为 open
negative:
  - DooTask 不主动批量拉取 LDAP 用户，所有同步都是登录 / 注册时按需触发
  - 不支持把 LDAP 的组织架构（OU）一键导入到 DooTask 部门表
  - 反向同步只在「ldap_sync_local=open」时生效
  - 本地用户改密码后不会自动同步密码到 LDAP（密码字段写入仅在用户首次反向同步时）
last_verified: v1.7.90
---

# 同步 LDAP 用户

## 同步模式
DooTask 的 LDAP 同步是**被动 + 按需**两种：

1. **LDAP → DooTask（默认）**：用户在 DooTask 登录页输入域账号 + 密码 → 后端经 LDAP 认证成功 → 自动建/合并本地账号
2. **DooTask → LDAP（可选）**：开关 `ldap_sync_local=open` 时，本地用户首次登录会反向把账号写到 LDAP

两种模式互不阻塞，可同时启用。

## LDAP → DooTask（自动）
触发：用户在 DooTask 登录页输入域账号 + 密码。

流程（`LdapUser::userLogin`）：

1. 用管理员 Bind 在 Base DN 下搜索 `loginAttr=用户输入用户名`
2. 找不到 → 登录失败
3. 找到 → 用「entry 的 DN + 用户输入的密码」二次 Bind
4. 二次 Bind 成功 → 从 entry 抽取邮箱（按 `mail` → `cn` → `uid` → `userPrincipalName` 顺序）
5. 邮箱为空 → 抛 `ApiException('LDAP 用户缺少邮箱属性，请联系管理员配置')`
6. 邮箱存在 → 本地按 email 查 user：
   - 不存在 → `User::reg(email, 随机密码)` 自动注册
   - 存在但不是 LDAP 用户 → 在本地账号上打 `ldap` identity，与 LDAP 合并
7. 同步 LDAP entry 的 `displayName` 到 `user.nickname`
8. 同步 LDAP entry 的 `jpegPhoto` 到 `uploads/user/ldap/{userid}.jpeg`

## DooTask → LDAP（反向）
触发：本地用户登录或注册时，且 `ldap_sync_local=open`。

流程（`LdapUser::userSync`）：

1. 跳过已是 LDAP 身份的用户
2. 用本地 email 在 LDAP 找 entry，已存在则不重复写
3. 不存在则在 LDAP 根据 Base DN 创建 entry，属性包含：
   - `cn / sn / uid / mail = email`
   - `userPassword = 用户当前密码（仅本次有效，下次改密码不会自动同步）`
   - `displayName = nickname`
   - `jpegPhoto = 当前头像二进制（若存在）`
4. 本地账号加上 `ldap` identity 表示已纳管

## 同步不到用户怎么办
1. 核对 LDAP 设置（「应用」→ 管理员应用「LDAP」）：地址、端口、Base DN、User DN 及密码，点「测试链接」验证
2. 确认网络 / 防火墙放通 DooTask 到 LDAP 的端口
3. LDAP 用户在**登录时**才同步创建，不是批量预同步；让用户用域账号登录一次即可出现
4. 详细排查见 [[ldap.troubleshoot.faq]]

## 不支持
- 没有「同步所有 LDAP 用户到 DooTask」的批量按钮
- 没有「同步部门 / OU」功能
- 不同步成员关系、群组、角色
- 不会自动执行删除 / 更新：`userDelete / userUpdate` 需要主程序业务流程主动调用
