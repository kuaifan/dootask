---
id: common-faq.search-no-result.faq
title: 全局搜索没结果 / 搜索功能不可用
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 搜不到东西
  - 全局搜索没结果
  - 搜索为空
  - manticore 没装
  - 搜索功能用不了
  - 搜索失效
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 默认不带全文搜索引擎，需在应用市场装 search 插件（Manticore）
  - 没装搜索插件时降级为 MySQL LIKE，只能搜简单关键字且速度慢
  - Manticore 装上后历史数据需要回填一次才能搜到
last_verified: v1.7.90
---

# 全局搜索没结果 / 搜索功能不可用

## 问题
- 顶部搜索框输关键字转圈或显示「无结果」
- 历史消息 / 任务 / 文件搜不到
- 同义词模糊搜索完全无效

## 原因
全局搜索依赖 Manticore Search 引擎，由 search 插件提供：

| 状态 | 表现 |
|---|---|
| 未装 search 插件 | 降级到 MySQL `LIKE`，慢且弱 |
| 已装但未回填 | 新数据能搜，老数据搜不到 |
| 装了但容器没起 | 全局搜索完全失败 |

`app/Module/Manticore/` 判断可用性，不可用就降级。

## 解决

**装搜索插件（管理员）**
进 AppStore（[[appstore.entry.menu-map]]）找「search」插件（Manticore 15.x）→ 安装 → 等容器启动。

**回填历史数据**
插件首启自动同步增量，老数据要管理员触发：
- 「应用 → 系统设置 → 搜索」找「重建索引」
- 或 `./cmd artisan manticore:rebuild`（视版本）

**检查容器**
```bash
docker ps | grep manticore
```
没起就 `./cmd up`。

**看后端日志**
搜索失败日志会写 `Manticore search error: <错误>`，查 `storage/logs/laravel.log` 过滤 `Manticore`。

**关键字过短**：默认最小 2-3 字符。

## 不支持
- 不支持自定义同义词词典
- 不支持搜索权限到字段级（只到「可见用户」）
- 部分插件数据（如审批表单）不进索引

## 相关
- 性能慢：[[common-faq.deploy-perf.faq]]
- 插件不工作：[[common-faq.plugin-not-loaded.faq]]
