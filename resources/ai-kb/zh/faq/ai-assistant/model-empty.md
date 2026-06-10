---
id: ai-assistant.model-empty.faq
title: AI 助手模型下拉是空的
type: faq
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - AI 选不了模型
  - 模型下拉为空
  - 暂无可用模型
  - 没有可选 AI
  - AI 不能发送
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 普通用户无法自助开通 / 配置模型，必须由系统管理员处理
  - 下拉空多数是后端配置层面的事，不是网络问题
  - DooTask 不自带任何免费内置模型
last_verified: v1.7.90
---

# AI 助手模型下拉是空的

## 问题
打开 AI 助手浮窗后，**底部「选择模型」下拉框**为空，显示「暂无可用模型」或灰色禁用，按发送也无反应。

## 常见原因

1. **管理员没启用任何模型**
   - 系统设置 → 「AI 模型」中所有服务商开关都关着，或未填 API Key
2. **AI 插件未安装**
   - 应用市场没装 ai 插件；如果连入口都没有则属于 [[ai-assistant.disabled.faq]]
3. **API Key 失效**
   - 管理员填了 Key 但已过期 / 余额耗尽，后端返回模型列表为空
4. **接口请求失败**
   - `GET api/assistant/models` 出错（鉴权失败、网络抖动），前端弹「获取模型列表失败」并自动关浮窗
5. **管理员开了模型但没设默认**
   - 极少数情况：模型列表为空数组

## 解决

1. **联系系统管理员**：到「系统设置 → AI 模型」开启至少一个服务商并填可用 API Key
2. **检查 ai 插件**：管理员到「应用市场」确认 ai 插件已装且未禁用
3. **刷新页面**：关闭 AI 助手浮窗后重新打开，触发新一次 `api/assistant/models`
4. **看后台 ai-bot 日志**：管理员排查 dootask-ai 容器日志，确认上游 API 可达

## 与其他「AI 看不到」的区别
- 下拉**空** = 管理员配置层面问题
- AI 入口**完全看不到**（浮按钮 / 快捷键无效）= ai 插件未装，详见 [[ai-assistant.disabled.faq]]

## 相关
- [[ai-assistant.model.concept]]
- [[ai-assistant.disabled.faq]]
