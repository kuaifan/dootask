---
id: dashboard.collapse.howto
title: 折叠 / 展开仪表盘分组
type: howto
feature: dashboard
scope: end-user
locale: zh
aliases:
  - 仪表盘折叠
  - 隐藏仪表盘分组
  - 隐藏协助任务
  - 仪表盘卡片折叠
  - 展开仪表盘
related_tools: []
related_pages: [dashboard]
prerequisites: []
negative:
  - 折叠状态按当前用户独立存储，不同步到其他设备
  - 不能完全隐藏顶部 3 张数字卡（始终显示）
  - 不能调换分组顺序
last_verified: v1.7.90
---

# 折叠 / 展开仪表盘分组

## 是什么
仪表盘下方的 4 个分组列表（[[dashboard.today.howto]] / [[dashboard.overdue.howto]] / [[dashboard.todo.howto]] / [[dashboard.assist.howto]]）每个都可独立折叠或展开。状态保存在浏览器 IndexedDB 的 `dashboardHiddenColumns` 键，按当前用户独立。

## 入口
- 桌面端：分组标题栏左侧的折叠箭头（▶ / ▼）
- 桌面端：点分组标题文字
- 移动端：同上

## 折叠后
- 分组标题保留（仍能看到分类名 + 数量）
- 列表内容收起
- 分组数量小标仍正常更新
- 状态保存到 IndexedDB → 下次进仪表盘默认保持

## 展开
- 再次点折叠箭头 / 标题 → 恢复展开
- 状态同步更新到 IndexedDB

## 同步规则
- IndexedDB 存储在浏览器本地
- 不同浏览器 / 不同设备的折叠状态各自独立
- 清浏览器数据会丢失折叠状态（重置为全展开）

## 不支持
- 不能完全删除分组（只能折叠）
- 不能拖动调整 4 个分组的顺序
- 不能给分组改名
- 不支持云端同步折叠状态
