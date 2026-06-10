---
id: org-department.deputy-view.howto
title: 切换到部门负责人视角
type: howto
feature: org-department
scope: end-user
locale: zh
aliases:
  - 负责人视角
  - 部门负责人视角
  - 看下属项目
  - 看部门成员项目
  - 切换部门视角
  - 怎么查看下属任务
  - 主管视角
related_tools: []
related_pages: []
prerequisites:
  - 当前用户是某部门的部门负责人（owner_userid）或部门管理员（deputy）
  - 系统管理员已在系统设置中开启「部门负责人视角」（`department_owner_project_view = open`）
negative:
  - 没开启系统开关时，`info/managed_departments` 接口固定返回空数组，前端不显示入口
  - 仅"只读"视角；不能修改项目、添加成员、改任务
  - 项目可单独关闭"部门负责人视角可见"（`projects.department_owner_view = close`），关闭后对负责人隐藏
  - 用户在多个部门同时管理时，弹窗会列出所有可切换部门，可多选或全选
  - 视角不持久跨多设备，是当前会话的偏好（保存在 `cacheDepartmentOwnerIds`）
last_verified: v1.7.90
---

# 切换到部门负责人视角

## 入口
- 桌面端：右上角头像菜单 → 顶部「负责人视角」入口（仅当 `managed_departments` 非空时出现）
- 接口（拉可管理部门）：`GET api/users/info/managed_departments`
- 接口（应用偏好）：`store.dispatch("setDepartmentOwnerIds", ids)`

## 操作步骤
1. 点击右上角头像 → 「负责人视角」
2. 弹窗显示我可管理的部门清单
3. 勾选要"切入"的部门（可多选 / 全选 / 反选 / 清空）
4. 点击「确定」生效

## 切换后效果
- 项目列表：除自己参与的项目外，新增可见所选部门成员参与的项目（标记 `department_readonly=true`）
- 任务列表：可只读浏览这些项目的全员可见任务
- 仪表盘 / 报表：相关聚合数据会把这些部门成员纳入统计范围
- 退出视角：再次打开弹窗 → 「清空」→「确定」

## 接口数据来源
- 我可管理的部门 = 我是 `owner_userid` 的部门 ∪ 我在 `user_department_owners` 表的部门
- 实际看到的项目 = 上述部门**递归全部下级**的成员所在项目（不含已关闭"部门负责人视角可见"的项目）

## 不支持
- 视角无法修改下属数据，所有操作按钮在 readonly 项目内会被隐藏或禁用
- 不能查看下属"全员不可见"的私密任务
- 系统未开启开关时本功能完全不可用

## 相关
- 角色定义：[[org-department.deputy.concept]]
- 任命部门管理员：[[org-department.add-deputy.howto]]
