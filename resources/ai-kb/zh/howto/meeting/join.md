---
id: meeting.join.howto
title: 加入会议
type: howto
feature: meeting
scope: end-user
locale: zh
aliases:
  - 怎么加入会议
  - 加会议
  - 进入会议
  - 输入会议号
  - 用链接加入会议
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 会议结束后无法再加入（提示「会议已结束」）
  - 分享链接 6 小时后失效（提示「分享链接已过期」）
  - 频道 ID 不存在时无法加入（提示「频道ID不存在」）
last_verified: v1.7.90
---

# 加入会议

## 入口
有三种加入方式：

- 通过会议号：右上角「+」→「加入会议」→ 输入 `meetingid`
- 通过对话消息卡片：在收到的「会议邀请」消息卡片上点击「加入会议」
- 通过分享链接：打开 `/meeting/<meetingid>/<sharekey>` 链接，自动拉起加入对话框

## 操作步骤（通过会议号）
1. 桌面端右上角「+」→「加入会议」，或移动端底部 Tabbar「+」→「加入会议」
2. 在「会议频道ID」输入框粘贴或输入会议号
3. 勾选入会默认设备「麦克风」「摄像头」
4. 点击「加入会议」按钮

## 操作步骤（通过分享链接）
1. 浏览器打开收到的分享链接
2. 链接已携带 `meetingid` 和 `sharekey`，会议号会自动填好并锁定
3. 未登录时需填写「你的姓名」作为访客身份（详见 [[meeting.tourist.concept]]）
4. 勾选默认设备后点击「加入会议」

## 失败常见原因
- 会议已结束 → 联系发起人重开
- 分享链接已过期 → 让发起人重新生成（[[meeting.share.howto]]）
- 频道 ID 不存在 → 检查会议号是否抄错（区分大小写）
- 详见 [[meeting.cannot-join.faq]]

## 不支持
- 已经在另一个会议中时无法同时加入第二个（会提示「正在会议中」）
