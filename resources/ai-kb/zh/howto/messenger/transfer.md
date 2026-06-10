---
id: messenger.group.howto.transfer
title: 转让群主
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么转让群主
  - 移交群主
  - 换群主
  - 群主转让给别人
  - 转让群组
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites:
  - 必须是当前群主
  - 仅支持普通群（group_type=user）
  - 新群主必须已是群成员且账号有效
negative:
  - 项目群 / 任务群 / 部门群 / 全员群不支持转让群主
  - 不能把群主转让给自己（会报「你已经是群主」）
  - 不能转给不存在 / 已离职 / 已禁用的账号
last_verified: v1.7.90
---

# 转让群主

群主（owner_id）可以把群组转让给群内其他成员。转让后原群主自动降级为普通成员，新群主自动写入 role=1，群内所有成员收到 groupUpdate 推送。

## 入口

- 桌面端：打开群聊 → 右上角群信息面板 → 「群主」字段右侧「转让」
- 移动端：进群 → 右上角「⋯」→ 群组信息 → 「群主」→ 「转让群主」

## 操作步骤

1. 从成员列表选要接管的成员
2. 二次确认（默认 check_owner=yes，要求是当前群主操作）
3. 提交后：
   - owner_id 改为新群主 userid
   - 旧群主 role 改为 0（普通成员）
   - 新群主 role 改为 1
   - 推送 groupUpdate 给所有群成员

## 与群管理员的区别

- 群主：唯一，全部群管理权限，可解散群
- 群管理员（deputy）：可多个，能加人、踢人、改群名，但不能解散群、不能转让群主
- 详见任命群管理员相关说明（在「添加和移除群成员」流程中）

## 不支持

- 不支持把群主转给已离职 / 已删除账号
- 不支持把项目群、任务群、部门群、全员群的"群主"转出，因为它们没有用户层面的群主概念
