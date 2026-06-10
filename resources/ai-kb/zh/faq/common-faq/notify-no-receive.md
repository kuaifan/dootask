---
id: common-faq.notify-no-receive.faq
title: 收不到任何通知
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 收不到通知
  - 一条通知都没有
  - 所有通知都没了
  - 通知全静音了
  - 不响也不弹
  - 通知不工作
related_tools: []
related_pages: [user_settings_notification]
prerequisites: []
negative:
  - DooTask 不会主动短信通知，不必去等短信
  - 系统不会自动告知用户「你被免打扰了」；得自己检查设置
  - 「已读」消息不会再次推送，被另一端读完不算故障
last_verified: v1.7.90
---

# 收不到任何通知

## 问题
DooTask 用户表示完全没收到任何通知——桌面不弹、APP 不响、邮件没来。

## 排查顺序
DooTask 有四条独立的通知通道，依次自检：

1. **应用内红点**：左侧聊天列表、任务列表是否有红点。没有 → 后端根本没产生通知，从源头排查（消息真的发给你了？被设了可见用户排除？）
2. **桌面 / Web 通知**：桌面端 / Web 是否拿到系统通知权限，详见 [[desktop-notify.permission.faq]]
3. **移动端 APP 推送**：手机是否给 DooTask 通知权限 + 后台运行；管理员是否配了友盟 [[mobile-notify.permission.faq]] / [[push-notice.troubleshoot.faq]]
4. **邮件通知**：管理员是否配 SMTP；该用户是否在「忽略邮箱」列表里，详见 [[email-notice.troubleshoot.faq]]

## 解决
1. 个人设置 → 通知设置 → 把所有要的通道勾上（参考 [[user-settings.notification.howto]]）
2. 检查个人是否开了「全局免打扰」「勿扰时间段」
3. 检查目标会话 / 项目是否被你单独设了免打扰
4. 重新登录刷新 socket 连接
5. 全公司都没通知 → 让管理员检查 swoole 进程、Redis、SMTP、友盟配置

## 不支持
- 没有「通知历史」可回放所有过去通知的全局界面（仅站内红点 + 邮件）
- 静默消息（如他人「已读不回」）本来就不响，不算故障

详细各通道独立排查见各 feature 的 troubleshoot 文档。
