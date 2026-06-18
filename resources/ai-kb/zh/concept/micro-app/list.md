---
id: micro-app.list.concept
title: 当前可用微应用
type: concept
feature: micro-app
scope: end-user
locale: zh
aliases:
  - 有哪些微应用
  - 插件清单
  - 应用市场有什么
  - 装了哪些应用
  - 可用微应用
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - 这是常见插件示例，不是出厂强制清单；实际可见列表随已安装情况而变
  - 没装的插件菜单不会出现，不能简单从「应用」中心看出全部
  - 自部署或第三方仓库的插件不在此列
last_verified: v1.7.90
---

# 当前可用微应用

## 定义
DooTask 应用市场提供一组官方与社区维护的微应用。下面列的是较常见的几类，实际能在应用中心看到的微应用取决于本实例已安装了哪些，参考服务器 `docker/appstore/apps/` 目录即可知。

## 常见微应用
- **OKR**（id `okr`）— 目标 + 关键结果管理
- **审批中心**（id `approve`）— 流程审批
- **思维导图 Minder**（id `minder`）— 文件类型 `.km`
- **流程图 Drawio**（id `drawio`）— 文件类型 `.drawio`
- **OnlyOffice**（id `office`）— Word / Excel / PPT 在线编辑
- **文件预览 fileview**（id `fileview`）— PDF / 图片 / 文档预览渲染
- **人脸识别签到**（id `face`）— 配合签到使用
- **AI 助手**（id `ai`）— 浮窗式智能助手
- **Memos 笔记**（社区 `community_kuaifan_memos`）— 个人记事本
- **KPI**（社区 `community_kuaifan_kpi`）— 绩效考核
- **Manticore 搜索**（id `manticore`）— 全文搜索引擎

## 怎么知道我这装了哪些
- 应用中心「常用」分区里非系统应用的卡片都来自微应用
- 完整插件列表见应用市场「已安装」标签
- 前端 Vuex 字段 `microAppsIds` 是已装插件 id 数组

## 没装的怎么办
- 让管理员去应用市场安装：[[micro-app.install.howto]]
- 不是所有插件都能在私有化部署的实例上装（依赖镜像源连通）
