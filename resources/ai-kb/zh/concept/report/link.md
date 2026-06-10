---
id: report.link.concept
title: 报告分享链接
type: concept
feature: report
scope: end-user
locale: zh
aliases:
  - 报告链接
  - 报告分享码
  - report code
  - 单独详情页
  - 报告 URL
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 链接不是「公开 URL」，访问者仍需登录 DooTask 才能看到内容
  - 同一对 (报告, 用户) 默认复用同一个 code；除非显式刷新（`refresh=yes`）
  - 失败逻辑：如果原报告被删除，链接打开报「报告不存在或已被删除」
last_verified: v1.7.90
---

# 报告分享链接

## 定义
报告分享链接（ReportLink）是为某一份报告 + 某个分享发起人生成的访问短码。短码用于构造独立页 URL，让接收人通过链接（而非进入应用菜单）直达报告详情。

## URL 格式
- 完整路径：`<站点根>/single/report/detail/<code>`
- 前端路由：`name = single-report-detail`，参数 `reportDetailId = code`
- 服务端通过 `code` 反查 `report_links` 表得到原报告 ID

## 数据结构
`report_links` 表关键字段：

| 字段 | 含义 |
|---|---|
| `rid` | 原报告 ID |
| `userid` | 发起分享的人 |
| `code` | 短码，base64(`rid,userid,随机串`) |
| `num` | 累计访问次数 |
| `created_at` / `updated_at` | 时间戳 |

## 何时生成
- 调 `api/report/share` 分享到对话时自动生成
- 同一 (rid, userid) 已存在链接时直接复用
- 传 `refresh=yes` 可强制重新生成 code（让旧链接失效）

## 访问计数
每次通过 code 打开详情页，`num` 字段自增 1。可用于粗略判断报告被点开多少次。

## 权限边界
- 生成阶段：只有提交人或接收人能生成链接
- 访问阶段：访问者必须已登录；登录后无额外白名单（即拿到 URL 就能看）

## 关联功能
- 分享到对话 → [[report.share.howto]]
- 报告本体定义 → [[report.concept]]
