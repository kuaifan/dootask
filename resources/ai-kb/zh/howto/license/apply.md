---
id: license.howto
title: 申请与录入 License
type: howto
feature: license
scope: super-admin
locale: zh
aliases:
  - 申请 License
  - 录入 License
  - 怎么填 License
  - 提交 License
  - 上传授权
  - License 填哪里
  - 终端绑定
  - 怎么扩容用户数
related_tools: []
related_pages: []
prerequisites:
  - 需要进入「系统设置」→「License」页面（仅管理员可见）
  - 保存 License 仅超级管理员（第一个注册用户）可执行
negative:
  - 不能在终端外部直接编辑 License 文件，必须走管理端 API
  - 一份 License 仅对当前终端的 SN + MAC 有效，换机或换网卡需重新申请
  - 不支持把 License 拆给多个独立部署共享
last_verified: v1.7.91
---

# 申请与录入 License

> 本文介绍**离线授权**（手动粘贴 License 原文）。License 页现有「离线授权 / 在线授权」两个 Tab，
> 用 App Store 账号登录自助签发并自动续期的方式见 [[license.online.howto]]。

## 入口
桌面端：左上角头像 →「系统设置」→「License」→「离线授权」Tab（仅管理员可见）。
对应后端：`POST api/system/license`，`type=save` 写入。

## 操作步骤

### 第 1 步 - 获取当前终端信息
在「License」页面顶部，DooTask 会显示：

- **doo_sn** — 终端 SN（机器指纹，每次部署生成）
- **macs** — 当前服务器网卡 MAC 列表（用于绑定校验）
- **doo_version** — 当前主程序版本号
- **user_count** — 当前真实用户数（剔除机器人和禁用号）

这些字段是申请 License 时必须提供给签发方的信息。

### 第 2 步 - 申请 License
访问 DooTask 官网（或销售渠道）提交：
- 终端 SN（必填）
- 终端 MAC（一张或多张）
- 期望的最大用户数
- 期望的有效期
- 是否需要按 SN 严格绑定

收到 License 原文（加密字符串）后进入下一步。

### 第 3 步 - 录入 License
1. 把官方返回的 License 原文整段复制
2. 粘贴到「License」页的输入框
3. 点击「保存」（接口字段 `license`，调用 `type=save`）
4. 后端用 `Doo::licenseSave()` 写入终端 License 文件
5. 页面自动刷新校验结果：`info` 字段重新解析，`error` 数组为空表示通过

## 校验结果解读
返回结构里 `error` 数组列出当前不满足的规则：

- `终端SN与License不匹配` — License 对应的 SN ≠ 当前 doo_sn（多发生在换机迁移）
- `终端MAC与License不匹配` — License MAC 名单与本机网卡无交集
- `终端用户数超过License限制` — `user_count > people`，需要扩容或停用账号
- `终端License已过期` — `expired_at` 已经过去

详细处理见 [[license.expire.faq]]。

## 不支持
- 普通管理员能看 License 信息但不能保存；只有超级管理员（id=1）能 save
- 不支持上传文件方式，仅接受文本字段
- 3 人以下的部署不强制 License，但用户数严格限制为 3
