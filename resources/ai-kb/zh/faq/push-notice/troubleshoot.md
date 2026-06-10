---
id: push-notice.troubleshoot.faq
title: APP 收不到推送怎么办
type: faq
feature: push-notice
scope: end-user
locale: zh
aliases:
  - 收不到推送
  - APP 没声音
  - 通知不响
  - 手机没消息
  - 推送失败
  - 后台推送不到
related_tools: []
related_pages: []
prerequisites: []
negative:
  - DooTask 内部没有「重发推送」按钮
  - 推送日志只有管理员通过查库 umeng_logs 才能看
  - 静默消息和自己发的消息本来就不推，不算故障
last_verified: v1.7.90
---

# APP 收不到推送怎么办

## 问题
DooTask 移动端 APP 后台 / 退出登录状态下没收到新消息通知。

## 排查顺序
1. **管理员有没有配友盟？**
   - 进「管理后台 → 系统设置 → APP 推送」检查「开启推送」= 开启
   - 检查 iOS / Android 的 Appkey 和 Master Secret 非空
2. **当前会话是不是免打扰？**
   - 打开对应聊天 → 右上角设置 → 检查「免打扰」是否打开
   - 免打扰时该会话不推
3. **APP 是不是开了通知权限？**
   - iOS：「设置 → DooTask → 通知 → 允许通知」
   - Android：「设置 → 应用 → DooTask → 通知 → 允许全部」
   - 详见 [[mobile-notify.permission.faq]]
4. **是不是 PC 在线被延迟了？**
   - PC 端 60 秒内活跃过，APP 推送会被延迟 10 秒
   - 这期间消息被读则**跳过**推送；这是设计行为
5. **是不是别名失效了？**
   - 卸载 APP / 30 天没启动会让 UmengAlias 过期
   - 重新打开 APP 登录会触发别名注册（自动刷新 updated_at）
6. **是不是国产 ROM 的厂商通道问题？**
   - 华为/小米/OPPO/VIVO 后台杀进程严格，需在系统中允许「自启动 + 后台运行」
   - 友盟厂商通道（mipush 等）需在友盟控制台配证书，并使用正式签名的 APP

## 系统级排查（管理员）
- 用「邮件发送测试」类比的思路验证一条消息能不能推（无界面，需查 `umeng_logs` 表）
- `umeng_logs.response` 字段记录友盟接口返回，按错误码定位
- 临时关闭 PC 端登录测试 APP 端是否能直接收到（排除延迟优化）

## 不算故障的情形
- 静默消息（silence=1）：刻意不推
- 自己发的消息：不推自己
- 已读消息：延迟推送时再次检查已读则跳过

## 仍然不行
联系系统管理员把对应时段的 `umeng_logs` 反给运维分析。
