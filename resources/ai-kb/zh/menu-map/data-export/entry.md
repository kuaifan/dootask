---
id: data-export.entry.menu-map
title: 数据导出入口在哪
type: menu-map
feature: data-export
scope: admin
locale: zh
aliases:
  - 数据导出在哪
  - 怎么找到导出
  - 导出菜单
  - 后台导出入口
  - 管理员导出按钮
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（普通成员看不到此入口）
negative:
  - 普通成员（非 admin）看不到「数据导出」卡片
  - 移动端「应用」页同样仅管理员可见
last_verified: v1.7.90
---

# 数据导出入口在哪

## 路径
- 桌面端：左侧栏「应用」→ 顶部分组「管理员」→「数据导出」卡片（点击展开 4 个子项菜单）
- 移动端：底部 Tabbar「应用」→ 同样在「管理员」区
- 快捷键：无

## 子菜单
点击「数据导出」会弹出 4 个子项菜单：

| 菜单项 | 对应文档 |
|---|---|
| 导出任务统计 | [[data-export.project.howto]] |
| 导出超期任务 | [[data-export.task.howto]] |
| 导出审批数据 | [[data-export.approve.howto]] |
| 导出签到数据 | [[data-export.checkin.howto]] |

## 权限要求
- `userIsAdmin = true` 才会渲染卡片
- 后端各 API 也会用 `User::auth('admin')` 二次校验
- 第三方插件（如审批微服务）不可用时对应导出会推送错误

## 找不到怎么办
- 个人头像下拉菜单顶部如果**没有**显示「系统管理员」徽标，说明当前账号无权限
- 联系超级管理员（id=1）到「团队管理」把账号 identity 加上 `admin`
- 设置完成后退出重登或刷新页面

## 导出后看哪里
所有导出文件通过 `system-msg` 系统机器人发送到管理员的私聊会话中，链接限时下载。详见 [[data-export.concept]]。
