---
id: app-admin.ldap.howto
title: LDAP 应用入口
type: howto
feature: app-admin
scope: admin
locale: zh
aliases:
  - LDAP 在哪
  - LDAP 设置怎么打开
  - 域账号同步
  - 接入 AD
  - 第三方账号集成
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - LDAP 配置错误不会回滚，须在保存前自行核对
  - 单次同步用户上限以 LDAP 服务端策略为准，不在 DooTask 设置
last_verified: v1.7.90
---

# LDAP 应用入口

## 是什么
LDAP 应用让管理员配置 AD / OpenLDAP / 通用 LDAP 目录服务，把域账号同步成 DooTask 用户，支持 LDAP 登录。属于管理员应用，普通成员看不到入口。

## 入口
- 桌面端：左侧栏「应用」→ 下方「管理员」分区 →「LDAP」卡片
- 移动端竖屏：底部 Tabbar「应用」→「管理员」分区 →「LDAP」
- 点击后右侧弹出 700 宽的「LDAP 设置」抽屉

## 抽屉内能做什么
- 配置 LDAP 服务器地址、端口、绑定 DN、密码
- 设置用户搜索基准 DN 与过滤器
- 设置属性映射（uid / mail / displayName 等 → DooTask 字段）
- 启用 / 关闭 LDAP 登录开关
- 手动触发一次全量同步、查看同步日志

## 详细配置
LDAP 各字段含义、测试连接步骤见 ldap feature 的 chunk（后续起草）。

## 不支持
- 不支持多 LDAP 源并存（同一时间只能一组配置）
- 同步只新增 / 更新用户，不会删除已离职的域账号；需手动清理
