---
id: messenger.group.howto.rename
title: 修改群名称
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 改群名
  - 群名怎么改
  - 修改群名称
  - 群组改名
  - 设置群头像
  - 怎么改群头像
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites:
  - 普通群（group_type=user）需是群主或群管理员
  - 全员群（group_type=all）需是系统管理员
negative:
  - 项目群 / 任务群 / 部门群的群名跟随源数据，不能在群聊里直接改
  - 群名长度限制 2-100 字符，超出会报错
  - 普通成员（非群主、非管理员）改群名会报「仅群主或群管理员可操作」
last_verified: v1.7.90
---

# 修改群名称

修改群名称（chat_name）和群头像（avatar）在同一个面板，只有具备权限的角色可操作。

## 入口

- 桌面端：打开群聊 → 右上角「群信息」/「成员」图标 → 顶部群名右侧「编辑」按钮
- 移动端：进群 → 右上角「⋯」→ 群组信息 → 点击群名 / 群头像

## 操作步骤

1. 修改群头像：点击当前头像 → 上传新图（jpg / png / gif / webp）
2. 修改群名：点击群名进入编辑态，输入新名称（2-100 字符）
3. 提交后立即生效，群内所有成员收到群信息更新推送

## 字段默认值

| 字段 | 默认值 | 限制 |
|---|---|---|
| chat_name | 创建时输入的名称 | 2-100 字符 |
| avatar | 系统生成的占位图 | jpg / png / gif / webp |

## 权限矩阵

- 普通群（group_type=user）：群主、群管理员可改名 / 改头像
- 全员群（group_type=all）：仅系统管理员可改名 / 改头像
- 项目群 / 任务群 / 部门群：不可改名（跟随源数据），但可改头像

## 不支持

- 改群名后无法撤销操作，需手动改回
- 群名不支持 emoji 之外的特殊控制字符
