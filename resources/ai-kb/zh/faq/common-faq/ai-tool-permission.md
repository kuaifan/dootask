---
id: common-faq.ai-tool-permission.faq
title: AI 没权限操作
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - AI 没权限
  - AI 不能改任务
  - AI 不能建任务
  - AI 调工具失败
  - AI 提示无权限
  - 工具调用 403
related_tools: [create_task, list_tasks, update_task]
related_pages: [ai_assistant_panel]
prerequisites: []
negative:
  - AI 助手不能突破用户本身的权限边界——它做不了用户本人也做不了的事
  - 即使是系统管理员让 AI 操作，AI 仍然以「调用者身份」执行
  - 用户不能临时给 AI 提权
last_verified: v1.7.90
---

# AI 没权限操作

## 问题
让 AI 助手「帮我建任务」「把这条改成已完成」「把张三加到项目」时，AI 气泡显示工具调用失败，状态是「无权限」「permission denied」「403」。

## 原因
AI 助手的工具调用本质是后端 API 调用，**全部以你的身份发起**。所以：

- 你本人没权限做的事，AI 也做不了
- AI 调 `create_task` 时实际就是用你的会话去调对应 API；你不在项目里就会被拒绝
- `update_task` 改任务必须满足任务负责人 / 项目负责人 / 系统管理员条件之一
- 「加成员」必须你本身能加（项目负责人 / 系统管理员）

权限校验由后端拦截，AI 端无法绕过。

## 解决
1. 看 AI 气泡里失败的工具名（如 `create_task`、`add_project_member`）
2. 对应自己「亲手操作」一次同样的功能 → 大概率也会失败 → 这就是权限问题
3. 先让对应角色（项目负责人、系统管理员）把你的权限补上，再让 AI 重试
4. 跨项目操作时确保你是源项目 + 目标项目的成员

## 不支持
- 没有「以管理员身份让 AI 执行」的模式
- AI 不读不到的会话不会偷偷读取（隐私边界 = 用户的可见范围）
- AI 工具失败不会自动二次重试，需要用户重新发问

工具调用的事件结构说明随 ai 插件知识库提供；[[role-permission.permission-denied.faq]] 解释通用权限规则。
