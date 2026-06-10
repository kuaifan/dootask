---
id: mobile-notify.permission.faq
title: 移动端没有通知权限
type: faq
feature: mobile-notify
scope: end-user
locale: zh
aliases:
  - 手机不弹通知
  - APP 没声音
  - 通知权限
  - iOS 不弹通知
  - Android 收不到
  - 后台杀进程
  - 通知没振动
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 通知权限拒绝过一次后 DooTask 不会主动再次弹窗请求；只能去系统设置改
  - 即便授予通知权限，国产 ROM 的电池优化 / 自启动限制仍可能屏蔽后台通知
  - 通知权限和「APP 后台运行」是两件事，二者都允许才稳定收推送
last_verified: v1.7.90
---

# 移动端没有通知权限

## 问题
DooTask 移动端 APP 收不到通知 / 没振动 / 锁屏后没推送。

## 原因
- 系统通知权限未授予
- 国产 ROM 后台杀进程严格，APP 被关闭
- DooTask 时段免打扰打开
- 网络异常导致推送未达（友盟服务器无法触达设备）

## 解决（iOS）
1. 「设置 → DooTask」
2. 点「通知」
3. 开启「允许通知」
4. 勾选「锁定屏幕」「通知中心」「横幅」全部样式
5. 「声音」「角标」也建议开启
6. 「设置 → 通用 → 后台 APP 刷新」中允许 DooTask
7. 「设置 → 蜂窝网络」允许 DooTask 使用流量

## 解决（Android 原生）
1. 「设置 → 应用 → DooTask → 通知」
2. 「允许通知」开启
3. 「通知类别」中所有渠道都开启
4. 「设置 → 应用 → DooTask → 电池」选择「不受限制」
5. 「设置 → 应用 → 特殊应用访问 → 设备管理员」或「自启动管理」中允许 DooTask

## 解决（华为 / 荣耀）
1. 「设置 → 应用 → 应用启动管理」→ DooTask → 手动管理 → 允许「自启动」「关联启动」「后台活动」
2. 「设置 → 通知 → DooTask」→ 允许通知 + 锁屏显示

## 解决（小米 / Redmi）
1. 「设置 → 应用设置 → 应用管理 → DooTask」
2. 「自启动」打开
3. 「省电策略」选「无限制」
4. 「通知管理」全开

## 解决（OPPO / VIVO / 一加）
1. 通用做法：应用详情中开启「自启动」「关联启动」「后台高耗电」
2. 通知中允许「锁屏通知」「横幅」
3. VIVO 的「i 管家 → 应用管理 → 后台高耗电」中允许 DooTask

## 仍然不收？
1. 检查 DooTask 内时段免打扰是否打开（[[mobile-notify.silent.howto]]）
2. 检查会话是否被免打扰（[[push-notice.silent.howto]]）
3. 检查 APP 内是否已登录（退出登录后 UmengAlias 失效）
4. 联系管理员查 `umeng_logs` 看友盟侧是否收到了推送请求
5. 详见 [[push-notice.troubleshoot.faq]]
