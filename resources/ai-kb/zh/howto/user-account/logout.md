---
id: user-account.logout.howto
title: 退出登录
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 退出登录
  - 退出账号
  - 怎么登出
  - 注销登录
  - 切换账号
  - 退出 DooTask
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 退出登录只清除当前设备的 token / 设备记录，不影响其它设备
  - 退出登录不是注销账号；账号还在，下次还能登录（注销见 [[user-account.delete.howto]]）
  - 退出后未发送/草稿消息不会保留
last_verified: v1.7.90
---

# 退出登录

## 入口
- 桌面端：右上角头像 → 下拉菜单 →「退出登录」
- 移动端：底部 Tabbar「我的」→ 顶部头像区下方 →「退出登录」
- 客户端：同桌面端

## 操作步骤
1. 点「退出登录」，弹确认对话框
2. 确认后调用 `api/users/logout`，清除当前设备 token
3. 自动跳到登录页

## 退出后做了什么
- 当前设备的 `UserDevice` 记录被删除（device hash 失效）
- 当前 token 不再可用；继续用旧 token 调接口会返回 401
- 其它设备的登录不受影响（要全设备登出请用 [[user-account.device.howto]] 逐个登出）

## 切换账号
退出后直接在登录页用其它邮箱登入即可。DooTask 不支持「同时登录多个账号」，桌面端可用多个浏览器配置或客户端用「多开」入口实现多账号并行。

## 不支持
- 不支持「保持登录」勾选项；token 有效期由管理员在系统设置统一配置（默认 30 天）
- 不支持「退出后保留聊天草稿」
