---
id: common-faq.sync-task-not-update.faq
title: 任务更新没同步 / 看不到别人改的字段
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 任务没刷新
  - 别人改了我看不到
  - 任务字段不同步
  - 实时刷新失败
  - 任务变化收不到
  - 改完没反应
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 没有"轮询拉新"机制，所有实时性都依赖 WebSocket
  - WebSocket 断线期间发生的变更不会自动补拉
  - 关闭浏览器标签后再打开不会自动重连之前的 WS 会话
last_verified: v1.7.90
---

# 任务更新没同步 / 看不到别人改的字段

## 问题
同事在任务上改了截止时间 / 负责人 / 状态，但我这边页面上还是旧值，要刷新才看得到。

## 原因
DooTask 任务字段的实时同步走两条路径：

- **WebSocket 推送**：后端在 `Task` 模型保存后通过 `Doo::userIdPush` 推 `task` 事件，前端 store 收到后更新本地缓存
- **进入页面拉一次**：进任务详情 / 项目页时主动拉一次最新数据

如果出现「同事改了我看不到」，多数是 WebSocket 推送没到达：

- 浏览器后台休眠 / 切到别的标签页时 WS 可能被节流
- 网络抖动后 WS 断开但前端还没察觉
- 同事改的字段不在你的可见范围（如可见用户限制）

## 解决
1. 进任意其他页（项目列表）再回当前页 → 强制重新拉一次
2. 看右下角连接状态：显示「已断开」就等待自动重连或刷浏览器
3. F5 刷新，彻底重拉所有 store
4. 确认对方真的有修改：进任务详情看修改时间字段

## 相关
- 仪表盘类似问题：[[dashboard.refresh.faq]]
- WebSocket 断开排查：[[common-faq.sync-websocket-disconnect.faq]]
