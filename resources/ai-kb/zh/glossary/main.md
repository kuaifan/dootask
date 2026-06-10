---
id: glossary.main
title: DooTask 核心术语表
type: glossary
feature: glossary
scope: end-user
locale: zh
aliases:
  - 术语
  - 术语表
  - DooTask 术语表
  - 名词解释
  - DooTask 名词
  - 这个词什么意思
  - 别名对照
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 只覆盖产品术语，不含技术名词
  - 别名只列常见口语化叫法
last_verified: v1.7.90
---

# DooTask 核心术语表

把口语化叫法对齐到正式术语。格式：术语 / 别名 / 定义。

## task（任务）
todo, 待办, 卡片, 工作项 — 归属某项目某一列的工作项，核心实体。

## subtask（子任务）
子卡片, 拆分任务 — 父任务下的下级任务，仅 1 层不可再嵌套。

## project（项目）
看板 — 任务的容器，含若干列与若干任务，按成员授权。

## column（列）
工作流列, 状态列, status, 阶段 — 项目内按状态分组的纵列。

## flow（工作流）
流程, 任务流, 状态流转 — 列与列之间的流转规则，可配自动动作。

## dialog（对话 / 群聊）
会话, 群, 群聊, IM, 聊天 — 消息会话总称，含单聊、群聊、项目讨论、机器人对话。

## meeting（会议）
在线会议, 视频会议, 通话, 开会 — 基于 WebRTC 的多人音视频会议。

## approve（审批）
审批中心, 走流程, 报销, 请假 — 表单 + 多级节点的审批系统应用。

## checkin（签到）
打卡, 考勤 — 员工每日打卡，支持地点 / Wi-Fi / 人脸校验。

## report（工作报告）
日报, 周报, 月报, 汇报 — 定期工作总结，支持模板与自动汇总。

## okr / kpi
目标、KR（OKR）；绩效、考核（KPI）— OKR=周期目标管理；KPI=规则化绩效。

## bot（机器人）
bot, AI 机器人, webhook 机器人 — 聊天中可被 @ 触发的自动化账号。

## memos / minder
备忘录, 笔记 / 脑图, mindmap — 个人轻量笔记 / 思维导图编辑器。

## drawio / office / fileview
流程图 / Office / 在线预览 — 三个文件类插件。

## calendar / dashboard / favorite
日历 / 首页, 工作台 / 收藏, 标星 — 日程聚合视图 / 登录后默认页 / 跨实体收藏。

## micro-app / appstore
插件, 应用 / 插件市场 — micro-app=扩展模块；appstore=安装升级入口，仅管理员可访问。

## ai-assistant
AI, 智能助手, ChatGPT — 基于 RAG 的产品内 AI 助手，由 AI 微应用提供。

## ProjectUser.owner
项目管理员, 项目负责人 — `owner` 字段；`0`=成员，`1`=负责人，`2`=管理员。

## userid -1
AI 账号, AI bot, 系统 AI — 系统保留账号，AI 消息以此 ID 标识，不占席位。

## EEUI / Electron
App, 客户端, 桌面端, 手机端 — EEUI=移动端原生壳；Electron=桌面端原生壳；浏览器版共用前端。
