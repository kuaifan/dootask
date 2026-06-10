---
id: view.switch.howto
title: 切换看板 / 列表 / 甘特图视图
type: howto
feature: view
scope: end-user
locale: zh
aliases:
  - 怎么切换视图
  - 换种方式看任务
  - 看板换列表
  - 切到甘特图
  - 视图按钮在哪
related_tools: [list_tasks]
related_pages: [project_detail]
prerequisites: []
negative:
  - 视图选择按项目持久化（cacheParameter.menuType），换项目不会自动跟随
  - 没有快捷键切换视图
  - 移动端视图切换按钮位置与桌面一致，但小屏更靠右
  - 工作流视图不是这里切换，是通过工作流筛选 Cascader 叠加
last_verified: v1.7.90
---

# 切换看板 / 列表 / 甘特图视图

## 入口
- 桌面端 Web：项目详情页右上角，3 个图标排成一行的切换条
- 移动端：项目详情页顶部同位置（图标更小）

## 三个切换按钮
从左到右依次：

1. **看板**（图标：方块阵列）→ `menuType='column'`，默认视图
2. **列表**（图标：横线列表）→ `menuType='table'`
3. **甘特图**（图标：时间轴）→ `menuType='gantt'`

切换器底部有一条滑块（slider）会跟随高亮，所选视图按钮变色。

## 操作步骤
1. 进入项目详情页
2. 点切换条上对应图标
3. 数据立即按新视图重排，切换过程不重新请求数据（已在前端）
4. 选择记录在 `cacheParameter.menuType`，下次进入该项目仍是此视图

## 持久化
- 视图状态按 `项目 ID × 当前用户` 维度记录
- 不跨设备同步（本地缓存）
- 默认值为 `column`（看板）

## 与"全部项目页"
- 项目列表页（all 项目）有自己的视图（项目卡片 / 项目列表），与单项目的视图无关

## 不支持
- 视图切换没有键盘快捷键
- 不能强制所有项目用同一视图
- 不能新增第 5 种自定义视图
- 部门只读模式下视图仍可切换（只是不能编辑任务）
