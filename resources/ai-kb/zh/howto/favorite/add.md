---
id: favorite.add.howto
title: 收藏一个对象
type: howto
feature: favorite
scope: end-user
locale: zh
aliases:
  - 怎么收藏任务
  - 收藏项目
  - 收藏文件
  - 收藏消息
  - 加星
  - 怎么加收藏
related_tools: []
related_pages: [task_detail, project_detail, file, messenger]
prerequisites:
  - 当前用户对被收藏对象有可见权限
negative:
  - 不能批量收藏多个对象，一次只能加一个
  - 不能替别人收藏（收藏仅对自己生效）
  - 没有「收藏分组」「收藏夹」概念，全部进同一个列表
last_verified: v1.7.90
---

# 收藏一个对象

## 支持的对象类型
- **任务（task）**：项目下的任意任务
- **项目（project）**：自己加入或可见的项目
- **文件（file）**：文件柜中的文件
- **消息（message）**：群聊 / 私聊中的某条消息

## 操作步骤
进入对应对象的详情页或操作菜单，点击「收藏 / 加星」按钮即可。同一按钮再次点击为取消收藏（toggle 语义，见 [[favorite.remove.howto]]）。

- 任务：任务详情页 → 顶部「⋯」菜单 →「收藏」
- 项目：项目详情页 → 顶部「⋯」菜单 →「收藏项目」
- 文件：文件详情 / 文件柜右键 →「收藏」
- 消息：长按 / 右键消息气泡 →「收藏」

## 添加备注（可选）
收藏成功后，进入「我的收藏」（见 [[favorite.list.howto]]）列表，点击对应行的「备注」字段可编辑：

- ≤ 255 字符
- 备注仅本人可见，可随时修改清空

## 接口行为
后端使用 toggle 接口（`POST api/users/favorite/toggle`），传 `type` 与 `id`；已收藏则取消，未收藏则添加。前端按钮态由 `users/favorite/check` 实时回查。

## 不支持
- 不能批量收藏；每次操作一个对象
- 收藏不通知他人；不会出现在动态中
- 不能给收藏排序 / 分组 / 打标签
