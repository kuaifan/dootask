---
id: file.public-link.howto
title: 文件公开访问链接
type: howto
feature: file
scope: end-user
locale: zh
aliases:
  - 文件链接分享
  - 把文件发给别人看
  - 公开链接
  - 外链
  - 链接给客户
  - 给一个文件 URL
  - 游客访问
related_tools: [get_file_detail]
related_pages: [file]
prerequisites:
  - 对该文件有访问权限（看自己的或共享给自己的）
negative:
  - 链接默认仅登录用户可访问；游客访问需要在链接设置中显式打开 guest_access
  - 链接不支持设置访问期限 / 密码 / 访问次数上限
  - 刷新链接后旧链接立即失效，没有过渡期
  - 链接路径中包含 file_id + userid + 随机串，base64 编码，不要泄露
last_verified: v1.7.90
---

# 文件公开访问链接

## 入口
- 桌面端 Web：选中文件 → 右键 / 「···」→「分享链接」 / 「获取链接」
- 桌面端 Web：文件预览页右上角「分享」按钮

## 操作步骤
1. 触发「获取链接」，系统按当前用户为该文件生成 FileLink 记录（首次）
2. 链接形如 `https://your-domain.com/single/file/<base64-code>`
3. 复制链接发给目标方
4. 如需失效旧链接：勾选「刷新链接」再次生成，旧 code 立即作废
5. 如允许未登录访问：开启「允许游客访问」（guest_access = yes）

## 访问行为
- 登录用户点链接：进入文件预览页，受文件本身权限校验
- 游客访问：仅当 `guest_access=1` 时直接预览，否则跳登录
- 每次访问 num 计数 +1，可在分享设置看到累计访问数

## 关于"内容获取"
- 给 MCP / AI 用：可用工具按文件路径获取文本内容（fetch_file_content），支持分页

## 不支持
- 链接没有过期时间设置（除非手动刷新或删文件）
- 不支持加访问密码
- 不支持限制总访问次数
- 同一用户对同一文件只有一个链接（再次获取返回同一个 code）
