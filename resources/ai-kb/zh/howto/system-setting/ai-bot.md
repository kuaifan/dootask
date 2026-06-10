---
id: system-setting.ai-bot.howto
title: AI 机器人与默认模型
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - AI 机器人
  - 默认 AI 模型
  - 绑定机器人
  - AI 机器人怎么配
  - 全局默认模型
  - aibot
  - defmodels
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 应用市场已安装并启用 ai 插件
  - 已在「AI 设置」配置过至少一个模型供应商
negative:
  - 未安装 ai 插件时菜单不出现，调用接口会直接抛错
  - aibot 设置不影响群机器人 @ 时使用的模型，群机器人有独立绑定
  - 该接口在 SYSTEM_SETTING=disabled 环境会拒绝写入
last_verified: v1.7.90
---

# AI 机器人与默认模型

## 入口
桌面端：左上角头像 →「系统设置」→「AI 机器人」。
对应后端：`POST api/system/setting/aibot`，参数 `type=save`。

## 是什么
「AI 机器人」（aibotSetting）是把 AI 能力对外露出的人格层：

- 选定某个**模型供应商 + 具体模型**作为系统默认
- 选定一个或多个**机器人账号**承载 AI 身份（在群聊里显示头像/昵称）
- 控制哪些场景默认走哪个模型（任务分析、报告、对话等）

具体字段由 ai 插件动态注入，服务端只接受 `setting` 已存在的键，未知键直接丢弃。

## 操作步骤
1. 先在 [[system-setting.ai-model.howto]] 配好模型
2. 进入「AI 机器人」页
3. 「默认模型」下拉选择一个已配置的模型
4. 「绑定机器人」选择系统机器人（如「智能助手」）
5. 保存 → 提示「保存成功」即生效

## 相关接口
- `setting__aibot` — 主配置读写（仍生效）
- `setting__aibot_models` — 已废弃（v1.4.35+，列表改由 ai 插件提供）
- `setting__aibot_defmodels` — 已废弃（同上）

调用 `setting__aibot` 时可传 `filter=<prefix>`，仅返回以 prefix 开头的字段（便于客户端按场景拉取）。SYSTEM_SETTING=disabled 时 `_key`、`_secret` 结尾的字段会被打码。

## 不支持
- 不能在此处新增模型供应商（在 [[system-setting.ai-model.howto]] 操作）
- 不支持按用户级别绑定模型，仅支持系统级默认
- SYSTEM_SETTING=disabled 时无法查看完整密钥：所有 `_key/_secret` 字段会以 4+****+4 形式脱敏
