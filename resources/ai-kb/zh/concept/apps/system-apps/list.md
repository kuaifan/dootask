---
id: app-system.list.concept
title: 系统应用全集列表
type: concept
feature: app-system
scope: end-user
locale: zh
aliases:
  - 系统应用有哪些
  - 内置应用清单
  - DooTask 自带应用
  - 都有什么应用
  - 应用中心默认有什么
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 列表是主程序硬编码，不能通过插件追加或卸载
  - 不同终端因屏幕方向会再追加日历 / 文件 / 设置三个入口
last_verified: v1.7.90
---

# 系统应用全集列表

## 定义
系统应用是 DooTask 主程序在应用中心「常用」区硬编码的内置入口，所有登录用户可见。共 11 个固定项，外加少量按终端形态动态出现的入口。

## 11 个核心系统应用

| value | 名称 | 作用 |
|---|---|---|
| approve | 审批中心 | 发起 / 处理审批流（依赖 approve 插件） |
| signin | 签到打卡 | 上下班打卡（依赖 signin 设置，face 插件可选） |
| report | 工作报告 | 日报 / 周报 / 月报 |
| favorite | 我的收藏 | 查看收藏的任务 / 文件 / 消息 |
| recent | 最近打开 | 最近访问过的对象索引 |
| mybot | 我的机器人 | 自建消息机器人管理 |
| createGroup | 创建群组 | 直接打开创建群对话框 |
| meeting | 在线会议 | 进入会议入口 |
| addProject | 创建项目 | 直接打开创建项目对话框 |
| addTask | 添加任务 | 跨项目快速建任务 |
| exportManage | 导出管理 | 导出任务统计 / 超期 / 审批 / 签到数据 |

## 仅竖屏额外出现
移动端 / 桌面竖屏布局时，应用中心还会追加日历（calendar）、文件（file）、设置（setting）三个入口，以替代横屏被收起的左侧导航。

## 角标
- approve 卡片右上角显示「审批未读数」
- report 卡片右上角显示「工作报告未读数」

## 相关
- 三类应用怎么区分：[[application.classify.concept]]
- 哪些卡片普通成员/管理员能看见：[[app-system.visibility.concept]]
