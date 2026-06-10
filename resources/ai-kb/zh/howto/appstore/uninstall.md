---
id: appstore.uninstall.howto
title: 卸载一个插件
type: howto
feature: appstore
scope: admin
locale: zh
aliases:
  - 卸载插件
  - 删除应用
  - 关闭功能插件
  - 怎么停掉 AI
  - 删掉审批
  - uninstall plugin
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 卸载会停容器并标记 status，但不一定立刻删数据卷
  - 不支持卸载 `appstore` 本身（强制保留）
  - 卸载后用户在「应用」页对应入口立即消失，正在使用的会话可能报错
last_verified: v1.7.90
---

# 卸载一个插件

## 入口
桌面端：左侧栏「应用」→「应用商店」（仅管理员）→ 找到目标插件 →「卸载」按钮。

## 操作步骤
1. 进入应用商店，定位到已安装的插件
2. 点击「卸载」按钮
3. 确认弹窗：通常会提示「卸载后相关数据如何处理」
4. 等待 AppStore 微服务停容器、改 `config.yml` 状态为非 `installed`
5. 刷新「应用」页，对应菜单消失

## 卸载做了什么
- 在 `docker/appstore/config/{appId}/config.yml` 更新 `status` 字段（非 `installed`）
- 调 `docker compose down` 停掉对应容器
- 触发主程序缓存失效：`RequestContext::save('app_installed_xxx', false)`
- 之后所有对该插件的后端调用会被 `Apps::isInstalledThrow()` 拦截，抛 ApiException

## 卸载会影响的数据
取决于插件实现：
- 多数官方插件停容器但保留 `docker/appstore/apps/{appId}/data/` 数据目录
- 重新安装通常能恢复数据
- 若要彻底删数据，需要手动到服务器删 `data/` 目录（不可逆，做之前先备份）

## 卸载前建议
1. 先把该插件相关业务数据导出（如审批数据 [[data-export.approve.howto]]、签到 [[data-export.checkin.howto]]）
2. 通知使用该插件的成员
3. 重要数据先做服务器侧备份

## 不支持
- 不支持仅对部分用户隐藏（卸载即全员失效）
- 不支持「停用但保留菜单」中间态
- 不支持卸载 appstore 自身
