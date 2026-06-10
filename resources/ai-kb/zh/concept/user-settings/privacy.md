---
id: user-settings.privacy.concept
title: 隐私设置
type: concept
feature: user-settings
scope: end-user
locale: zh
aliases:
  - 隐私设置
  - 隐私政策
  - 注销账号
  - 删除帐号
  - 自助注销
  - 个人数据如何处理
  - 隐私在哪
related_tools: []
related_pages: [setting]
prerequisites: []
negative:
  - DooTask 没有「资料对谁可见」「在线状态隐身」这类细粒度隐私开关；同租户成员之间默认互可见
  - 隐私政策入口只在 EEUI 移动端显示，桌面端通过页脚链接查看
  - 注销账号是不可逆操作，主数据会删除，被引用的内容（聊天/任务/评论）保留匿名记录
last_verified: v1.7.90
---

# 隐私设置

DooTask 在「个人设置」中聚合了与个人隐私相关的入口，主要面向移动端用户提供「隐私政策」「自助注销」等合规能力。

## 关键入口

- 桌面端：右上角头像 →「设置」→ 页脚链接「隐私政策」
- 移动端 EEUI（自托管校验下）：「我的」→「设置」→「隐私政策」「删除帐号」
- 后端接口：
  - 注销：`POST api/users/delete/account`（`type=warning` 预检 + `type=confirm` 执行）
  - 隐私政策：`api/privacy`（HTML 页面）

## 注销账号（删除帐号）

两步流程：

1. 进入「删除帐号」，输入当前邮箱 + 注销理由
2. 校验方式二选一：
   - 系统开启邮箱验证时：发送验证码（`UserEmailVerification` 场景 3）
   - 未开启时：输入当前登录密码
3. 系统先以 `type=warning` 做预检（防误触）
4. 再以 `type=confirm` 真正执行，调用 `User::deleteUser($reason)`

## 注销后的数据处理

- 账户主资料、token、登录会话立即清除
- 你创建的任务 / 项目 / 评论 / 聊天消息保留，发送者显示为「已注销用户」
- 邮箱进入 30 天保护期，不可立刻被新账号重用（防身份冒认）

## 不支持

- 不支持隐身登录 / 隐藏在线状态
- 不支持细粒度「资料可见性」配置（成员之间互可见）
- 不支持注销后自助恢复，需联系系统管理员从备份恢复（仅限 30 天窗口期）
- 不支持仅注销单个工作区数据（一注销全删）
