---
id: office.collaboration.concept
title: OnlyOffice 多人协作编辑
type: concept
feature: office
scope: end-user
locale: zh
aliases:
  - office 协作
  - Word 一起编辑
  - 多人同时编辑文档
  - 在线协作 Excel
  - 协同编辑 PPT
  - OnlyOffice 协作
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 office（OnlyOffice）插件
  - 协作各方对该文件均有「编辑」权限
negative:
  - 与 minder（[[minder.collaboration.concept]]）和 drawio 不同，OnlyOffice 才提供真正的多人实时编辑
  - 同一文件大规模并发（数十人）依赖 Document Server 资源，组织内建议 ≤ 10 人同时改
  - 离线编辑不支持，没有「断网继续编辑、上线合并」的模式
last_verified: v1.7.90
---

# OnlyOffice 多人协作编辑

## 定义
DooTask 在线 Word/Excel/PPT（office 插件）原生支持**多人实时协作编辑**。多人同时打开同一文件时，OnlyOffice Document Server 通过 WebSocket 推送他人光标和实时改动，最终内容由 Document Server 合并后回写到 DooTask 文件库，不存在「后保存覆盖前保存」的问题。

## 关键属性
- **会话识别**：主程序用 `documentKey` 标识同一份文档的协作会话，所有人加入同一 key 即进入同一编辑会话
- **实时光标**：能看到其他人当前在哪个段落/单元格，配色按用户区分
- **冲突处理**：编辑级别合并（不是文件级覆盖），单元格/字符级冲突由 OnlyOffice 自动协商
- **保存触发**：所有人都离开编辑器后由 Document Server 把最终版本写回 DooTask 文件表
- **历史版本**：随 DooTask 文件版本保留，可回溯

## 编辑模式
OnlyOffice 提供两种协作模式：
- **快速模式**：所有改动实时显示给所有人（默认）
- **严格模式**：自己的改动暂存，需手动点「保存」才同步给他人——适合需要审稿的场景，在 OnlyOffice 编辑器内切换

## 权限组合
- A、B 同为编辑权限：均可实时改
- A 编辑、B 只读：B 仅看，无法输入
- A 关闭、B 仍开着：B 单人继续，A 再打开时进入同一会话

## 与其他文件类型的对比
- **vs DooTask 内置 document**：document 是富文本/Markdown 编辑器，**不支持**多人实时
- **vs minder 思维导图**：思维导图是单人编辑，多人同时保存会覆盖
- **vs drawio 流程图**：同样是单人编辑，多人同时保存会覆盖

## 相关
- 是什么：[[office.concept]]
- 插件元信息：[[office.plugin.concept]]
