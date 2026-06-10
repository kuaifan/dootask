---
id: project.invite.howto
title: 项目邀请链接
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 邀请链接
  - 项目邀请
  - 加入项目
  - 邀请别人加项目
  - 邀请码
related_tools: [get_project]
related_pages: [project_settings, project_member]
prerequisites:
  - 是项目拥有者（owner=1）或管理员（owner=2）
  - 站点设置开启 project_invite
negative:
  - 邀请链接通过 code 唯一识别项目，不绑定具体邀请人
  - 链接刷新后旧链接立即失效
  - 接收邀请的用户会自动加入项目，不走审批
last_verified: v1.7.90
---

# 项目邀请链接

## 是什么
DooTask 通过 `ProjectInvite` 表生成项目邀请：每条记录 `project_id + code + num`，其中 `code` 是加密的邀请码，凭它可直接加入项目，无需审批。常用于"把链接发到群里让大家自己加"。

## 入口
- 桌面端：项目设置 → 「成员管理」 → 顶部「邀请链接」
- 桌面端：项目顶部「⋯」 → 「邀请成员」 → 「复制链接」

## 操作步骤
1. 拥有者 / 管理员点击「生成邀请链接」（首次）或「刷新」（已有时换新）
2. 复制链接（或扫描二维码）
3. 把链接发给目标用户
4. 目标用户打开链接登录 → 自动加入项目（owner=0）+ 进 [[project.dialog.concept]] 群聊

## 链接管理
- 一个项目只有一条邀请链接（一对一），刷新后旧链接立即失效
- `num` 字段累计经此链接加入的人数
- 暂时禁用：在系统设置关闭 `project_invite` 开关

## 与「[[project.member.howto]] 直接加成员」的区别

| 维度 | 邀请链接 | 直接加成员 |
|---|---|---|
| 是否要被邀请人配合 | 是（点链接） | 否（直接加） |
| 是否走审批 | 否（直加） | 否（直加） |
| 适合人数 | 多人 / 不确定 | 已知列表 |
| 链接可销毁 | 是（刷新） | 不适用 |

## 不支持
- 邀请链接不能限制有效次数 / 有效期（要禁用必须刷新换新）
- 邀请不能指定加入后的角色，默认 owner=0
- 不支持邮件 / 短信自动发送邀请，需手动复制链接
