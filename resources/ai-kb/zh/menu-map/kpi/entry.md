---
id: kpi.entry.menu-map
title: KPI 绩效考核入口
type: menu-map
feature: kpi
scope: end-user
locale: zh
aliases:
  - KPI 在哪
  - 怎么打开绩效
  - 找不到绩效考核
  - 绩效入口
  - 怎么进 KPI
related_tools: []
related_pages: [application]
prerequisites:
  - 应用市场已安装 kpi 插件
  - 主程序版本 > 1.4.67
negative:
  - 主程序不内置 KPI，未装插件时入口不会出现
  - 主程序版本不够无法安装插件，入口也不会出现
  - 不同角色看到的页面功能不同，但入口是同一个
last_verified: v1.7.90
---

# KPI 绩效考核入口

## 路径
KPI 由独立插件 `community_kuaifan_kpi` 提供，安装后在应用中心注册一个菜单项：

- 桌面端：左侧栏「应用」→「绩效考核」（对应 URL `apps/kpi`）
- 移动端：底部 Tabbar「应用」→「绩效考核」
- URL 上会自动附带 `theme` 参数，用于主题（亮 / 暗）同步

## 加载方式
- 菜单项类型 `url_type: iframe`，在 DooTask 主框架内嵌打开
- `immersive: true`：进入后会全屏沉浸展示，左侧主导航被收起
- 同一个入口对所有角色可见，进入后页面根据当前用户的 KPI 角色（employee / manager / hr）展示对应功能

## 权限要求
- 入口本身：所有已登录用户可见
- 角色映射：
  - DooTask 系统管理员 → KPI 内部 hr 角色
  - DooTask 部门负责人 → KPI 内部 manager 角色
  - 其他用户 → KPI 内部 employee 角色
- 注意：角色只在用户首次进入 KPI 时分配，后续 DooTask 角色变更不会自动同步

## 看不到入口怎么办
1. 确认应用市场已安装 `kpi` 插件
2. 主程序版本必须 > 1.4.67，否则插件无法安装
3. 容器首次启动需要拉取镜像，可能等待几分钟
4. 安装完成后刷新页面或重新登录让菜单生效

## 相关
- 插件元信息：[[kpi.plugin.concept]]
- KPI 是什么：[[kpi.concept]]
- 创建考核：[[kpi.create.howto]]
