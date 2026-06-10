---
id: messenger.todo.howto
title: 消息设为待办
type: howto
feature: messenger
scope: end-user
locale: zh
aliases:
  - 消息设待办
  - 标记消息待办
  - 让某人去做
  - 钉一条消息给某人
  - 群消息派活
  - 提醒某人这条
related_tools: [send_message]
related_pages: [messenger, dialog_chat]
prerequisites:
  - 客户端需 ≥ 0.37.18
  - 设别人为待办需具备「群主 / 项目/任务负责人 / 系统管理员」身份（系统设置 todo_set_permission=close 时收紧）
negative:
  - 通知（notice）、标注（tag）、其他待办消息（todo）类型不可再被设为待办
  - 待办设置后可再次取消，但完成（done_at）后不能撤销完成
  - 待办提醒时间（remind_at）只能精确到分钟，不支持秒级
last_verified: v1.7.90
---

# 消息设为待办

「消息待办」（msg todo）让一条群消息变成派活：勾选谁需要处理后，被勾选成员在该会话顶部和全局待办里会看到它，处理完点「完成」即可销项。可设可选的提醒时间。

## 入口

- 桌面端：鼠标悬停消息 → 操作菜单 → 「设为待办」
- 桌面端：长按消息气泡 → 「设为待办」
- 移动端：长按消息气泡 → 「设为待办」

## 操作步骤

1. 触发「设为待办」
2. 选择目标：
   - all：会话全部成员（默认）
   - user：指定 userids
3. 可选：设置提醒时间 remind_at
4. 提交 → 群里出现一条 todo 类系统消息「XXX 把 YYY 设为待办」

## 完成待办

被指派的成员可在「会话顶部待办列表」或「全局待办」里点击「完成」。每完成一人，群消息会更新 done_userids。

## 取消 / 改提醒

- type=user 且 userids=[] 用于清除所有人的待办
- todoremind 接口可单独改提醒时间（remind_at 为空则取消提醒）

## 不支持

- 不支持把 tag / todo / notice 类型消息设为待办
- 不支持已撤回 / 已删除的消息恢复待办
- 普通成员在 todo_set_permission=close 时不能给别人派待办
