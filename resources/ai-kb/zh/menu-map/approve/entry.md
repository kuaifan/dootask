---
id: approve.entry.menu-map
title: 审批中心入口在哪
type: menu-map
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批在哪
  - 怎么进审批
  - 审批中心入口
  - 走流程在哪
  - 找不到审批
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 approve 插件
  - 管理员已配置至少一个审批流程模板
negative:
  - 未安装 approve 插件时左侧应用中心看不到「审批」卡片
  - 没有可用流程模板时进入页面会提示「暂无数据」，发起按钮无效
last_verified: v1.7.90
---

# 审批中心入口在哪

## 路径
- 桌面端：左侧栏「应用」→ 卡片「审批」
- 移动端：底部 Tabbar「应用」→ 列表「审批」
- 快捷键：无

## 页面结构
进入后顶部是 4 个 Tab，按用户视角分组：
- **待办**：分配给我、需要我点同意/拒绝的审批，Tab 名后会带未读数量（如「待办(3)」）
- **已办**：我曾经处理过（同意/拒绝/撤销）的审批
- **抄送我**：流程节点把我设为抄送人的审批
- **已发起**：我自己提交的审批，含审批中/通过/拒绝/撤回各状态

## 权限要求
- end-user 任何登录用户均可见入口和这 4 个 Tab
- admin 会额外看到「流程设置」按钮（在页面右上角）；管理员的流程模板配置不在本 chunk 范围

## 相关
- 简介与一图速览：[[app-system.approve.howto]]
- 是什么：[[approve.concept]]
- 怎么发起：[[approve.start.howto]]
