---
id: report.entry.menu-map
title: 工作报告入口在哪
type: menu-map
feature: report
scope: end-user
locale: zh
aliases:
  - 工作报告在哪
  - 工作汇报怎么打开
  - 周报入口
  - 日报在哪写
  - 怎么进报告页面
  - 打开汇报
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 「工作报告」不在左侧主导航栏，是右上角头像菜单 + 应用中心系统应用
  - 普通成员菜单显示「工作报告」；系统管理员菜单中不直接显示该项，可改走应用中心
last_verified: v1.7.90
---

# 工作报告入口在哪

## 路径
- 桌面端（普通成员）：右上角头像 → 下拉菜单「工作报告」
- 桌面端（任何角色）：左侧栏「应用」→ 系统应用「工作报告」卡片
- 移动端：底部 Tabbar「我的」→「工作报告」
- 详情独立页（分享后访问）：`single/report/detail/<code>`
- 编辑独立页：`single/report/edit/<reportEditId>`
- 快捷键：无

## 打开后的界面
顶部两个 Tab：
- **我发送的**：自己提交过的报告列表 [[report.my.howto]]
- **我收到的**：别人发给我的报告列表，带未读数 [[report.receive.howto]]

右上角「+」可新建报告 [[report.create.howto]]。

## 权限要求
- end-user 可见，所有登录用户都能写和收报告
- 不需要插件，是主程序内置功能
