---
id: license.expire.faq
title: License 过期或失效怎么办
type: faq
feature: license
scope: super-admin
locale: zh
aliases:
  - License 过期
  - License 失效
  - 授权过期了
  - 提示终端 License 已过期
  - 用户数超出限制
  - SN 不匹配
  - MAC 不匹配
  - 换服务器了授权失效
  - 续费 License
related_tools: []
related_pages: []
prerequisites: []
negative:
  - License 失效不会立即锁死系统，但会持续在管理端报错
  - 修改 License 接口（save）仅超级管理员能调用，普通管理员只能看
  - 不支持自行重置 SN（重新部署会生成新 SN，需要重新签发 License）
  - 若用的是在线授权，error 数组还可能出现「在线授权即将到期/已过期/已失效」等提醒，处理见 [[license.online.howto]]
last_verified: v1.7.91
---

# License 过期或失效怎么办

## 问题
管理后台「License」页提示以下任一错误：

- 终端 License 已过期
- 终端用户数超过 License 限制
- 终端 SN 与 License 不匹配
- 终端 MAC 与 License 不匹配

或者：用户登录、AI 助手等功能突然出现授权相关的提示。

## 原因
后端 `api/system/license` 接口在每次拉取时实时校验：

1. 取本地 License 文件解出 `info`（含 SN/MAC/people/expired_at）
2. 与当前终端的 `doo_sn`、网卡 `macs`、`user_count`、当前时间逐项比对
3. 任一规则不满足 → 写入返回值的 `error` 数组

特殊情况：`info.people <= 3` 时跳过 SN / MAC 校验（小团队豁免）。

## 解决

### 情况 1：过期
1. 联系 DooTask 销售/渠道续费
2. 拿到新的 License 原文
3. 进入「系统设置」→「License」→ 粘贴新原文 → 保存
4. 重新加载页面，确认 `error` 数组已清空

### 情况 2：用户数超限
两种处理方式任选其一：

- 申请扩容版 License → 按情况 1 流程录入
- 临时禁用部分账号，让 `user_count` 降到 `people` 以下
  - 禁用方式：管理后台「成员管理」选中用户 → 禁用
  - 机器人账号不计入用户数，无需处理

### 情况 3：SN / MAC 不匹配
- **换了服务器**：新部署会生成新 doo_sn，必须重新申请 License
- **换了网卡或加了网卡**：联系签发方更新 MAC 白名单
- **不绑定模式**：申请 License 时声明「不绑 MAC」，签发方下发的 License 无 MAC 字段

录入方式同上，全部走「系统设置」→「License」→ 保存。

## 怎么自检
拉接口 `POST api/system/license?type=error` 仅返回 `error` 数组，便于脚本巡检。空数组表示完全通过。
