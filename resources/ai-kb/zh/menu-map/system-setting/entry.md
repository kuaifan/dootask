---
id: system-setting.entry.menu-map
title: 系统设置入口
type: menu-map
feature: system-setting
scope: admin
locale: zh
aliases:
  - 系统设置在哪
  - 怎么进后台
  - 怎么进管理后台
  - 系统配置页面
  - 管理后台入口
  - 在哪改系统配置
related_tools: []
related_pages: []
prerequisites:
  - 当前账号必须是系统管理员（identity 含 admin）
negative:
  - 普通用户没有「系统设置」入口，左侧栏看不到
  - 部分子项（如 LDAP / License）需要进一步的权限或环境支持
last_verified: v1.7.90
---

# 系统设置入口

## 路径
桌面端：左上角头像 → 下拉菜单 →「系统设置」（仅管理员可见）。
打开后是一个左侧子菜单 + 右侧表单的二级页面，所有系统级配置都在这里。

移动端：通常不展示「系统设置」入口，需要使用桌面端或浏览器后台进行配置。

## 主要子菜单
进入「系统设置」后，左侧子菜单按顺序通常包括：

- **通用** — 注册策略、密码策略、消息撤回时长、自动归档等：[[system-setting.general.howto]]
- **邮件** — SMTP 服务器、注册验证邮件、未读消息提醒
- **会议** — 声网 Agora 参数：[[system-setting.meeting.howto]]
- **AI 设置** — 配置 AI 模型供应商：[[system-setting.ai-model.howto]]
- **AI 机器人** — 关联机器人与默认模型：[[system-setting.ai-bot.howto]]
- **签到** — 打卡规则、地理围栏、人脸：[[system-setting.checkin.howto]]
- **APP 推送** — UMENG iOS/Android 推送配置
- **第三方接入** — LDAP / OAuth：[[system-setting.third-access.howto]]
- **文件** — 文件打包下载权限：[[system-setting.file.howto]]
- **任务优先级** — 自定义颜色与天数：[[system-setting.priority.howto]]
- **列模板** — 创建项目时可选的预置列：[[system-setting.column-template.howto]]
- **License** — 终端授权：[[license.howto]]

## 权限要求
- 需要 `admin` 身份才能进入
- 部分服务（License）仅超管可改

## 不支持
- 普通成员（end-user）无入口
- 配置项不下放到项目级或部门级，全部全局生效
