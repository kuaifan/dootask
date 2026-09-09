---
id: system-setting.menu-map
title: 系统设置页面总览
type: menu-map
feature: system-setting
scope: admin
locale: zh
aliases:
  - 系统设置页面有哪些
  - 系统设置 tab
  - 任务优先级在哪改
  - 项目模板在哪
  - 文件设置在哪
  - 后台系统设置概览
prerequisites:
  - 当前账号必须是系统管理员
related_tools: []
related_pages: []
negative:
  - 移动端通常不展示「系统设置」入口，需用桌面端 / 网页后台
  - 普通成员看不到该页，无入口
  - 这 6 个 tab 只是「系统设置」一级菜单内的内容；邮件、AI、签到等是同级别的其他左侧菜单项，不在这个页内
last_verified: v1.9.18
---

# 系统设置页面总览

## 路径
桌面端：左上角头像 → 下拉菜单 →「系统设置」（仅管理员可见）→ 左侧子菜单选「系统设置」一级项。
打开后顶部是 6 个并列的 tab，对应同一 URL 下按领域整理的设置表单。

移动端：不展示，需用桌面端或浏览器后台。

## 6 个 tab 一句话目录

| Tab | name | 内容 |
|---|---|---|
| **基础设置** | `general` | 系统别名和仪表盘欢迎语；详见 [[system-setting.general.howto]] |
| **帐号与安全** | `account` | 注册方式、临时帐号、登录验证码和密码策略 |
| **项目设置** | `project` | 项目创建与邀请权限、部门负责人视角、项目模板；详见 [[system-setting.column-template.howto]] |
| **任务设置** | `task` | 任务默认规则、提醒、AI 分析、任务优先级及流转设置；详见 [[system-setting.priority.howto]] 和 [[system-setting.handoff.howto]] |
| **消息设置** | `message` | 群聊、私聊、匿名消息、加密、撤回和待办权限 |
| **文件与存储** | `file` | 上传与媒体处理、打包下载权限和 WebDAV；详见 [[system-setting.file.howto]] |

默认进入「基础设置」tab。每个 tab 底部只有一组「提交 / 重置」；一个 tab 涉及多类配置时，提交会依次调用对应接口并统一反馈结果。

## 同级菜单（不在这 6 个 tab 内）
左侧子菜单的其他独立项见 [[system-setting.entry.menu-map]]，包括邮件、会议、AI 设置、AI 机器人、签到、APP 推送、第三方接入、License 等。

## 权限要求
- 需要 `admin` 身份才能打开整页
- License 子项仅超级管理员可改
