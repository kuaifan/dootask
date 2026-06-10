---
id: dashboard.refresh.faq
title: 仪表盘不刷新 / 任务变化后看不到
type: faq
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘不更新
  - 任务完成了仪表盘还有
  - 仪表盘要手动刷新吗
  - 仪表盘有缓存
  - 仪表盘没数据
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 仪表盘没有手动刷新按钮，靠 600ms 防抖定时器自动刷
  - 任务字段改动是通过 WebSocket 推送进来的，掉线时可能不同步
  - 仪表盘进入页面时会立即拉一次最新数据
last_verified: v1.7.90
---

# 仪表盘不刷新 / 任务变化后看不到

## 问题
任务完成 / 改了截止时间 / 加了新任务，但仪表盘的列表 / 数字没立刻变化。

## 原因
仪表盘的数据流是「前端 store + WebSocket 推送 + 600ms 防抖定时器」的混合：
- 进入仪表盘页时立即拉一次任务列表
- 后续靠 WebSocket 推送实时变化
- 任何任务字段变更触发 `getTaskForDashboard(600)`：600 毫秒后重算分组

但如果：
- WebSocket 断开重连 → 期间的事件丢失
- 浏览器后台休眠 → 定时器暂停
- 跨标签页的修改 → 仍依赖 WebSocket 推送

可能出现仪表盘看到的与真实状态有几秒差异。

## 解决
1. **手动触发重算**：进任意其他页（如左侧栏点项目）再回仪表盘 → 强制拉新数据
2. **刷新浏览器**（F5）：彻底重拉所有 store
3. 看右下角连接状态：若显示"已断开"等几秒待重连
4. 确认任务确实生效：进任务详情看 complete_at / end_at 字段

## 不支持
- 没有"手动刷新仪表盘"按钮
- 没有刷新间隔配置（固定 600ms 防抖）
- 不支持 polling 周期性拉数据，只走 WebSocket 推送
