---
id: app-system.export.howto
title: 导出管理入口
type: howto
feature: app-system
scope: end-user
locale: zh
aliases:
  - 导出在哪
  - 导出管理
  - 导出任务
  - 导出审批
  - 导出签到
  - exportManage 入口
related_tools: []
related_pages: [application]
prerequisites:
  - 涉及全局数据的导出（如审批数据）会再校验管理员权限
negative:
  - 卡片只是导出动作的快捷入口，不是导出历史查看页
  - 没有按项目精细筛选的二级菜单，按"类型"分类
last_verified: v1.7.90
---

# 导出管理入口

## 是什么
应用中心「导出管理」卡片对应 `exportManage` 系统应用，点击后弹出二级菜单提供四种常用导出动作。每种动作内部再走对应的导出流程（生成文件 / 触发下载 / 进异步队列）。

## 入口
- 桌面端：左侧导航「应用」→「导出管理」卡片
- 移动端：底部 Tabbar「应用」→「导出管理」

## 二级菜单
点击卡片后弹出菜单：

| 选项 | 触发动作 |
|---|---|
| 导出任务统计 | `openManageExport / task` |
| 导出超期任务 | `openManageExport / overdue` |
| 导出审批数据 | `openManageExport / approve` |
| 导出签到数据 | `openManageExport / checkin` |

## 操作步骤
1. 应用中心 → 点「导出管理」
2. 在二级菜单选导出类型
3. 弹出导出配置对话框（选时间范围 / 项目 / 用户等条件）
4. 提交后按数据量同步下载或异步生成

## 不支持
- 不能一次同时导出多种类型，每次只能选一种
- 卡片不显示历史导出记录或下载列表

## 与管理员区「数据导出」的区别
- 应用中心常用区「导出管理」：所有用户可见，按动作分类的一键导出
- 管理员区「数据导出」：仅管理员，覆盖更多类型与导出参数的高级配置
