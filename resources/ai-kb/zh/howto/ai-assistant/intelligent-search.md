---
id: ai-assistant.intelligent-search.howto
title: 让 AI 做智能搜索
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 智能搜索
  - AI 搜一下
  - 全站搜索
  - 找一下 X
  - 不知道在哪
  - 跨模块搜索
related_tools: [intelligent_search]
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 应用市场已安装 mcp_server 插件
negative:
  - 只搜当前用户有权访问的内容
  - 默认结果上限 20 条，按相关度排序
  - 不返回系统设置 / 插件配置等管理项
last_verified: v1.7.90
---

# 让 AI 做智能搜索

## 这是什么
当你不确定要找的东西是任务、项目、文件还是人时，让 AI 调 `intelligent_search` 工具做跨类型语义搜索，结果合并任务 / 项目 / 文件 / 联系人 / 消息五大类，按相关度统一排序。

## 怎么问
- "搜一下『官网改版』有关的所有东西"
- "找下『年终报告』，不管是什么类型"
- "凡是提到上线日期的内容"
- "我想找昨天讨论的接口设计，记不清是文件还是消息"

## 与按类型搜的区别
- **按类型搜**（如 `search_files` / `list_tasks`）：你已经知道是文件 / 任务，能给出精确筛选
- **智能搜索**：你只有一个关键词，让 AI 跨类型匹配，相关度排序

## 结果通常长什么样
分组返回：

- 任务（前 N 条）
- 项目（前 N 条）
- 文件（前 N 条）
- 联系人（前 N 条）
- 消息（前 N 条）

每项含标题、摘要、跳转链接，便于继续操作（打开 / 重新讨论 / 转发）。

## 不支持
- 不能跨工作空间 / 跨租户搜索
- 不返回敏感字段（密码、邮箱手机号正文不会出现在摘要）
- 不能精确按数值范围筛（如"金额 > 1000"），需用具体业务工具

## 相关
- 知识库检索：[[ai-assistant.search-help-docs.howto]]
- 找文件：[[ai-assistant.search-files.howto]]
- 找任务：[[ai-assistant.list-tasks.howto]]
- 找人：[[ai-assistant.search-users.howto]]
