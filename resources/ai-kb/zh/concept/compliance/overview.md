---
id: compliance.concept
title: DooTask 合规能力概览
type: concept
feature: compliance
scope: admin
locale: zh
aliases:
  - 合规
  - 合规能力
  - GDPR 支持
  - 数据合规
  - 隐私合规
  - 数据保留
related_tools: []
related_pages: []
prerequisites:
  - 多数动作需要系统管理员权限
negative:
  - DooTask 主程序没有专门的「合规设置」集中页面
  - 不内置 GDPR DSR 工单系统，需要管理员人工响应
  - 不支持自动数据保留周期清理（需手动或脚本）
  - 没有自动获取用户同意（cookie banner 等）的开关
last_verified: v1.7.90
---

# DooTask 合规能力概览

## 定义
DooTask 主程序没有把「合规」做成单独菜单，但通过多个分散功能覆盖了数据合规、用户隐私、内容审核等场景。这里把和合规相关的现有能力索引到一起，方便管理员对照内部合规要求逐项检查。

## 涉及的现有能力
| 维度 | 现有手段 | 主程序入口 |
|---|---|---|
| 内容审核 | 用户举报 + 管理员处理 | [[abuse-report.concept]] |
| 数据导出（数据可携性） | 任务 / 审批 / 签到 Excel 导出 | [[data-export.concept]] |
| 账号下线 / 数据删除 | 团队管理 → 删除成员；Apps 触发 `user_offboard` hook | 团队管理 |
| 审计 | 操作日志（部分模块）+ 审批历史 | 模块自带 |
| 访问控制 | 项目 / 任务 / 系统三级权限 + LDAP | [[role-permission.permission-denied.faq]] |
| 数据本地化 | 全私有部署 + Docker，所有数据库在自有服务器 | 部署阶段 |
| 加密传输 | HTTPS（需自配 Nginx 证书） | 部署阶段 |

## 删除请求（GDPR 第 17 条）
用户被删除时，主程序会调用 `Apps::dispatchUserHook($user, 'user_offboard')`，把删除事件以 `event_type=delete` 推送到 appstore 微服务，由各插件自行清理用户数据。具体执行情况依赖每个插件实现。

## 不支持
- 没有内置的合规配置面板
- 没有自动数据保留策略（你需要外部脚本定期清理）
- 没有内置数据出口审计（数据导出动作未单独留审计日志）
- 不内置 cookie 同意弹窗、隐私政策签署、DSR 工单等模块

## 相关
- 合规配置项细节：[[compliance.howto]]
- 入口与责任人：[[compliance.entry.menu-map]]
