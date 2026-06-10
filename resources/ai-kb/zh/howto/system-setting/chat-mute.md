---
id: system-setting.chat-mute.howto
title: 群组与私聊禁言设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 全员群禁言
  - 群聊禁言
  - 私聊禁言
  - 怎么禁止发消息
  - all_group_mute
  - user_private_chat_mute
  - user_group_chat_mute
  - 关闭私聊
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 三个开关相互独立，不会联动；要全静音得分别关
  - 部门群、项目群等系统群不受 user_group_chat_mute 控制，永远可发言（除非加入全员群禁言）
  - 系统管理员永远可发言，不受任何开关限制
last_verified: v1.7.90
---

# 群组与私聊禁言设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「消息相关」。

涉及三个独立字段：

| 字段 | UI 标签 | 默认 | 控制范围 |
|---|---|---|---|
| `all_group_mute` | 全员群组禁言 | `open` | 系统「全员群」（`group_type=all`） |
| `user_private_chat_mute` | 私聊禁言 | `open` | 个人对个人的私聊（`type=user`） |
| `user_group_chat_mute` | 群聊禁言 | `open` | 个人自建群（`group_type=user`） |

每个字段值都是 `open`（开放发言）/ `close`（禁言）。

## 禁言粒度与谁能发

- `all_group_mute=close`：除系统管理员外，所有人都不能在全员群发言；管理员仍可发
- `user_private_chat_mute=close`：禁止任何成员发起 / 继续个人私聊；只有管理员可发
- `user_group_chat_mute=close`：个人自建群禁言；管理员仍可发；**部门群、项目群、会议群等系统群不受影响**

后端 `WebSocketDialog::checkMute` 会按会话类型路由到对应开关。被禁言时尝试发消息会收到「个人会话禁言 / 当前会话全员禁言 / 个人群组禁言」错误。

## 能不能解禁
能。任何字段从 `close` 改回 `open` 后立即恢复发言能力，不需要重启或重新登录。对历史已发 / 未发消息没有任何回溯影响。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「消息相关」
2. 分别在「全员群组禁言」「私聊禁言」「群聊禁言」选「开放」或「禁言」
3. 「提交」保存，立即生效

## 不支持
- 不能临时禁言某个具体用户（用群管理员的踢人 / 群规则代替）
- 不能定时禁言（如"晚上 22 点后禁言"）
- 不影响机器人推送：机器人消息走系统接口，不走聊天禁言判定
