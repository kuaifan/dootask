---
id: user-account.info.concept
title: 用户信息字段
type: concept
feature: user-account
scope: end-user
locale: zh
aliases:
  - 用户信息
  - 个人信息字段
  - 我的资料
  - userid
  - identity
  - 部门字段
related_tools: [get_users_basic]
related_pages: []
prerequisites: []
negative:
  - 邮箱（email）不能自助修改；如需换邮箱只能注销后重新注册（[[user-account.delete.howto]]）
  - 昵称必须 2-20 字；< 2 提示「昵称不可以少于2个字」，> 20 提示「昵称最多只能设置20个字」
  - 联系电话长度 6-20，且全系统不可重复
  - 个人简介 ≤ 500 字，地址 ≤ 100 字，职位 2-20 字
last_verified: v1.7.90
---

# 用户信息字段

## 定义
DooTask 用户对象由 `api/users/info` 返回，包含账号、身份、个人资料三类字段。`info__departments` 单独返回当前用户的部门列表。

## 关键字段
| 字段 | 含义 | 是否可改 |
|---|---|---|
| `userid` | 用户 ID（自增主键） | 否，注册即定 |
| `email` | 登录邮箱 | 否，自助不可改 |
| `nickname` | 昵称（2-20 字） | 是，editdata |
| `userimg` | 头像 URL | 是，editdata |
| `tel` | 联系电话（6-20，全局唯一） | 是，editdata |
| `profession` | 职位/职称（2-20 字） | 是，editdata |
| `birthday` | 生日（YYYY-MM-DD） | 是，editdata |
| `address` | 地址（≤ 100 字） | 是，editdata |
| `introduction` | 个人简介（≤ 500 字） | 是，editdata |
| `lang` | 界面语言（zh/en/...） | 是，editdata |
| `identity` | 身份标记数组（admin/ldap/temp/...） | 否，系统设定 |
| `department` | 所属部门 ID 数组 | 否，由管理员调整 |
| `department_name` | 部门名（拼接） | 否，由 department 推导 |
| `department_owner` | 是否默认部门下第一级负责人 | 否 |
| `managed_departments` | 可切换负责人视角的部门 | 否 |
| `last_ip` / `last_at` | 最近一次登录的 IP / 时间 | 自动写 |
| `line_ip` / `line_at` | 最近一次活跃 IP / 时间 | 自动写 |
| `login_num` | 登录次数 | 自动累加 |
| `changepass` | 是否需在下次登录强制改密码 | editpass 后清零 |
| `email_verity` | 邮箱是否已验证（1/0） | 邮箱验证后置 1 |

## identity 常见值
- `admin`：系统管理员
- `ldap`：LDAP 同步过来的账号
- `temp`：临时账号
- `bot`：机器人账号
- `system`：系统/演示账号（受保护）

## 与「部门」的关系
- 一个用户可属多个部门
- `info__departments` 返回最多 10 个部门，且把「当前用户作为负责人的部门」排在最前
- 部门管理在系统管理员的「团队管理 → 部门管理」中维护
