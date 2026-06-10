---
id: common-faq.notify-mobile-fail.faq
title: 手机收不到通知
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 手机不响
  - 手机收不到
  - APP 没推送
  - 锁屏没消息
  - 手机不弹
  - 安卓收不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - PC 在线时 APP 推送会延迟 10 秒；这期间被读则跳过推送，不是故障
  - 静默消息（silence=1）和自己发的消息本来不推
  - 友盟别名 30 天不启动 APP 会过期，需重新登录刷新
last_verified: v1.7.90
---

# 手机收不到通知

## 问题
桌面 / Web 端可以正常收到 DooTask 消息，但移动 APP 后台 / 退出登录状态下没推送提醒、没振动、锁屏空白。

## 排查顺序

1. **当前会话是不是免打扰**：APP 内打开聊天右上角看免打扰开关
2. **系统通知权限**：iOS / Android 是否给 DooTask 通知权限，详见 [[mobile-notify.permission.faq]]
3. **国产 ROM 后台杀进程**：华为 / 小米 / OPPO / VIVO 需手动允许 DooTask「自启动 + 后台运行」
4. **PC 端 60 秒内活跃过**：DooTask 推送规则会延迟手机 10 秒，期间消息被 PC 读完会跳过 APP 推送（设计行为）
5. **管理员有没配友盟**：管理后台 → APP 推送 → 检查 iOS/Android Appkey + Master Secret 是否填好
6. **友盟别名失效**：卸载 APP / 30 天没启动 → 重新登录 APP 触发 UmengAlias 注册
7. **会话级 / 项目级免打扰**：在 APP 内对应会话 / 项目设置里检查

## 解决
1. 退出 APP 重新登录刷新 token + 友盟别名
2. 关掉 PC 端临时测试（断网或退出账号）→ 验证 APP 是否能即时收
3. 找系统管理员看 `umeng_logs` 表的最新返回；详见 [[push-notice.troubleshoot.faq]]
4. 国产 ROM 详细步骤见 [[mobile-notify.permission.faq]]

## 不支持
- APP 无「重发上次推送」按钮，需对方重新触发消息
- 已读消息不再推送，跨设备打开 PC 读完不会再响 APP
- 没有手机震动 / 声音的精细自定义，跟系统通知设置走

## 检查清单（用户自助）
- [ ] iOS 设置 → DooTask → 通知 → 允许通知 + 锁屏 + 横幅
- [ ] Android 设置 → DooTask → 通知 + 自启动 + 电池不受限
- [ ] APP 内退出账号 → 重新登录
- [ ] 群聊免打扰开关确认关闭
