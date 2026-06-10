---
id: ai-assistant.mcp-down.faq
title: AI 的 MCP 工具不可用
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - mcp 挂了
  - mcp_server 不可用
  - AI 全部工具不能用
  - AI 都不能操作
  - MCP 连接失败
  - AI 助手只能聊天不能动手
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 普通用户不能自助重启插件，需管理员
  - mcp_server 容器异常时所有 33 个 MCP 工具都不可用（包含页面操作）
  - 不影响纯文本对话和知识库检索（search_help_docs 是 AI 容器内置）
last_verified: v1.7.90
---

# AI 的 MCP 工具不可用

## 问题
AI 浮窗能聊天但任何"做事"指令都失败：
- 浮窗顶部不显示工具图标
- 让 AI 建任务 / 发消息 / 跳页面都没反应
- 工具气泡显示「连接失败」「mcp_server unavailable」

## 原因
- **mcp_server 插件未安装**：应用市场没装这个系统插件
- **mcp_server 容器挂了**：宿主资源不足 / 配置错误导致容器退出
- **WebSocket 不通**：反向代理 / 防火墙拦截了 `wss://.../apps/mcp_server/mcp/operation` 路径
- **AI 模型配置缺工具能力**：管理员配模型时关了 tool 调用集成

## 解决（普通用户）
1. 先确认是不是只你一个人的问题：和同事对比
2. 报给系统管理员，告知"mcp_server 不可用"
3. 等待恢复期间可改用：
   - 手动用前端操作功能
   - 用 AI 做纯文本辅助（写文案 / 总结 / 翻译）
   - 知识库检索（`search_help_docs` 不依赖 mcp_server，仍可用）

## 解决（管理员）
1. 应用市场 → 找到 `mcp_server` → 看运行状态
2. 没安装 → 安装
3. 已安装但状态异常 → 重启该插件
4. 长期失败 → 看插件日志（容器 docker logs）排查
5. 反向代理：确保 wss 路径未被拦

## 相关
- 工具失败：[[ai-assistant.tool-failed.faq]]
- 没调工具：[[ai-assistant.no-tool-call.faq]]
- 工具机制：[[ai-assistant.tools.concept]]
