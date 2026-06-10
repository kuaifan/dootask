---
id: task.notify.concept
title: 任务提醒推送（即将超时 / 已超时）
type: concept
feature: task
scope: end-user
locale: zh
aliases:
  - 任务提醒
  - 任务推送
  - 任务到期通知
  - 任务超期提醒
  - 任务快到截止时间会提醒吗
  - 任务收不到通知怎么办
related_tools: [update_task]
related_pages: [task_detail, user_settings]
prerequisites:
  - 管理员已在「APP 推送」设置中开启推送（appPushSetting）
negative:
  - 没有 end_at（截止时间）的任务不会推送即将超时 / 已超时提醒
  - 管理员未开启「APP 推送」设置时，到期 / 超期定时提醒整体不发送
  - 不能为单条任务自定义提醒提前量，统一为到期前约 1 小时
last_verified: v1.7.90
---

# 任务提醒推送（即将超时 / 已超时）

## 定义
任务到期提醒由「任务提醒」机器人（task-alert）通过私聊发送模板消息。推送记录在 `ProjectTaskPushLog` 表，按 `type` 区分：

- `0` — 新任务（被分配时）
- `1` — 任务**即将超时**（截止时间 end_at 前约 1 小时）
- `2` — 任务**已超时**（end_at 过后约 1 小时，且任务未完成）
- `3` — 任务时间被修改

即将超时 / 已超时两类按用户 + 任务去重，避免重复推送。

## 触发条件
- 任务必须设置截止时间 `end_at`
- 管理员需在管理员应用「APP 推送」中开启推送（未开启则定时提醒不运行）
- 后端定时任务扫描命中后，向任务负责人 + 协助人发送，文案如「您负责的任务即将超时」「您协助的任务已经超时」

## 推送渠道
- **站内消息**：「消息」列表中 task-alert「任务提醒」机器人的私聊会话
- **桌面端**：经 Electron 系统通知弹出
- **移动端**：离线 / 后台用户经 UMENG（友盟）推送触达，见 [[mobile-client.notify.concept]]

## 不支持
- 不能为单条任务自定义提醒时间偏移（如「提前 2 小时」），固定为到期前约 1 小时、超期后约 1 小时各提醒一次
- 不能给非负责人 / 非协助人推送
- 已完成或已归档的任务不再推送
