---
id: push-notice.alias.concept
title: 友盟 Alias 用户绑定
type: concept
feature: push-notice
scope: end-user
locale: zh
aliases:
  - UmengAlias
  - 友盟别名
  - 设备绑定
  - 推送绑定
  - 怎么收到推送
  - 推送目标
related_tools: []
related_pages: []
prerequisites:
  - 管理员已开启 APP 推送
  - 用户在移动端 APP 已登录
negative:
  - Web 端 / 桌面端不写 UmengAlias 表，因此不通过友盟推送
  - 别名 30 天未活跃即不再用于推送（updated_at 过期）
  - 调试模式（isDebug=1）的 APP 不会注册别名，避免污染生产推送
last_verified: v1.7.90
---

# 友盟 Alias 用户绑定

## 定义
`umeng_alias` 是 DooTask 用来记录「某用户的某台移动设备应该接收哪些推送」的绑定表。每次移动 APP 启动登录后会调用 `api/users/umeng/alias` 注册或更新这条记录，后端按 `userid → alias` 反向查到所有活跃设备来发推送。

## 关键字段
| 字段 | 说明 |
|---|---|
| userid | DooTask 用户 ID |
| alias | 友盟 alias，用户在友盟侧的唯一标识 |
| platform | ios / android（其他平台调用会被拒） |
| device | 设备型号 |
| device_hash | 设备指纹，关联 UserDevice 表 |
| version | APP 版本号（含 versionName） |
| ua | userAgent |
| is_notified | 系统通知权限（0=未授予 / 1=已授予） |
| updated_at | 最后活跃时间；超过 30 天的别名不再被推送选中 |

## 注册逻辑
APP 启动 → 调 `api/users/umeng/alias?action=update`：
1. 校验 platform 必须是 ios / android
2. 校验 alias 长度 2-20 字符
3. 校验 isDebug=0（调试模式拒绝）
4. 表里同 `userid + alias + platform` 存在则更新；不存在则插入

退出登录时可调 `action=remove` 删别名。

## 多设备规则
- 同一 userid 可绑定多台设备，每个设备一条记录
- 推送时按 platform 分组，每个用户每平台最多取最近 5 个 alias 一起发（按 updated_at 倒序）
- 角标数取自该用户未读且非静默消息总数（WebSocketDialogMsgRead）

## 解绑
- 用户卸载 APP 后系统不会主动删除别名记录；该别名后续推送会在友盟侧失败，不影响其他设备
- 30 天未活跃自动从推送目标中剔除（但记录仍在表里）
- 管理员可在数据库直接清理 `umeng_alias`
