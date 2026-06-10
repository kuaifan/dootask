---
id: project.exit.howto
title: 主动退出项目
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 退出项目
  - 离开项目
  - 不想参与项目了
  - 主动退群
  - 取消参与项目
related_tools: [get_project]
related_pages: [project_settings, project_member]
prerequisites:
  - 当前用户是项目成员（非拥有者）
negative:
  - 项目拥有者不能直接退出，必须先 [[project.transfer.howto]] 转让
  - 退出后名下未完成任务的负责人会被自动转给拥有者
  - 退出后仍能看到自己历史发布的消息 / 任务，但失去新通知
last_verified: v1.7.90
---

# 主动退出项目

## 入口
- 桌面端：项目顶部「⋯」 → 「退出项目」
- 桌面端：项目设置 → 「成员管理」 → 自己行 → 「退出项目」
- 移动端：项目详情右上角菜单 → 「退出」

## 操作步骤
1. 选「退出项目」
2. 弹窗显示：「退出后名下 N 个未完成任务将转给项目拥有者」
3. 确认后服务端：
   - 删 ProjectUser 自己一行
   - 把名下 ProjectTaskUser 命中的 owner=1 任务转给项目拥有者（assist 不变）
   - 同步移出 [[project.dialog.concept]] 群聊
   - ProjectLog 记一条「X 退出了项目」

## 拥有者想退出怎么办
1. 先转让拥有者：[[project.transfer.howto]] 把 owner=1 给别人
2. 转后自己变 owner=0，再走退出流程
3. 没有合适接班人 → 不能退出，只能删项目（[[project.archive-delete.howto]]）

## 退出后能看到什么
- 历史项目动态、消息、自己发的内容仍在数据库
- 但你的左侧栏 / 项目列表中看不到该项目
- 站点搜索仍可能命中你发的内容（按权限筛）

## 不支持
- 不能"暂时离开"（一退就是永久，再加回来要 [[project.member.howto]]）
- 不能保留消息通知（退出后该项目所有推送停止）
- 个人项目（[[project.personal.concept]]）不能退出，只能删
