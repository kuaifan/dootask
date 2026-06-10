---
id: application.menu-admin.howto
title: 管理员自定义全员应用菜单
type: howto
feature: application
scope: admin
locale: zh
aliases:
  - 自定义应用菜单
  - 给所有人加应用入口
  - 加自定义微应用
  - 全员可见的应用怎么配
  - microapp menu
related_tools: []
related_pages: [application]
prerequisites:
  - 当前用户为系统管理员（userIsAdmin）
negative:
  - 仅管理员可见入口和保存接口
  - 自定义菜单只是注册一个 URL/iframe 入口，DooTask 不托管业务页面
  - 配置项里没有"权限继承项目角色"的开关
last_verified: v1.7.90
---

# 管理员自定义全员应用菜单

## 是什么
管理员可以在应用中心追加自定义菜单项（iframe / 外链等），对应一个或多个全员/管理员可见的入口。底层调用 `api/system/microapp_menu`，保存到系统设置的 `microapp_menu` 项。

## 入口
- 桌面端 / 移动端：应用中心右上角「⋯」→「自定义应用菜单」（普通成员看不到此项）

## 操作步骤
1. 应用中心右上角「⋯」→「自定义应用菜单」
2. 点「新增菜单」生成一张空白配置卡
3. 填写必填字段：
   - 应用 ID（如 `custom-okr`）
   - 菜单标题
   - 菜单 URL（支持 `{user_token}` 等占位符）
4. 调整可选项：菜单位置 / 可见范围 / 图标 / 类型（iframe / iframe_blank / inline / inline_blank / external）/ 背景色 / 保活 / 暗黑等
5. 点「保存」提交，应用中心立即出现新菜单

## 关键字段
| 字段 | 取值 | 说明 |
|---|---|---|
| 菜单位置 | `application` / `application/admin` / `main/menu` | 决定卡片放在常用区、管理员区或顶部主导航 |
| 可见范围 | `admin` / `all` | 仅管理员或全员可见 |
| 类型 | iframe / iframe_blank / inline / inline_blank / external | 决定打开方式 |

## 不支持
- 普通成员无入口，必须以管理员身份进
- 不能精细到「按部门可见」，可见范围仅 admin / all 两档
- 配置不会影响微应用插件本身，只是追加一层菜单
