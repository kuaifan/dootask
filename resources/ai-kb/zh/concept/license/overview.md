---
id: license.concept
title: License Key 是什么
type: concept
feature: license
scope: super-admin
locale: zh
aliases:
  - License
  - 授权码
  - 许可证
  - License Key 是什么
  - 多少人能用
  - 用户上限
  - 终端授权
  - 怎么算授权
  - SN 是什么
  - 绑定 MAC
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限才能查看
  - 仅超级管理员能保存 License
negative:
  - 3 人以下的部署不强制 License（不绑 SN / MAC，但仍受人数限制）
  - 一份 License 不能拆给多个 DooTask 终端共用
  - 过期或人数超限不会立刻锁死功能，但会在管理端持续报错提示
last_verified: v1.7.90
---

# License Key 是什么

## 定义
License Key 是 DooTask 终端的授权凭证，决定一个部署允许多少注册用户、绑定哪台机器、有效期到何时。它是一段加密字符串，由官方根据「终端 SN + MAC + 人数 + 过期时间」签发。

后端通过 `api/system/license` 接口读写，存储在主程序根目录的 License 文件中（由 `Doo::licenseSave/licenseContent` 管理）。

## 关键属性

- **license（content）** — License 原文字符串
- **info.people** — 允许的最大用户数；0 表示无限制
- **info.sn** — 授权绑定的终端 SN
- **info.mac** — 授权允许的 MAC 列表（数组）
- **info.expired_at** — 过期时间（字符串，空字符串/0 表示永久）
- **doo_sn / doo_version** — 当前终端的 SN 与主程序版本
- **macs** — 当前服务器实际网卡 MAC 列表
- **user_count** — 当前非机器人、未禁用的活跃用户数

## 与其他概念的关系

- **小团队豁免**：`info.people <= 3` 时不校验 SN / MAC，相当于「3 人内永久免费」
- **超额提示**：`user_count > info.people` 时返回 `error: 终端用户数超过License限制`
- **绑定校验**：SN 不匹配 → `终端SN与License不匹配`；MAC 不在白名单 → `终端MAC与License不匹配`
- **过期校验**：当前时间 > `expired_at` → `终端License已过期`

## 使用场景
- 申请新的 License：见 [[license.howto]]
- 处理过期或失效：见 [[license.expire.faq]]
- 管理后台「License」页会汇总 `error` 数组，展示所有不满足的规则
