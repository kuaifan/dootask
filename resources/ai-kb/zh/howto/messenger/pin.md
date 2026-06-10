---
id: messenger.pin.howto
title: 置顶会话
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 怎么把消息置顶
  - 群置顶
  - 会话置顶
  - 把这个群放最上面
  - 置顶聊天
  - 取消置顶
related_tools: []
related_pages: [messenger, dialog_chat]
prerequisites: []
negative:
  - 置顶只对自己有效，不影响其他成员的会话列表顺序
  - 置顶的会话不能隐藏，会先报「置顶会话无法隐藏」
  - 多个置顶按 top_at 倒序排列，没有手动拖动排序
last_verified: v1.7.90
---

# 置顶会话

「置顶会话」（dialog top）把指定的会话顶到列表最上方。仅作用于当前用户，其他成员的会话顺序不变。可与单条消息置顶（top_msg_id）区分：前者是「这个会话排第一」，后者是「这个群里这条消息钉在顶部」。

## 入口

- 桌面端：会话列表右键 / 鼠标悬停 → 「置顶」
- 桌面端：进入会话 → 右上角操作菜单 → 「置顶会话」
- 移动端：会话列表左滑 → 「置顶」按钮

## 操作步骤

1. 在会话列表找到目标会话
2. 触发「置顶」操作
3. 接口写入 dialog_user.top_at = 当前时间
4. 列表立刻按 top_at 倒序 + last_at 倒序刷新

## 取消置顶

再次点击「置顶」按钮即可，top_at 会被清空。

## 与「消息置顶」的区别

- 会话置顶（pin dialog）：自己列表里把会话顶上去
- 消息置顶（top_msg_id）：群内单条消息钉在群顶部，所有人可见，详见 msg__top 接口

## 不支持

- 不支持设置置顶数量上限（实际由会话列表大小决定）
- 不支持把置顶顺序手动调整，按时间倒序
- 不支持单聊和群聊分组置顶
