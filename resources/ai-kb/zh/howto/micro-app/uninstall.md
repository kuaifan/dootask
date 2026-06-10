---
id: micro-app.uninstall.howto
title: 卸载微应用
type: howto
feature: micro-app
scope: admin
locale: zh
aliases:
  - 怎么卸载微应用
  - 删插件
  - 卸载应用
  - 删 OKR
  - 移除微应用
  - 停掉插件
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 卸载只停容器并隐藏菜单，业务数据保留在数据库中，下次重装可继续使用
  - 卸载部分插件会让依赖该插件的功能失效（如 face 卸载后人脸签到不可用）
  - 普通成员看不到卸载入口
last_verified: v1.7.90
---

# 卸载微应用

## 入口
- 桌面端：左侧栏「应用」→ 应用市场 → 已安装插件 →「卸载」
- 桌面端备选：系统设置 →「插件 / 应用市场」→ 选中目标插件 →「卸载」
- 卸载入口仅 `userIsAdmin = true` 时可见

## 操作步骤
1. 进入应用市场，切到「已安装」标签
2. 找到目标插件，点击「卸载」
3. 确认弹窗后等待容器停止与清理
4. 卸载成功后插件状态变为 `uninstalled`，应用中心对应卡片消失（普通成员需刷新）

## 数据保留与彻底清理
- 默认卸载：保留业务数据表，仅停容器、删菜单
- 重装可继续用旧数据
- 彻底清理：需手动到数据库删表 + 删 `docker/appstore/config/<id>/`、`docker/appstore/apps/<id>/`

## 依赖关系提醒
- 卸载 `approve` → 审批中心入口消失，但已发起的审批数据保留
- 卸载 `face` → 人脸识别签到不可用，已采集数据保留
- 卸载 `office` → OnlyOffice 在线编辑不可用，文件可下载但不可在线编辑

## 不支持
- 卸载不能选「同时清空数据」，须手动清
- 无回收站，菜单卸载后立即消失
