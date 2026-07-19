---
id: dashboard.refresh.faq
title: 个人仪表盘任务变化后为什么没有立即移动
type: faq
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘不更新
  - 完成任务后还在原列表
  - 本周完成少了一条
  - 仪表盘切回来不加载
  - 个人仪表盘有缓存吗
related_tools: [list_tasks]
related_pages: [dashboard]
prerequisites: []
negative:
  - 个人视角没有「立即刷新」按钮
  - 切回仪表盘不会在已有缓存时强制显示整页 Loading
  - 团队统计的刷新规则见 dashboard.team-refresh.faq
last_verified: v1.8.69
---

# 个人仪表盘任务变化后为什么没有立即移动

## 现象
在仪表盘内完成任务后，数量已经减少，但任务行仍划线保留在原分组；「本周完成」暂时还没有出现这条任务。

## 原因
这是个人视角的稳定完成区逻辑：当前页面完成的任务会暂时留在原位置，避免列表突然消失或分组跳动。任务重新进入稳定缓存后，才按 `complete_at` 进入本周完成。页面上的数量和划线状态会先根据本地任务状态更新，最终分组以服务端返回的完成时间为准。

## 数据加载
- 应用首次没有任务缓存时才显示初始化 Loading。
- 已有缓存时切换离开再回来会直接复用页面数据，不重复显示整页 Loading。
- 新账号会在初始化完成前后区分欢迎、Loading 和最终空状态，避免先错误显示欢迎卡。

## 排查
1. 等待短暂同步完成。
2. 打开任务详情确认状态已保存。
3. 检查客户端连接是否正常。
4. 长时间仍不一致时刷新浏览器重新获取数据。

团队视角有独立的手动刷新入口：[[dashboard.team-refresh.faq]]。
