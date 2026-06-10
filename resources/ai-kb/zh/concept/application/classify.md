---
id: application.classify.concept
title: 应用中心的三类应用
type: concept
feature: application
scope: end-user
locale: zh
aliases:
  - 应用分类
  - 系统应用是什么
  - 管理员应用是什么
  - 微应用是什么
  - 应用怎么区分
  - 应用类型
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 普通成员看不到「管理员应用」分区
  - 微应用不固定，随插件安装/卸载动态变化
last_verified: v1.7.90
---

# 应用中心的三类应用

## 定义
应用中心把所有可点击的入口卡片分成三类：系统应用、管理员应用、微应用。三者按数据源、可见范围、是否依赖插件来区分。

## 三类对比

| 类别 | 数据来源 | 可见范围 | 是否依赖插件 |
|---|---|---|---|
| 系统应用 | 主程序硬编码（11 个固定项） | 所有登录用户 | 否，主程序内置 |
| 管理员应用 | 主程序硬编码（LDAP / 邮件 / 推送 / 举报 / 数据导出 / 团队管理） | 仅 `userIsAdmin` | 否 |
| 微应用 | 已安装插件提供的 menu 注册项 | 由插件 `visible_to` 决定（admin / all） | 是 |

## 关键差异
- **是否能被卸载**：系统应用和管理员应用无法卸载；微应用随插件卸载消失
- **能否自定义**：管理员可在「自定义应用菜单」给微应用追加 / 覆盖入口配置，系统应用不能动
- **排序**：三类卡片混合后由用户拖拽个性化（仅本人可见）

## 与「应用市场」的关系
- 应用中心 = **使用**入口
- 应用市场（AppStore）= 安装 / 卸载 / 配置插件，仅管理员可见，会改变可用的微应用清单

## 相关
- 系统应用完整列表见：[[app-system.list.concept]]
- 拖拽排序：[[application.sort.howto]]
