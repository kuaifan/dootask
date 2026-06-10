---
id: approve.plugin.concept
title: 审批插件架构
type: concept
feature: approve
scope: end-user
locale: zh
aliases:
  - 审批是插件吗
  - approve 插件是什么
  - 审批中心怎么部署的
  - 为什么审批要单独装
  - 审批不在主程序里吗
related_tools: []
related_pages: [application]
prerequisites: []
negative:
  - approve 不是主程序内置功能，必须先在应用市场安装插件才能用
  - 审批数据存在独立的 `<前缀>approve_*` 数据表里，不在主程序业务表
  - 卸载插件时若勾选「删除数据」会清空全部审批数据，无法恢复
last_verified: v1.7.90
---

# 审批插件架构

## 定义
审批（approve）是 DooTask 的独立插件，由 `kuaifan/dooapprove` Docker 镜像提供一套独立的工作流引擎服务。主程序通过反向代理与之通信，所有审批的流程定义、实例、任务、历史都由插件维护，不在主程序业务表里。

## 关键属性
- **独立容器**：插件作为 docker-compose 服务名 `approve` 启动，端口仅在内网暴露
- **独立数据库表**：复用主库实例但表前缀为 `<DB_PREFIX>approve_`（如 `pre_approve_*`），与主程序业务表逻辑隔离
- **HTTP 反代**：主程序 nginx 把 `/approve/` 转给插件容器；`/approve/api/` 先经 `/approveAuth` 校验主程序 token 再放行
- **业务桥接**：主程序 `ApproveController` 通过 `http://approve` 调用插件 REST 接口（路径前缀 `/api/v1/workflow/...`），把结果包成 `Base::retSuccess` 返回前端
- **通知桥接**：审批状态变化通过 `approval-alert` 机器人在 DooTask 群聊中下发模板消息

## 与主程序的关系
- 主程序登录 token = 审批插件身份凭据（通过 `verifyToken` 接口校验）
- 主程序用户/部门/机器人是审批的"人员主数据"，审批不复制用户表
- 用户感知不到容器分离：在 [[app-system.approve.howto]] 描述的「审批中心」页面内完成所有操作

## 不支持
- 主程序无法直接 SQL 查询审批数据，必须经插件 API
- 关闭/卸载插件后无法发起新审批：「应用 - 审批」入口在新发起处会报错（`Apps::isInstalledThrow`）
