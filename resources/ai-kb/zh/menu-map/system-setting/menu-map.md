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
  - 这 4 个 tab 只是「系统设置」一级菜单内的内容；邮件、AI、签到等是同级别的其他左侧菜单项，不在这个页内
last_verified: v1.7.90
---

# 系统设置页面总览

## 路径
桌面端：左上角头像 → 下拉菜单 →「系统设置」（仅管理员可见）→ 左侧子菜单选「系统设置」一级项。
打开后顶部是 4 个并列的 tab，对应同一 URL 下的 4 个子表单。

移动端：不展示，需用桌面端或浏览器后台。

## 4 个 tab 一句话目录

| Tab | name | 内容 |
|---|---|---|
| **系统设置** | `setting` | 全站通用开关：注册、密码、消息、视频、上传、欢迎语等几十项；详见 [[system-setting.general.howto]] |
| **任务优先级** | `taskPriority` | 自定义任务优先级的颜色、名称、提前提醒天数；详见 [[system-setting.priority.howto]] |
| **项目模板** | `columnTemplate` | 新建项目时可选的预置「列模板」清单（如 看板列、状态列）；详见 [[system-setting.column-template.howto]] |
| **文件设置** | `fileSetting` | 文件相关策略（如打包下载权限、缩略图）；详见 [[system-setting.file.howto]] |

默认进入「系统设置」tab。切换 tab 不会重置已填未保存的表单值，但每个 tab 单独保存。

## 同级菜单（不在这 4 tab 内）
左侧子菜单的其他独立项见 [[system-setting.entry.menu-map]]，包括邮件、会议、AI 设置、AI 机器人、签到、APP 推送、第三方接入、License 等。

## 权限要求
- 需要 `admin` 身份才能打开整页
- License 子项仅超级管理员可改
