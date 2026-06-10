---
id: ai-assistant.tool-permission.faq
title: AI 提示没权限操作怎么办
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 没权限
  - AI 提示权限不足
  - AI 不能改这个
  - AI 操作被拒
  - AI 看不到这个任务
  - AI 不能访问
related_tools: []
related_pages: []
prerequisites: []
negative:
  - AI 不能绕过 DooTask 权限体系，调工具同样走后端鉴权
  - 没有「AI 超级模式」开关，AI 的权限始终等同当前登录用户
  - 普通用户无法自助提权，必须由管理员或负责人调整
last_verified: v1.7.90
---

# AI 提示没权限操作怎么办

## 问题
AI 工具调用气泡显示「无权限」「您不在可见用户列表」「不是项目成员」等错误，AI 回复说"无法帮您完成"。

## 原因
AI 以你当前登录身份调用所有工具，后端的所有权限校验都生效：

- **项目级**：非项目成员看不到/不能改项目内任务
- **任务级**：任务设了可见用户白名单，名单外用户即使是项目成员也看不到
- **角色级**：改项目设置 / 删列 / 改成员需要项目负责人或管理员
- **系统级**：装插件 / 改系统设置 / 看导出 / 管理用户需要系统管理员（userIsAdmin）
- **超级管理员**：超管专属功能仅 id=1 的用户可用

## 解决
1. 确认操作类型对应哪一级权限
2. 联系对应角色补加权限：
   - 项目级 → 联系项目负责人加你为成员或改角色
   - 任务级 → 联系任务负责人把你加入可见用户
   - 系统级 → 联系系统管理员
3. 部分功能依赖插件已安装（如审批要 approve 插件）
4. 让 AI 换种方式（如不能 update_task 可改成 send_message 通知负责人改）

## 怎么看自己的权限
- 头像 → 「个人设置」可看部门 / 角色
- 系统管理员 / 部门负责人身份会显示在右上角下拉菜单顶部

## 相关
- 通用权限不足：[[role-permission.permission-denied.faq]]
- 工具调用失败：[[ai-assistant.tool-failed.faq]]
