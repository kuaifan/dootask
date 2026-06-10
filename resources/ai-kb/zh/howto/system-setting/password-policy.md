---
id: system-setting.password-policy.howto
title: 密码策略设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 密码策略怎么设
  - 密码复杂度怎么改
  - 强密码怎么开
  - 简单密码 复杂密码
  - password policy
  - 改密码规则
prerequisites:
  - 需要系统管理员权限
  - 部署环境变量 SYSTEM_SETTING 不为 disabled
related_tools: []
related_pages: []
negative:
  - 修改策略不会触发存量用户重置密码，存量密码继续可用
  - 不支持自定义正则、最小特殊字符数等细粒度规则，只有 simple / complex 两档
  - 密码长度上限固定 32 位，无管理后台开关
last_verified: v1.7.90
---

# 密码策略设置

## 入口
桌面端：左上角头像 →「系统设置」→「系统设置」标签 →「帐号相关」→「密码策略」。

字段名：`password_policy`，枚举 `simple` / `complex`，默认 `simple`。

## 两档区别

| 档位 | 长度 | 复杂度要求 |
|---|---|---|
| simple（简单） | ≥ 6 位 | 无 |
| complex（复杂） | ≥ 6 位 | 必须混合：不能全是数字、不能全是字母、不能仅数字+大写、不能仅数字+小写 |

通用约束：所有档位下密码长度上限 32 位。

## 对存量密码的影响
策略只在「设置密码」「修改密码」「注册」时校验。改成 `complex` 后：
- 已有用户登录不受影响（旧弱密码仍能用）
- 用户下次主动改密码必须满足新规则
- 管理员代设密码同样要满足新规则

如果要强制全员升级密码，需要管理员手动通知用户修改，没有内置「强制重置」按钮。

## 操作步骤
1. 进入「系统设置」→「系统设置」→「帐号相关」
2. 「密码策略」选 `simple` 或 `complex`
3. 页面底部「提交」保存

## 不支持
- 没有「禁用最近 N 次旧密码」「定期强制改密」等高级规则
- 没有按账号 / 部门差异化策略，全局生效
