---
id: app-admin.entry.menu-map
title: 管理员应用入口在哪
type: menu-map
feature: app-admin
scope: admin
locale: zh
aliases:
  - 管理员应用怎么打开
  - LDAP 在哪
  - 邮件通知入口
  - 团队管理入口
  - 举报管理入口
  - 数据导出入口
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 不在「系统设置」页面里，而是在「应用」中心
  - 普通成员路径不存在该入口
last_verified: v1.7.90
---

# 管理员应用入口在哪

## 路径
- 桌面端：左侧主导航「应用」→ 页面下方「管理员」分区
- 移动端竖屏：底部 Tabbar「应用」→ 滚动到「管理员」分区
- 快捷键：无

## 「管理员」分区在哪
- 应用中心页面分两行：上方「常用」+ 下方「管理员」
- 当前管理员账号没有任何卡片可见时（极少见），分区标题也不显示
- 排序模式下可拖动调整顺序（保存后仅自己生效）

## 各卡片打开方式
点击卡片直接弹出右侧抽屉，不跳页面：
- 「LDAP」→ LDAP 设置抽屉（700 宽）
- 「邮件通知」→ 邮件设置抽屉
- 「APP 推送」→ UMENG 推送抽屉
- 「举报管理」→ 举报记录抽屉
- 「数据导出」→ 下拉菜单选导出类型（任务/超期/审批/签到）
- 「团队管理」→ 跳转到团队管理页

## 权限要求
- admin 才能看到「管理员」分区
- 详见 [[app-admin.visibility.concept]]
