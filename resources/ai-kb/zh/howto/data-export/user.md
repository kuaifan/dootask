---
id: data-export.user.howto
title: 用户列表能不能批量导出
type: howto
feature: data-export
scope: admin
locale: zh
aliases:
  - 用户导出
  - 成员名单导出
  - 通讯录导出
  - 导出员工列表
  - 全员表
related_tools: [get_users_basic, search_users]
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（仅在替代方案中）
negative:
  - 主程序后台没有「用户/成员批量导出 Excel」功能
  - 没有 `api/users/export` 这样的接口
  - 不支持自助导出含敏感字段（手机号、邮箱）的用户表
last_verified: v1.7.90
---

# 用户列表能不能批量导出

## 结论
DooTask 主程序**没有**用户列表批量导出功能。管理员后台「数据导出」仅支持任务统计、超期任务、审批、签到 4 类，不包含用户/成员名单。

## 替代方案

1. **API 拉取**
   - `api/users/searchinfo` 按昵称 / 邮箱搜索
   - MCP 工具 `search_users`、`get_users_basic`（受权限管控）
   - 适合脚本或机器人批量获取

2. **LDAP 同步反向导出**
   - 如果用户来自 LDAP，可直接从 AD/LDAP 服务器导出员工目录

3. **数据库导出**
   - 仅 DBA：从 `users`、`user_departments` 表按需导出
   - 必须遵守内部合规规范

## 隐私和合规
- 用户表含手机号 / 邮箱 / 部门等敏感字段
- 即使是系统管理员，导出全员通讯录也应有内部审批
- 建议参考 [[compliance.concept]] 的数据保留与最小化原则

## 不支持
- 不支持产品 UI 批量导出全员 Excel
- 不支持普通成员获取全员表

## 相关
- 数据导出概览：[[data-export.concept]]
- 入口：[[data-export.entry.menu-map]]
