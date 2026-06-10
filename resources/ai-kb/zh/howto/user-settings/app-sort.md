---
id: user-settings.app-sort.howto
title: 调整应用菜单排序
type: howto
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 应用排序
  - 改菜单顺序
  - 拖动应用图标
  - 应用中心排序
  - 把某个应用放前面
  - 重排微应用
  - 自定义菜单顺序
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 应用排序只影响当前用户自己看到的顺序，不影响其他成员
  - 排序按「分组」隔离：普通应用（base）与管理员应用（admin）各排各的
  - 系统内置应用 / 微应用 / 管理员应用都参与排序，但隐藏的应用不会出现在排序里
last_verified: v1.7.90
---

# 调整应用菜单排序

「应用菜单排序」让用户按自己习惯重新排列应用中心 / 左侧应用栏的图标顺序。每个用户独立配置，不会影响他人。

## 入口

- 桌面端：左侧「应用」→ 进入应用中心 → 拖动应用卡片调整位置
- 桌面端：右上角全局「+」面板里同步排序（共用同一份配置）
- 后端接口：
  - 获取：`GET api/users/appsort`
  - 保存：`POST api/users/appsort/save`

## 数据结构

存表 `user_app_sorts`（一个用户一行），字段 `sorts` 为 JSON：

```
{
  "base":  ["micro:calendar", "system:approve"],
  "admin": ["system:ldap"]
}
```

- `base` 普通成员可见的应用
- `admin` 管理员独占应用
- 每项为 `<source>:<id>` 形式（`system` / `micro` 等命名空间 + 应用 id）

## 操作步骤

1. 进入「应用中心」（或「应用」面板）
2. 长按 / 拖动应用图标到目标位置
3. 松手后会自动调用 `appsort__save` 保存
4. 其他终端登录会拉新顺序

## 默认顺序

未配置时按系统默认顺序（按插件注册先后 + 安装时间）。任意拖动一次即生成本人配置。

## 不支持

- 不支持隐藏单个应用图标（请去插件 / 微应用设置卸载）
- 不支持把「admin 应用」拖进「base 分组」
- 不支持团队级 / 部门级统一应用顺序模板
