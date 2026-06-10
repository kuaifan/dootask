---
id: role-permission.grant-admin.howto
title: 授予 / 取消系统管理员身份
type: howto
feature: role-permission
scope: super-admin
locale: zh
aliases:
  - 设为管理员
  - 给管理员权限
  - 添加管理员
  - setadmin
  - clearadmin
  - 取消管理员
  - 怎么让别人当管理员
related_tools: []
related_pages: []
prerequisites:
  - 操作账号必须已经是系统管理员（admin identity）
  - 目标账号必须存在且未被禁用
negative:
  - 不能给临时账号（temp identity）授予管理员身份前，请先取消其 temp 标记
  - 不能取消超级管理员（id=1）的管理员身份，超管始终自带 admin
  - 普通用户无法自助申请，必须由现任管理员授予
last_verified: v1.7.90
---

# 授予 / 取消系统管理员身份

## 入口
- 桌面端：右上角头像 → 「团队管理」 → 选中用户 → 「设为管理员」/「取消管理员」
- 移动端：移动端无团队管理入口，需在桌面端操作

## 操作步骤
1. 用系统管理员账号登录
2. 进入团队管理（左侧搜索 / 筛选用户）
3. 点击目标用户行的更多操作
4. 选择「设为管理员」或「取消管理员」
5. 后端调用 `POST api/users/operation`：
   - `setadmin`：把 `'admin'` 字符串添加到目标用户 `identity` 数组
   - `clearadmin`：从 `identity` 数组移除 `'admin'`

## 生效时间
- 后端立即生效（数据库直接更新 `users.identity`）
- 前端要等目标用户下次刷新或重新登录，`store.state.userIsAdmin` 才会更新
- WebSocket 在线用户会收到资料更新通知

## 谁能操作
- **超级管理员**：可操作任意用户（除自己）
- **系统管理员**：可操作其他用户，但不能操作超管（被 `checkSystem(1)` 拦截）
- **普通用户**：不可操作

## 与其它身份的关系
- `identity` 是字符串数组，可同时含 `admin`、`temp`、`disable` 等。授予 admin 不会清除其它标记
- 改 admin 身份不影响项目 / 任务级权限。详见 [[role-permission.admin.concept]]
- 想知道遇到「权限不足」该联系谁，见 [[role-permission.permission-denied.faq]]
