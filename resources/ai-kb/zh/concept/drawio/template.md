---
id: drawio.template.concept
title: drawio 内置图形与模板
type: concept
feature: drawio
scope: end-user
locale: zh
aliases:
  - drawio 模板
  - drawio 图形库
  - drawio 能画什么
  - 流程图都有哪些类型
  - drawio 支持的图形
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 drawio 插件
negative:
  - DooTask 不提供「业务模板市场」，新建图永远是空白画布
  - 不能直接将 drawio 图形粘贴到 DooTask 任务详情或讨论消息
  - 模板/形状无法跨公司账号共享（每个部署独立）
last_verified: v1.7.90
---

# drawio 内置图形与模板

## 定义
drawio 编辑器自带丰富的图形库（Shape Libraries）和绘图分类，但 DooTask 集成时**不预置业务模板**。每次新建图都从空白画布开始，需要绘图者从图形库手动拖出元素或在 drawio 内的「File → New From Template」选择官方模板。

## 主要图形分类
drawio 左侧图形库可勾选启用以下分类（部分）：
- 通用形状（General）
- 流程图（Flowchart）
- 泳道图（Swimlanes）
- UML（类图、时序图、用例图、状态图、活动图）
- 实体关系（ER）
- 网络与基础设施（Cisco / AWS / Azure / GCP / Kubernetes 图标）
- 软件架构（C4 模型）
- 业务流程（BPMN）
- 思维导图（mockup）
- 电气、布线、机柜
- 安卓/iOS 线框图

## 选用方式
- 编辑器左下角「More Shapes」勾选要启用的图形库
- 直接从左侧图形库拖元素到画布
- 通过菜单 `File → New From Template` 选择官方预设模板（流程示例、组织结构等）

## 与思维导图（minder）对比
- drawio 也能画思维导图，但灵活但偏「画」
- 专注思维导图推荐用专门的 [[minder.concept]] 插件，节点操作更顺手

## 不支持
- DooTask 主程序不预置业务模板（如审批流程模板等）
- 模板不能跨部署共享，只能依赖 drawio 上游内置的模板
- 不能上传自定义图形库到所有用户

## 相关
- 是什么：[[drawio.concept]]
- 创建：[[drawio.create.howto]]
