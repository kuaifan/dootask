---
id: file.tree.concept
title: 文件树结构
type: concept
feature: file
scope: end-user
locale: zh
aliases:
  - 文件夹结构
  - 文件父子关系
  - 文件嵌套
  - 文件树
  - 文件层级
related_tools: [list_files, get_file_detail]
related_pages: [file]
prerequisites: []
negative:
  - 单个文件夹下最多 300 个直接子文件 / 子文件夹
  - 不支持软链接 / 快捷方式（一个文件只能在一个位置）
  - 不支持跨用户的全局共享根目录
last_verified: v1.7.90
---

# 文件树结构

## 定义
DooTask 的文件以树形结构组织：每个文件或文件夹（type=folder）通过 `pid` 字段指向父级，根目录 `pid=0`。系统额外维护 `pids` 字段（递归祖先 ID 串）用于快速查询路径与权限。

## 关键属性
- **pid**：直接父文件夹 ID，0 表示根
- **pids**：祖先链字符串如 `,5,12,28,`，用于 LIKE 查询子树
- **type=folder**：文件夹本身也是 File 表的一条记录
- **userid**：拥有者（与共享文件夹一致时表示共享根）
- **pshare**：所属共享根 ID，0 表示不在任何共享内

## 容量限制
- 单文件夹直接子项数量上限 300（含子文件夹）
- 整树深度无强制上限但超过 10 层不推荐
- 共享文件夹最多 100 个共享成员

## 与「项目任务文件」的区别
- 文件树：用户的网盘，独立功能
- 项目任务文件：附在任务下的附件（`project_task_files` 表），不进入文件树
- 两者完全隔离，互不可见

## 文件类型枚举
folder（文件夹）/ document（在线文档）/ mind（思维导图）/ drawio（流程图）/ word / excel / ppt / picture / archive / pdf / txt / code / media 等。
