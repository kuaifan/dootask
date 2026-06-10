---
id: user-account.import.howto
title: 批量导入用户
type: howto
feature: user-account
scope: admin
locale: zh
aliases:
  - 批量导入用户
  - 批量创建账号
  - 一次建多个用户
  - 上传 excel 用户
  - 用户导入模板
  - 导入员工
related_tools: []
related_pages: []
prerequisites:
  - 必须是系统管理员（userIsAdmin）才能调 import / import_preview / import_template
negative:
  - 仅支持 xls / xlsx / csv 文件，单次最多导入 500 条
  - 不支持用导入更新已有用户；同邮箱已存在的行会标错跳过
  - 不支持在文件里指定头像 / 电话等扩展字段，列只有邮箱、昵称、初始密码、职位
  - 必须先预览解析确认，再确认导入；直接 import 不带 rows 会报「没有可导入的数据」
last_verified: v1.7.90
---

# 批量导入用户

## 入口
- 桌面端：左上角头像 / 昵称下拉菜单 →「团队管理」→ 成员列表上方的「批量导入」按钮
- 仅系统管理员可见

## 操作步骤
1. 点「下载导入模板」获取官方模板（对应 `api/users/import/template`）
2. 按模板列顺序填写每一行（列顺序固定）：
   - 第 1 列 邮箱（必填，未被注册）
   - 第 2 列 昵称（必填，2-20 字）
   - 第 3 列 初始密码（必填，6-32 位，满足密码策略）
   - 第 4 列 职位（选填，填写则需 2-20 字）
3. 上传文件（仅支持 xls / xlsx / csv，单次最多 500 行），系统先调 `api/users/import/preview` 解析校验（不创建账号）
4. 预览页逐行显示校验结果（邮箱重复、字段缺失、密码不合法等），确认后点「确认导入」调 `api/users/import` 实际创建
5. 可勾选「首次登录强制改密码」（默认勾选，对应 changepass=1）；预览中可按行调整邮箱认证状态（email_verity）

## 失败行的处理
- 预览阶段会标出每行解析错误（邮箱重复、字段缺失、密码不合法等）
- 导入阶段返回 `result`，包含成功条数和失败条数清单
- 失败的行不影响成功的行，可修正后单独再传

## 不支持
- 不支持「导入即邀请发送邮件」开关，邮箱通知由 reg_verify 设置统一决定
- 不支持 Excel 一次性指定头像/电话；后续可让用户自行在「个人设置」补全
- 普通用户无此入口；非管理员调 import 会被 `User::auth('admin')` 拦截
