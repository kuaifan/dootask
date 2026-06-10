---
id: kpi.concept
title: KPI 绩效考核是什么
type: concept
feature: kpi
scope: end-user
locale: zh
aliases:
  - KPI
  - 绩效
  - 绩效考核
  - 绩效管理
  - 员工评估
  - Key Performance Indicator
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 kpi 插件
negative:
  - KPI 不是 OKR，与 OKR 在 DooTask 中是两个独立插件
  - KPI 不是主程序内置功能，未装插件时不可用
  - KPI 用户角色与 DooTask 系统角色不完全等价（详见正文）
last_verified: v1.7.90
---

# KPI 绩效考核是什么

## 定义
KPI（Key Performance Indicator，关键绩效指标）在 DooTask 中由独立插件 `community_kuaifan_kpi` 提供，是面向企业和组织的现代化绩效考核管理系统。它支持创建考核任务、按模板录入指标评分、邀请同事评分、HR 审核与异议处理，让多角色协作完成员工绩效评估。

## 在 DooTask 中的形态
- 通过应用市场安装的社区插件（基于 Next.js + Go）
- 与 DooTask 用户体系打通：用户信息、部门信息自动同步
- 与 DooTask 主程序作为应用插件集成，菜单挂在「应用」下

## 用户角色
KPI 内部有自己独立的三级角色（与 DooTask 系统角色不完全等价）：

- **employee（普通员工）**：查看 / 填写自己的考核、提交异议、参与邀请评分
- **manager（部门主管）**：员工的全部能力 + 评估下属、导出数据
- **hr（HR 管理员）**：完整系统权限，管理部门 / 员工 / 模板 / 规则 / 异议

## 关键能力
- **考核管理**：创建、填写、管理绩效考核
- **邀请评分**：多角度 360 度评价（详见 [[kpi.create.howto]]）
- **异议处理**：员工可申诉，HR 审核调整得分
- **统计分析**：数据图表与报表，支持 Excel 导出
- **KPI 模板**：HR 维护通用模板供考核复用

## KPI 与 OKR 的区别
- **KPI**：直接考核指标，与绩效 / 薪酬绑定，偏重「不能丢分」
- **OKR**：目标管理工具，鼓励挑战性目标，与考核解耦

## 不支持
- 不直接对接 DooTask 任务完成情况自动算绩效
- 不支持脱离 DooTask 单独登录使用

## 相关
- 插件元信息：[[kpi.plugin.concept]]
- 入口在哪：[[kpi.entry.menu-map]]
- 创建考核：[[kpi.create.howto]]
- 评分机制：[[kpi.scoring.concept]]
