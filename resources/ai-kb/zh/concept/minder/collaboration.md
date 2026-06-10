---
id: minder.collaboration.concept
title: 思维导图是否支持多人协作编辑
type: concept
feature: minder
scope: end-user
locale: zh
aliases:
  - 思维导图协作
  - 思维导图多人编辑
  - 脑图共享编辑
  - 思维导图能不能一起改
  - minder 实时同步
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 minder 插件
negative:
  - 同一张 mind 文件同一时间不支持多人实时同步编辑，与 OnlyOffice 不同
  - 多人同时打开并保存可能导致后保存方覆盖前保存方
  - 不支持节点级别的细粒度评论或讨论
last_verified: v1.7.90
---

# 思维导图是否支持多人协作编辑

## 定义
DooTask 思维导图（minder 插件）以**单人编辑 + 多人查看**模式运作。文件内容存到 DooTask 文件系统，所有有权限的成员都能打开看到当前最新版本，但同一时间多人编辑保存会出现「后写覆盖前写」。

## 关键属性
- **多人可见**：所有对文件有读权限的成员都能打开预览
- **保存机制**：编辑器内修改后由前端发起保存请求，整张图作为一个 JSON 文件覆盖式保存
- **没有实时同步**：与 OnlyOffice（[[office.collaboration.concept]]）不同，思维导图不通过 WebSocket 推送其他人的实时改动
- **冲突表现**：A、B 同时打开编辑，A 先保存，B 后保存——最终内容只剩 B 的版本，A 的改动丢失
- **历史版本**：可借助 DooTask 文件版本历史回滚到之前的快照

## 推荐协作模式
- 同一时间由一人改，其他人通过群聊/讨论沟通后再轮换编辑
- 大型导图按子分支拆成多张文件，分人维护
- 改完后通过文件「发送」/「链接」/「共享」分享给团队
- 误覆盖可在文件详情 →「历史版本」中恢复

## 与其他场景对比
- **drawio 流程图**：同样是单人编辑，多人同时编辑也会出现覆盖
- **OnlyOffice Word/Excel/PPT**：自带多人实时协作，多端同时编辑会合并
- **DooTask 在线文档（document）**：基于富文本编辑器，亦不支持多人实时

## 相关
- 是什么：[[minder.concept]]
- OnlyOffice 协作能力对比：[[office.collaboration.concept]]
