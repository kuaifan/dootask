---
id: user-account.delete.howto
title: 注销账号
type: howto
feature: user-account
scope: end-user
locale: zh
aliases:
  - 注销账号
  - 删除账号
  - 销户
  - 不想用了
  - 彻底删除账号
  - 账号怎么注销
related_tools: []
related_pages: []
prerequisites:
  - 必须知道当前登录邮箱
  - 开启邮箱验证时需收到验证码；未开启时需输入登录密码
negative:
  - 注销后无法自助恢复，只能由系统管理员从 `user_deletes` 表里手动还原（详见 [[user-account.delete-restore.faq]]）
  - 系统账号（system）禁止注销
  - 注销不会立即物理删除所有数据；用户基础信息缓存在 user_deletes 表保留以维持历史记录可读
  - 注销后该邮箱可以被重新注册（但与原账号无关联）
last_verified: v1.7.90
---

# 注销账号

## 入口
- 桌面端：右上角头像 →「个人设置」→「账号安全」→ 底部「注销账号」
- 移动端：「我的」→「个人设置」→ 底部「注销账号」

## 操作步骤
1. 点「注销账号」打开确认页
2. 输入「当前邮箱」（必须与已登录账号完全一致）
3. 二次验证：
   - 系统开启邮箱验证（emailSetting.reg_verify=open）：点「发送验证码」→ 填邮件里的验证码
   - 否则：填登录密码
4. 可选：填注销原因（保留到 `user_deletes.reason`，供管理员参考）
5. 点「确认注销」（type=confirm）

## 注销后的数据处理
- 用户标记为已删除，`user_deletes` 表保留快照：邮箱、昵称、头像、部门
- 该用户在任务/项目/聊天中的历史记录仍存在，但显示为「已注销用户」
- 该用户在「团队管理」普通列表中不再出现
- token 立即失效，所有设备登出

## 注销前建议
- 先把负责的项目、任务转交他人；否则注销后这些工作项会保留旧的负责人指向
- 导出/备份你需要保留的文件、报告
- 退出所有项目与群组

## 不支持
- 不支持「暂时关闭账号」（停用账号是管理员才能做的操作）
- 不支持注销后保留消息历史的副本下载

## 相关
- 注销能不能恢复：[[user-account.delete-restore.faq]]
