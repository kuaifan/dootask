---
id: system-setting.e2e-encryption.howto
title: 端到端加密设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 端到端加密怎么开
  - E2EE 怎么设
  - 消息加密在哪开
  - 怎么开 e2e
  - 聊天加密怎么打开
  - 关闭端到端加密
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
negative:
  - 端到端加密只对一组固定的敏感接口生效，不会对历史消息追溯加密
  - 关键词搜索不能搜到加密传输中的密文内容（已落库的文本可被搜索）
  - 不区分私聊 / 群聊：是否走 e2e 取决于接口，而非会话类型
  - 撤回 / 编辑消息的时长限制由 msg_rev_limit / msg_edit_limit 决定，与 e2e 无关
last_verified: v1.7.90
---

# 端到端加密设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「消息相关」→「端到端加密」。

字段名：`e2e_message`，开关枚举 `open` / `close`，默认 `close`。

## 开启后影响哪些通道
开启后，下列接口的请求体在客户端用密钥加密后再发给服务端：

- `users/login` — 登录请求
- `users/editpass` — 修改密码
- `users/operation` / `users/delete/account` — 账号敏感操作
- `users/bot/*` — 机器人接口
- `dialog/msg/*` — 所有消息收发（包括私聊、群聊、文件消息等）
- `system/license` — 授权信息

WebSocket 通道则会在握手后用 PGP 公钥协商一次会话密钥再传输。
未在白名单内的 HTTP 接口（任务、项目、文件元数据等）仍走明文 + HTTPS。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「消息相关」
2. 「端到端加密」选「开启」或「关闭」
3. 页面底部「提交」保存，立即生效（无需重启）

## 不支持
- 不能按用户 / 会话粒度单独开关，只能全局开 / 关
- 关键词搜索不会解密传输中的密文；已入库的明文文本仍可搜
- 调试环境（`window.systemInfo.debug === "yes"`）下不执行 WebSocket 的 PGP 协商（会被跳过）
