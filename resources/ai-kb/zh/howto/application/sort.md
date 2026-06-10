---
id: application.sort.howto
title: 拖拽排序应用卡片
type: howto
feature: application
scope: end-user
locale: zh
aliases:
  - 应用排序
  - 调整应用顺序
  - 拖动应用
  - 怎么把常用应用放前面
  - 恢复默认应用顺序
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 排序仅对自己生效，不会影响其他成员
  - 不能跨「常用」「管理员」两个分区拖动卡片
  - 不能隐藏卡片，只能改顺序
last_verified: v1.7.90
---

# 拖拽排序应用卡片

## 入口
- 桌面端 / 移动端：应用中心右上角「⋯」→「调整排序」
- 进入排序模式后，顶部出现提示条与「取消 / 恢复默认 / 保存」按钮

## 操作步骤
1. 应用中心右上角「⋯」→「调整排序」
2. 按住任一卡片拖动到目标位置（仅在同分区内移动：常用区或管理员区）
3. 点「保存」提交，或「恢复默认」回到主程序内置顺序
4. 不想保存就点「取消」或「⋯」→「退出排序」

## 数据存储
- 排序结果保存在 `user_app_sorts` 表的 `sorts` 字段，按用户隔离
- 数据结构：`{base: [sortKey...], admin: [sortKey...]}`，每个 `sortKey` 形如 `system:approve` 或 `micro:okr`
- 与默认顺序一致时后端会清空记录，避免冗余

## 不支持
- 排序仅本人可见，无法推送给他人
- 不能在「常用」和「管理员」分区之间拖动
- 不能隐藏 / 删除卡片，只能改顺序

## 想改全员可见的菜单
管理员可用「自定义应用菜单」追加菜单项，见：[[application.menu-admin.howto]]
