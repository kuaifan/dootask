---
id: project.flow.howto.edit
title: 编辑工作流节点 / 流转规则 / 负责人
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 改工作流节点
  - 改流转规则
  - 工作流负责人
  - 工作流绑定列
  - 编辑流程节点
related_tools: [update_project]
related_pages: [project_settings, project_flow]
prerequisites:
  - 是项目拥有者或管理员
  - 项目已启用工作流（[[project.flow.howto.create]]）
negative:
  - 节点的 columnid 一旦绑定，拖列与改流转状态联动
  - 删除节点不会回填已存在任务的 flow_item_id（任务可能"卡"在不存在的节点上）
  - 改节点名只改新任务的 flow_item_name，已存在任务保留旧名
last_verified: v1.7.90
---

# 编辑工作流节点 / 流转规则 / 负责人

## 入口
- 桌面端：项目设置 → 「工作流」 → 节点列表
- 拥有者 / 管理员才可改

## 节点字段
| 字段 | 含义 |
|---|---|
| `name` | 节点名（≤20 字符） |
| `status` | start / progress / test / end |
| `color` | 节点色块（看板色条用） |
| `turns` | 可流转到的节点 id 数组 |
| `userids` | 节点负责人（限定只有这些人可让任务进入此节点） |
| `usertype` | add 追加 / replace 替换 / merge 合并任务负责人 |
| `userlimit` | 是否锁定 userids（不允许其他人接手） |
| `columnid` | 可选绑定到具体列 |
| `sort` | 在列表中的展示顺序 |

## 改节点
1. 在节点行点编辑
2. 改字段后「保存」
3. 服务端 update + WebSocket 推送

## 改 turns（流转规则）
- 多选「这个节点可以流转到哪些节点」
- 不在 turns 列表的节点禁止直接流转
- 想让任意流转 → 把所有节点都加入 turns

## userids + usertype 联动
- usertype=add：拖任务到此节点，userids 中的人自动加入任务协作者
- usertype=replace：拖任务到此节点，任务负责人改为 userids 中的人
- usertype=merge：合并任务负责人 + userids 去重
- userlimit=1：非 userids 的人不能让任务流转到此节点

## 绑定 columnid 的副作用
- 看板视图拖任务跨列 → flow_item_id 自动同步
- 工作流视图拖任务跨节点 → column_id 自动同步
- 不绑定时两个视图各自独立

## 不支持
- 不能批量编辑多个节点
- 不能给节点单独配「跳过完成时间字段」之类的副作用
- 删除节点不会清理"卡在该节点的任务"，需要手动改任务的 flow_item_id
