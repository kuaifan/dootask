---
id: approve.install.howto
title: 安装审批插件
type: howto
feature: approve
scope: admin
locale: zh
aliases:
  - 怎么装审批
  - 启用审批中心
  - 审批怎么开
  - 在哪开启审批
  - 安装 approve 插件
  - 审批中心没有怎么办
related_tools: []
related_pages: [application]
prerequisites:
  - 当前用户是系统管理员（userIsAdmin）
  - 服务器能拉取 `kuaifan/dooapprove` Docker 镜像
negative:
  - 普通成员看不到「应用市场」入口，无法自行安装
  - 安装包约 25MB，下载较慢，必须看「安装日志」判断进度，不能凭感觉重试
  - 卸载时勾选「删除数据」会清空全部审批历史，且不可恢复
last_verified: v1.7.90
---

# 安装审批插件

## 入口
- 桌面端：左侧栏「应用」→ 右上角「应用市场」→ 顶部分类「插件」→ 找到「审批中心」

## 操作步骤
1. 在应用市场卡片上点击「安装」
2. 弹窗确认参数（默认 `DEMO_DATA: "true"` 会随安装写入一份演示流程模板，正式环境可改 `false`）
3. 点「确定」开始安装，主程序会创建一个 docker-compose 服务并拉起容器
4. 安装过程中打开「安装日志」面板查看拉镜像进度（约 25MB）
5. 状态变为 `installed` 后，左侧栏「应用」会自动出现「审批中心」入口

## 启用后默认行为
- 自动注册路由 `api/approve/*`，主程序 `ApproveController` 开始可用
- 自动注册 nginx 反代 `/approve/`，把 iframe 内的流程模板编辑页透传给插件容器
- 若 `DEMO_DATA: "true"`：随安装写入「请假申请」等演示流程模板，方便上线测试
- 在主程序数据库里创建 `<前缀>approve_*` 表前缀的工作流表（不与主程序业务表混库）

## 卸载/重装
- 应用市场 → 已安装 →「卸载」
- 弹窗有「同时删除数据」勾选：勾上则连同 `<前缀>approve_*` 表一并 drop；不勾则保留数据下次安装时自动接上
- 仅升级版本走「更新」按钮，不会触发数据清理

## 不支持
- 没有「试用」/「禁用」中间态：要么 `installed` 要么 `uninstalled`
- 不能同时存在新旧两个版本

## 相关
- 插件架构与数据隔离原理：[[approve.plugin.concept]]
- 入口与四个 Tab 的常规用法：[[app-system.approve.howto]]
