---
id: favorite.entry.menu-map
title: 我的收藏 / 最近打开入口
type: menu-map
feature: favorite
scope: end-user
locale: zh
aliases:
  - 我的收藏在哪
  - 最近打开在哪
  - 收藏夹入口
  - 怎么找到我的收藏
  - 最近浏览入口
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 没有顶部导航的独立入口，必须通过「应用」中心进入
  - 不在左侧栏一级菜单上，不会默认置顶
  - 桌面端和移动端入口结构一致，没有平台差异化路径
last_verified: v1.7.90
---

# 我的收藏 / 最近打开入口

## 路径
**桌面端**
- 左侧栏「应用」→ 应用列表中的「我的收藏」卡片 → 打开「我的收藏」弹窗
- 左侧栏「应用」→ 应用列表中的「最近打开」卡片 → 打开「最近打开」弹窗

**移动端**
- 底部 Tabbar「应用」→ 应用列表中的「我的收藏」/「最近打开」卡片
- 进入后弹窗以全屏方式展示

**快捷键**：无

## 入口属性
| 应用 | value | 默认排序 sort | 触发事件 |
|---|---|---|---|
| 我的收藏 | `favorite` | 45 | `openFavorite` |
| 最近打开 | `recent` | 47 | `openRecent` |

两个入口都属于「常用应用」分组，定义在 `manage/application.vue` 的 `applyList`。可通过应用排序设置调整位置（见相关「应用中心排序」文档）。

## 权限要求
- `end-user`：所有登录用户可见可用
- 不区分管理员 / 超管，无开关可关闭
- 插件市场不提供，是系统内置功能

## 进入后能看到什么
- **我的收藏**：4 类对象（任务 / 项目 / 文件 / 消息）的收藏列表，支持类型过滤、编辑备注、取消收藏、跳转。详见 [[favorite.list.howto]]
- **最近打开**：系统自动记录的最近访问对象（任务 / 文件 / 任务附件 / 消息附件）。详见 [[favorite.recent.concept]]

## 相关
- 添加收藏：[[favorite.add.howto]]
- 取消收藏：[[favorite.remove.howto]]
- 收藏与最近的概念区别：[[favorite.concept]]
