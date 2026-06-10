---
id: abuse-report.handle.howto
title: 处理一条举报
type: howto
feature: abuse-report
scope: admin
locale: zh
aliases:
  - 处理举报
  - 怎么审核举报
  - 标记已处理
  - 删除投诉
  - 处理用户举报
related_tools: []
related_pages: [application]
prerequisites:
  - 需要系统管理员权限
negative:
  - 「已处理」状态不可逆，无法重置为「待处理」
  - 「删除」是数据库硬删除（`delete()`），不可恢复
  - 处理动作不会自动通知举报人或被举报对话成员
last_verified: v1.7.90
---

# 处理一条举报

## 入口
桌面端：左侧栏「应用」→「举报管理」（仅管理员可见）→ 列表查看。
API：
- 列表 `GET api/complaint/lists`
- 操作 `POST api/complaint/action`

## 操作步骤
1. 进入「举报管理」，列表按 id 倒序显示
2. 可按「类型」和「状态」筛选
3. 点开一条举报，查看：原因、举报人、对话 ID、附图、提交时间
4. 视情况选择：
   - 「已处理」：把 `status` 改为 1（已处理），保留记录备查
   - 「删除」：从 `complaints` 表硬删除，记录消失

## 处理后做什么
后台动作只改记录状态，**不会**：
- 不会自动封禁被举报的成员
- 不会自动删除被举报的对话或消息
- 不会自动通知任何人

如果确认违规需要进一步行动，要手动到：
- 「团队管理」→ 禁用 / 删除被举报成员账号
- 进入对应群聊把消息撤回 / 群解散
- 必要时上报合规负责人，参考 [[compliance.concept]]

## 类型说明
7 种举报类型说明见 [[abuse-report.concept]]。

## 不支持
- 不支持批量处理多条
- 不支持自动判断是否违规
- 不支持举报转交其他管理员
