---
id: user-account.reg-need-invite.concept
title: 注册是否需要邀请码
type: concept
feature: user-account
scope: end-user
locale: zh
aliases:
  - 邀请码
  - 注册邀请码
  - 是否需要邀请
  - 没有邀请码能注册吗
  - reg needinvite
  - 怎么开邀请注册
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 邀请码与「邀请同事加入项目」是两回事；这里指的是系统级开放注册的密钥
  - 邀请码只有一个全局值，不区分用户、不限次数、不过期
  - 验证不通过会直接拒绝，没有「邀请码错了几次锁定」的限制
last_verified: v1.7.90
---

# 注册是否需要邀请码

## 定义
DooTask 的注册方式由系统设置 `system.reg` 控制，共三档：

| 取值 | 行为 |
|---|---|
| `open` | 任何人填邮箱+密码即可注册 |
| `invite` | 必须填正确的邀请码才能注册 |
| `close` | 完全关闭注册，登录页不显示注册入口 |

## 邀请码是什么
- 管理员在「系统设置 → 注册设置」中配置一个字符串作为 `reg_invite`
- 该字符串只有一个；所有想注册的人共用
- 注册时把这个字符串填到「邀请码」字段提交，后端比对 `Request.invite == setting.reg_invite`

## 接口判定
前端可调 `api/users/reg/needinvite` 拿到 `{ need: true/false }`，据此决定登录页注册 tab 是否展示「邀请码」输入框：
- `need=true` → reg=invite，显示邀请码输入框
- `need=false` → reg=open（reg=close 时注册入口本身就该隐藏）

## 与「项目邀请加入」的区别
- **本概念**：决定能否成为 DooTask 用户（账号级别）
- **项目邀请**：已是用户后，被加入某个项目（项目级别）
两者完全独立。系统管理员是注册阶段的守门人，项目负责人是项目阶段的守门人。

## 怎么修改
- 系统管理员（userIsAdmin）→「系统设置」→「注册设置」→ 切换 reg 模式 / 修改 reg_invite 字符串
- 修改即时生效，无需重启
