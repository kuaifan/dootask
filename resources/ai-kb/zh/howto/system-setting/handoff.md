---
id: system-setting.handoff.howto
title: 设置任务流转与部门指派权限
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 怎么开启任务流转
  - 设置部门负责人指派范围
related_tools: []
related_pages: []
prerequisites:
  - 当前账号是系统管理员
negative:
  - 不开放非项目成员指派或隐式加入项目
last_verified: v1.9.18
---

# 设置任务流转与部门指派权限

## 入口
系统设置 → 任务设置 → 流转设置。配置全局生效，由系统管理员统一维护。

## 设置项
| 设置 | 选项 | 默认 |
|---|---|---|
| 任务流转 | 开启、关闭 | 关闭 |
| 部门视角指派权限 | 关闭、仅部门负责人、部门负责人及部门管理员 | 仅部门负责人 |
| 可指派人员范围 | 管理部门范围内的项目成员、项目内全部成员 | 管理部门范围内的项目成员 |
| 原负责人调整范围 | 仅调整管理范围内负责人、允许调整全部负责人 | 仅调整管理范围内负责人 |
| 指派留言要求 | 选填、必填 | 选填 |

## 生效范围
- 设置键依次为 `project_task_handoff`、`project_task_handoff_role`、`project_task_handoff_candidates`、`project_task_handoff_adjust`、`project_task_handoff_note`，由系统设置接口统一读写。
- 总开关开启才显示其余配置及任务详情「流转」标签，提供有权限的指派入口。
- 部门权限仍依赖系统及项目允许负责人视角；管理范围包含符合角色授权的部门及其下级部门，并与当前所选部门范围取交集。
- 候选人和原负责人调整范围仅约束部门视角新增授权，不收紧原有项目角色权限。
- 留言要求用于流转窗口人工指派，不阻断原负责人编辑、领取或自动工作流。
- 关闭不删除记录，原有任务操作及变更留痕继续工作。

## 不支持
不支持非项目成员指派、审批、接单或通过部门权限查看私密任务。流转操作见 [[task.handoff.howto]]。
