---
id: system-setting.general.howto
title: 通用设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 通用设置
  - 注册开关
  - 是否允许注册
  - 邀请码
  - 密码策略
  - 消息撤回时长
  - 消息编辑时长
  - 自动归档
  - 系统名称
  - 欢迎语
  - 上传大小限制
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 部署的环境变量 SYSTEM_SETTING 不能是 disabled，否则禁止修改
negative:
  - SYSTEM_SETTING=disabled 时所有系统设置都不能改（演示环境常用）
  - 自动归档天数限制为 1-100 天，超出会报错
  - 邀请码留空会自动生成随机码，不能完全关闭"凭码注册"模式
last_verified: v1.7.90
---

# 通用设置

## 入口
桌面端：左上角头像 →「系统设置」→「通用」。
对应后端：`POST api/system/setting`，参数 `type=save` 提交。

## 关键字段（按主题分组）

**注册与登录**
- `reg` — 注册策略：`open` / `close` / `invite`
- `reg_identity` — 新注册用户默认身份（`normal` / `temp`）
- `reg_invite` — 邀请码（留空自动生成）
- `login_code` — 登录验证码策略
- `password_policy` — 密码强度（`simple` / `complex`）

**项目与任务**
- `project_invite` / `project_add_permission` / `project_add_userids` — 项目创建与邀请权限
- `auto_archived` / `archived_day` — 任务自动归档与天数（1-100）
- `task_visible` / `task_default_time` / `task_user_limit` — 任务默认值
- `unclaimed_task_reminder*` — 未认领任务提醒
- `task_ai_auto_analyze` — 任务 AI 自动分析（需 ai 插件）
- `department_owner_project_view` — 部门负责人是否能看下属项目
- `todo_set_permission` — 设置 todo 权限

**消息与群组**
- `chat_information` — 群成员入群提示
- `anon_message` / `e2e_message` — 匿名 / 端到端加密
- `msg_rev_limit` / `msg_edit_limit` — 撤回/编辑时长上限（分钟）
- `all_group_mute` / `all_group_autoin` — 全员群免打扰 / 自动入群
- `user_private_chat_mute` / `user_group_chat_mute` — 默认免打扰

**媒体与上传**
- `convert_video` / `compress_video` — 视频转码与压缩
- `image_compress` / `image_quality` / `image_save_local` — 图片处理
- `file_upload_limit` — 单文件上传大小（MB）

**外观**
- `system_alias` / `system_welcome` — 系统名称别名与欢迎语

## 操作步骤
1. 进入「系统设置」→「通用」
2. 按表单逐项填写（不在白名单内的字段会被服务端自动剔除）
3. 点击「保存」，服务端落库到 `setting` 表（`system` 分组）
4. 提示「保存成功」即生效，无需重启

## 不支持
- 无法在 `SYSTEM_SETTING=disabled` 环境保存（一律拒绝）
- 注册开关只有 3 个枚举值，不支持「按邮箱白名单」直接配置
