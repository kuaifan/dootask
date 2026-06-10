---
id: role-permission.super-admin.concept
title: 超级管理员（id=1）
type: concept
feature: role-permission
scope: end-user
locale: zh
aliases:
  - 超管
  - 超级管理员
  - 谁是超管
  - 第一个用户
  - id 为 1 的用户
  - 主账号
  - owner 账号
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 系统中有且只有一个超级管理员（不能同时存在多个）
  - 超管账号不能被删除，也不能被其他管理员设为离职
  - 超管不能取消自己的管理员身份
  - 普通用户和系统管理员都无法把自己升为超管
last_verified: v1.7.90
---

# 超级管理员（id=1）

## 定义
超级管理员是 DooTask 实例中**第一个注册的用户**，对应数据库 `users.userid = 1`。整个系统只允许存在一个超管。

## 关键属性
- **判定方式**：`userid === 1`，与 identity 字段无关；超管同时自带 `admin` identity
- **唯一性**：系统中始终只有一个超管，新装实例由首次启动时创建
- **不可降级**：自己无法取消超管身份；其他人也改不了
- **环境保护**：当后端环境变量 `PASSWORD_ADMIN=disabled` 时，超管的部分敏感操作（如改密、改邮箱）会被禁止

## 专属能力（系统管理员也做不了）
- 修改超管自身设置（密码、邮箱、昵称等无人能代改）
- 不能被「设为离职」「删除会员」操作（后端 `checkSystem(1)` 拦截）
- 是系统级数据迁移、转让的兜底身份

## 转让超管
超管可以把超管身份转让给另一个账号。转让会同步迁移部门、项目、任务、文件归属，参考 [[role-permission.transfer-owner.howto]]。

## 与系统管理员的区别
- 超管 = 系统里第一个用户（id=1），唯一
- 系统管理员 = identity 含 `admin` 的所有用户，可以有多个，由超管或其他管理员授予
- 详见 [[role-permission.admin.concept]]
