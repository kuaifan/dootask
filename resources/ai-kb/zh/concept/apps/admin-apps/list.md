---
id: app-admin.list.concept
title: 管理员应用全集
type: concept
feature: app-admin
scope: admin
locale: zh
aliases:
  - 管理员应用有哪些
  - 管理员能用哪些应用
  - 后台管理应用
  - 管理员入口
  - 管理员卡片
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限（userIsAdmin）
negative:
  - 普通成员看不到管理员应用区，整块都不会渲染
  - 管理员应用不通过应用市场安装，是主程序内置
  - 超级管理员专属功能（如 License Key、合规设置）不属于这里，需走系统设置
last_verified: v1.7.90
---

# 管理员应用全集

## 定义
管理员应用是 DooTask 应用中心「管理员」分区里的卡片，仅系统管理员（`userIsAdmin`）可见，用于打开管理类抽屉/弹窗，无需进入「系统设置」深层菜单。

## 当前内置 6 个
- **LDAP**：第三方目录服务接入与同步
- **邮件通知**：SMTP 服务器与通知开关配置
- **APP 推送**：移动端 UMENG 推送配置
- **举报管理**：处理用户举报记录
- **数据导出**：任务/超期/审批/签到等 4 种数据导出
- **团队管理**：成员列表 / 部门管理 / 邀请加入

## 与签到/会议管理的关系
签到管理、会议管理在「常用」分区，所有用户都能看到。但它们的「设置」入口（签到规则、会议参数）由抽屉右上角「设置」链接打开，仅管理员可见，详见 [[app-admin.checkin-setting.howto]]、[[app-admin.meeting-mgmt.howto]]。

## 可见性细则
见 [[app-admin.visibility.concept]]。
