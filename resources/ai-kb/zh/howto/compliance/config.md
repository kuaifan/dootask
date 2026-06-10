---
id: compliance.howto
title: 合规配置项检查清单
type: howto
feature: compliance
scope: admin
locale: zh
aliases:
  - 合规配置
  - 怎么做合规
  - GDPR 配置
  - 合规检查
  - 数据合规清单
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员或超级管理员权限
  - 部分项需要服务器管理员配合（HTTPS / 备份脚本）
negative:
  - DooTask 没有「一键合规检查」按钮
  - 多数合规项是人工 + 脚本组合，没有自动巡检
  - 合规问题的法律责任在私有部署方，DooTask 仅提供能力
last_verified: v1.7.90
---

# 合规配置项检查清单

## 操作步骤
DooTask 没有集中页面，按以下分散位置逐项检查：

1. 用户协议 / 隐私政策展示
   - 「系统设置 → 通用设置」中可配置注册时是否要求勾选《用户协议》《隐私政策》
   - 链接为外链，需自行托管 HTML 内容

2. 注册策略
   - 「系统设置 → 注册策略」选择「关闭注册 / 需邀请码 / 自由注册」
   - 涉及未成年人 / 实名制场景应至少开启邀请码

3. 密码强度与登录验证
   - 「系统设置 → 安全设置」启用密码强度校验、强制 2FA（如安装相关插件）
   - LDAP 用户由 AD 侧管控

4. 内容审核
   - 培训管理员定期查 [[abuse-report.entry.menu-map]]
   - 必要时联动「团队管理 → 禁用账号」

5. 数据导出审计
   - 管理员侧导出会经 `system-msg` 私聊推送，记录可在机器人对话中追溯
   - 详见 [[data-export.concept]]

6. 数据保留与销毁
   - 写脚本定期清理 `web_socket_msgs`、`temp/` 临时文件
   - 离职员工走「团队管理 → 删除」自动触发 `user_offboard` hook

7. 数据备份与本地化
   - 用项目提供的 `./cmd backup` 类脚本（如有）定期备份数据库 + uploads
   - 备份文件应加密存放、限制访问

8. 传输加密
   - 部署侧用 Nginx 配 HTTPS 证书（Let's Encrypt 等）
   - 内网部署也应启用，至少在出口

## 不支持
- 不支持自动巡检上述项
- 不支持生成合规报告 PDF
- 不内置 DSR（数据主体请求）工单

## 相关
- 概览：[[compliance.concept]]
- 入口：[[compliance.entry.menu-map]]
