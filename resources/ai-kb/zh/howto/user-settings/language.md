---
id: user-settings.language.howto
title: 切换界面语言
type: howto
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 改语言
  - 换语言
  - 切换中文
  - 切换英文
  - 改成繁体
  - 怎么变英文
  - language switch
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - 语言切换只影响当前账号，不会改其他成员
  - 不影响其他用户给你发的消息（消息不会自动翻译，要单独用「消息翻译」功能）
  - 某些插件 / 微应用可能未提供完整翻译，会回退到中文 / 英文
last_verified: v1.7.90
---

# 切换界面语言

## 入口

- 桌面端：右上角头像 →「设置」→「语言设置」
- 移动端：「我的」→「设置」→「语言设置」
- 后端接口：`POST api/users/editdata`（`lang` 字段）

## 支持的语言

由 `resources/assets/js/language/` 下文件清单决定，常见：

- 简体中文（zh）
- 繁体中文（zh-CHT）
- English（en）
- 其他按部署版本提供（如日语 / 韩语 / 越南语等）

实际列表在「选择语言」下拉中展示，由 `languageList` 统一控制。

## 操作步骤

1. 进入「语言设置」子页
2. 在「选择语言」下拉选目标语言
3. 点击「提交」后立即热切换，无需刷新；浏览器 localStorage 会缓存为 `__system:languageName__`
4. 服务端同步保存 `users.lang` 字段，登录其他终端时按此自动恢复

## 影响范围

- 所有 `$L("...")` 包裹的界面文字
- 系统通知 / 邮件标题中带 `language` 参数的部分
- AI 助手回答会取当前语言作为弱提示词

## 不影响

- 已发出的聊天消息（消息内容不会自动翻译）
- 其他用户看到你的昵称 / 资料（按对方语言显示）
- 文件名 / 任务名 / 评论原文
