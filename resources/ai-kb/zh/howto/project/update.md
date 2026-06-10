---
id: project.update.howto
title: 编辑项目基本信息与设置
type: howto
feature: project
scope: end-user
locale: zh
aliases:
  - 改项目名
  - 改项目描述
  - 项目设置
  - 自动归档项目
  - AI 自动分析项目
related_tools: [update_project]
related_pages: [project_settings]
prerequisites:
  - 是项目拥有者（owner=1）或项目管理员（owner=2）
negative:
  - 普通成员（owner=0）无法编辑项目设置
  - 改名不影响 dialog_id 群聊的标题（需单独改群名）
  - 关闭 AI 自动分析后已生成的分析结果不会清除
last_verified: v1.7.90
---

# 编辑项目基本信息与设置

## 入口
- 桌面端：项目顶部「⋯」菜单 → 「项目设置」
- 移动端：项目详情页右上角齿轮图标

## 可编辑字段
| 字段 | 含义 | 谁能改 |
|---|---|---|
| name | 项目名称 | 拥有者 / 管理员 |
| desc | 项目描述 | 拥有者 / 管理员 |
| archive_method | 自动归档方式（disable / custom） | 拥有者 / 管理员 |
| archive_days | 自动归档天数（complete_at 多久后） | 拥有者 / 管理员 |
| ai_auto_analyze | AI 自动分析任务 | 拥有者 / 管理员 |
| task_template_share | 任务模板共享（[[task.template.howto]]） | 拥有者 / 管理员 |
| department_owner_view | 部门负责人视角（只读） | 拥有者 / 管理员 |

## 操作步骤
1. 进入项目设置面板
2. 改对应字段
3. 点「保存」，服务端 update + WebSocket 推送

## 自动归档
- `archive_method=disable`：从不自动归档
- `archive_method=custom`：任务完成 `archive_days` 天后自动归档（[[task.archive.howto]]）
- 归档后任务从默认视图收起，但保留全部数据

## 不支持
- 不能改 personal 字段（个人 ↔ 团队项目不可互转，详见 [[project.personal.concept]]）
- 不能改 userid（创建人），只能 [[project.transfer.howto]] 转拥有者
- 不能给项目改 dialog_id（群聊重建需要管理员介入）
