---
id: compliance.entry.menu-map
title: 合规设置入口在哪
type: menu-map
feature: compliance
scope: admin
locale: zh
aliases:
  - 合规设置在哪
  - 合规入口
  - GDPR 设置
  - 在哪配置合规
  - compliance 菜单
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
negative:
  - DooTask 主程序不提供独立的「合规设置」菜单
  - 合规相关能力分散在多个菜单下
  - 普通成员看不到任何合规相关后台
last_verified: v1.7.90
---

# 合规设置入口在哪

## 路径
DooTask **没有**单独的「合规设置」菜单，合规相关能力分散在以下入口：

| 合规维度 | 实际入口 | 角色 |
|---|---|---|
| 用户协议 / 隐私政策 | 「应用 → 系统设置 → 通用设置」 | 系统管理员 |
| 注册策略 | 「应用 → 系统设置 → 注册策略」 | 系统管理员 |
| 安全（密码强度） | 「应用 → 系统设置 → 安全设置」 | 系统管理员 |
| 内容审核 | 「应用 → 举报管理」 | 系统管理员 |
| 数据导出（可携性） | 「应用 → 数据导出」 | 系统管理员 |
| 账号删除（DSR） | 「应用 → 团队管理」选成员后删除 | 系统管理员 |
| LDAP / SSO | 「应用 → LDAP 集成」 | 系统管理员 |
| HTTPS / 加密传输 | 服务器 Nginx 配置 | 服务器运维 |
| 数据备份 | 服务器侧 `./cmd backup` 等脚本 | 服务器运维 |

## 权限要求
- 列表中产品内入口都要求 `userIsAdmin = true`
- 服务器层（HTTPS / 备份）需要 root 或 docker 权限
- 超级管理员（id=1）默认有全部产品权限

## 责任人建议
- **CTO / IT**：HTTPS、备份、数据本地化
- **系统管理员**：用户协议、注册策略、举报、账号管理
- **法务**：审定协议文本，监督流程

## 找不到具体功能怎么办
按维度查 [[compliance.howto]] 的检查清单，每一项都指向具体入口。

## 相关
- 概览：[[compliance.concept]]
- 配置项清单：[[compliance.howto]]
