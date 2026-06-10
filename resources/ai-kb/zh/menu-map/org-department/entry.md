---
id: org-department.entry.menu-map
title: 部门管理入口在哪
type: menu-map
feature: org-department
scope: admin
locale: zh
aliases:
  - 部门管理在哪
  - 怎么进部门管理
  - 部门入口
  - 团队管理在哪
  - 添加部门入口
  - 部门设置在哪
  - 找不到部门
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（userIsAdmin）才能看到管理后台「团队管理」入口
negative:
  - 普通成员**看不到**「团队管理」菜单；只能通过个人资料/头像查看自己所在部门
  - 移动端不提供完整的部门管理界面（仅管理后台桌面端可用）
  - 「默认部门」表示未分配部门的成员，是系统内置，不能新建/删除/改名
  - 没有快捷键
last_verified: v1.7.90
---

# 部门管理入口在哪

## 路径
- 桌面端：右上角头像菜单 → 「管理后台」→ 左侧菜单「团队管理」→ 顶部部门栏（左侧树形）
- 接口直接调用：`GET api/users/department/list`（限管理员）
- 快捷键：无

## 页面结构
进入团队管理后页面分两栏：

- **左侧部门树**：顶部「默认部门」+ 全部部门按 id 升序的树形列表
  - 每行：负责人头像、部门管理员头像（多人显示 +N）、部门名称、行尾「⋯」操作菜单
  - 底部按钮：「+ 新建部门」
- **右侧成员列表**：选中部门后展示该部门成员，可搜索（关键词 = 邮箱 / 昵称 / 职位）

## 部门行操作菜单（⋯）
| 操作 | 说明 |
|---|---|
| 添加子部门 | 见 [[org-department.add.howto]] |
| 部门交流群 | 跳转到该部门绑定的部门群 |
| 同步部门成员 | 见 [[org-department.sync.howto]] |
| 编辑 | 改名 / 改上级 / 改负责人 |
| 删除 | 红色文字；含子部门时无法删除 |

## 权限要求
- 管理后台入口仅 `userIsAdmin = true` 的系统管理员可见
- 普通成员的「我的部门」查看走个人资料页（接口 `info/departments`），最多看到自己所在的 10 个部门

## 相关
- 整体概念：[[org-department.concept]]
- 负责人视角（不同入口，普通成员也可触发）：[[org-department.deputy-view.howto]]
