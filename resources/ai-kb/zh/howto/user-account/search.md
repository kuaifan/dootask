---
id: user-account.search.howto
title: 搜索用户
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 搜索用户
  - 找人
  - 搜同事
  - 按部门筛人
  - 按项目找成员
  - 添加联系人
related_tools: [search_users, get_users_basic]
related_pages: []
prerequisites: []
negative:
  - 默认排除离职用户（disable=0），如要看离职用户须 disable=1 或 disable=2（含离职）
  - 默认排除机器人（bot=0），需要机器人时 bot=1/2
  - 单次最多返回 100 条，超过需翻页（page + pagesize）
  - 搜索结果只含基础字段（basicField），完整资料需调 user/info 或 get_users_basic
last_verified: v1.7.90
---

# 搜索用户

## 入口
- 桌面端：右上角头像 →「联系人」→ 搜索框
- 桌面端：任务/项目「添加成员」「指派负责人」时的弹窗里
- 桌面端：聊天「+ 发起对话」时的人员选择器
- 通用接口：`api/users/search`

## 关键词支持
单一关键词 `keys.key` 智能匹配：
- 含 `@` → 按 email 模糊匹配
- 纯数字 → 同时按 userid 精确 + nickname / pinyin / profession 模糊
- 其它 → 按 nickname / pinyin / profession 模糊

## 高级筛选
- `keys.disable`：`0` 仅在职（默认）/ `1` 仅离职 / `2` 全部
- `keys.bot`：`0` 排除机器人（默认）/ `1` 仅机器人 / `2` 全部
- `keys.project_id`：仅项目内的成员
- `keys.no_project_id`：排除项目内的成员（常用于「邀请新成员」时排除已在的人）
- `keys.dialog_id`：仅对话/群里的成员
- `keys.departments`：按部门 ID 过滤（多个逗号分隔）
- `sorts.az`：按拼音首字母 asc/desc 排序
- `with_department=1`：返回结果带 department_info
- `state=1`：返回结果带 online 在线状态

## 分页
- 默认 take 模式：`take` 10-100，单页返回
- 分页模式：传 `page` 参数后，用 `pagesize`（≤ 100，默认 10）

## 与 MCP 工具的关系
AI 助手内置 `search_users` 工具直接复用此接口；调 `get_users_basic` 可按 ID 列表批量取昵称/头像/邮箱，性能比搜索更高。

## 不支持
- 不支持按身份 identity 过滤（如「只搜管理员」），需取回后前端自行筛
- 不支持模糊匹配电话号码或地址字段
