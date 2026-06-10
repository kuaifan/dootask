---
id: project.sort.howto
title: 项目列表排序与置顶
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 项目置顶
  - 项目排序
  - 项目放最上面
  - 调整项目顺序
  - 取消置顶
related_tools: [list_projects]
related_pages: [project_list]
prerequisites: []
negative:
  - 置顶 / 排序是每用户独立的（ProjectUser.top_at / sort）
  - 不能给整团队设默认排序
  - 项目内任务不能用此机制置顶（任务排序见 [[task.sort.howto]]）
last_verified: v1.7.90
---

# 项目列表排序与置顶

## 是什么
DooTask 项目列表的顺序由两个字段决定，都按用户级别独立存储在 `ProjectUser` 表：
- `top_at`：置顶时间戳，非空则项目固定在顶部
- `sort`：手动排序值，决定置顶以下的相对顺序

列表查询排序规则：**top_at DESC、sort ASC、id DESC**。

## 置顶项目
- 桌面端：项目左侧栏右键项目 → 「置顶」
- 桌面端：项目列表悬停项目 → 「📌」按钮
- 移动端：长按项目行 → 「置顶」

服务端写入当前时间到 ProjectUser.top_at，项目立即排到顶部。后置顶的项目排得更靠前。

## 取消置顶
- 同入口，点「取消置顶」
- 服务端把 top_at 置空
- 项目回到非置顶区，按 sort 重新排

## 调整非置顶项目顺序
- 桌面端：在左侧栏拖动项目
- 服务端 `user__sort` 接口按拖动后的位置批量重写 sort 值
- 不会影响置顶区的相对顺序

## 与「项目内任务排序」的区别
- 项目排序是用户的私人视图（每用户独立）
- 任务排序（[[task.sort.howto]]）是项目内全局视图，所有成员看到的顺序一致

## 不支持
- 不能按字段自动排序（如按修改时间）
- 不能给团队 / 部门设统一默认排序
- 置顶项目数无限制，但前端展示区有限，置顶太多会出现滚动
