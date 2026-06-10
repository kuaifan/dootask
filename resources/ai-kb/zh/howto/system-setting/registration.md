---
id: system-setting.registration.howto
title: 注册策略设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 注册开关怎么开
  - 允许注册
  - 禁止注册
  - 邀请码注册
  - 邀请码在哪改
  - 临时帐号
  - 新用户身份
  - reg_invite
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 注册策略只有 open / close / invite 三档，不支持按邮箱域名白名单
  - 邀请码留空保存后系统会自动生成一个随机码，不能完全关闭"凭码可注册"模式
  - 临时帐号一旦创建无法在线一键转为正常帐号
last_verified: v1.7.90
---

# 注册策略设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「帐号相关」→「允许注册」。

涉及字段：
- `reg` — 注册策略
- `reg_identity` — 新注册用户身份
- `reg_invite` — 邀请码
- `temp_account_alias` — 临时帐号别名

## reg：注册策略

| 取值 | 含义 |
|---|---|
| `open` | 允许任何人在登录页注册 |
| `invite` | 仍可注册，但注册时必须填邀请码 |
| `close` | 禁止注册，登录页不显示注册入口 |

`reg=invite` 时下方显示「邀请码」输入框，对应 `reg_invite`。留空保存后系统会自动生成一个随机邀请码（用 `Base::generatePassword()`），不会真的"无码可用"。

## reg_identity：新注册用户身份
仅在 `reg` 为 `open` 或 `invite` 时显示。

| 取值 | 含义 |
|---|---|
| `normal` | 正常帐号，权限与已有成员一致 |
| `temp` | 临时帐号，受 5 项限制：禁止查看共享文件、禁止发起会话、禁止建群、禁止打电话、禁止打包下载文件 |

选 `temp` 时下方可填 `temp_account_alias`（临时帐号别名），用于在用户列表展示区别。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「帐号相关」
2. 选「允许注册」的三选一（同时配套填邀请码或注册身份）
3. 「提交」保存，立即生效

## 不支持
- 不能按邮箱后缀白名单 / 黑名单
- 不能给同一系统配多套邀请码，邀请码全局唯一
- 不能区分电脑端 / 移动端的注册开关
