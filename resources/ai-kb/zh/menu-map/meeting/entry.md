---
id: meeting.entry.menu-map
title: 在哪打开会议
type: menu-map
feature: meeting
scope: end-user
locale: zh
aliases:
  - 会议入口
  - 会议在哪
  - 怎么打开会议
  - 视频会议入口
  - 加入会议在哪里
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 会议没有放在左侧主导航一级菜单
  - 没有独立的全局快捷键查看历史会议（历史以会议消息卡片形式留在对话里）
last_verified: v1.7.90
---

# 在哪打开会议

## 路径
DooTask 在线会议有多个入口，按使用场景就近选择：

- 桌面端：右上角全局「+」→「新会议」或「加入会议」（快捷键 Cmd/Ctrl + J 直开新会议）
- 桌面端：左侧栏「应用」→ 应用中心「会议」卡片 →「创建会议 / 加入会议」
- 桌面端：消息对话窗口底部「+」→「会议」（按会话成员自动预填邀请名单）
- 桌面端：消息列表中长按某个联系人 →「发起会议」
- 移动端：底部 Tabbar「+」按钮 →「新会议 / 加入会议」
- 移动端：对话窗口底部「+」→「会议」
- 浏览器：直接打开分享链接（路径形如 `/meeting/<meetingid>/<sharekey>`）也可拉起加入对话框

## 权限要求
- end-user 即可创建和加入会议，无需管理员权限
- 但管理员必须先在「系统设置 → 会议」配置 appid 和密钥后，功能才可用

## 相关
- 详细新建步骤：[[meeting.create.howto]]
- 加入步骤：[[meeting.join.howto]]
- 从对话发起会议：[[meeting.from-dialog.howto]]
