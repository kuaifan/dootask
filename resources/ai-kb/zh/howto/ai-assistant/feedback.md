---
id: ai-assistant.feedback.howto
title: 给 AI 回答点赞或点踩
type: howto
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 点赞
  - 点踩
  - AI 回答不好怎么反馈
  - 反馈 AI 回答
  - 有帮助 没帮助
  - 复制 AI 回答
  - 取消反馈
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
negative:
  - 反馈只针对 AI 助手浮窗里的回复，聊天对话里 @AI 机器人的消息暂不支持
  - 点踩不会让 AI 立即重新回答，需要自己追问或重新提问
last_verified: v1.7.91
---

# 给 AI 回答点赞或点踩

## 这是什么
AI 助手浮窗中，每条 AI 回复完成后下方右侧会出现「复制 / 有帮助 / 没帮助」三个图标按钮（📋/👍/👎）。复制用于把回复内容复制到剪贴板，👍/👎 用于提交反馈，帮助官方改进 AI 回答质量和帮助文档内容。

## 怎么操作
1. 在 AI 助手浮窗中提问，等待回复完成（流式输出结束后按钮才出现）
2. 回复下方右侧点击 📋（复制）可复制该条回复正文到剪贴板（不含推理过程）
3. 点击 👍（有帮助）或 👎（没帮助）提交反馈
4. 按钮高亮表示已提交；再点另一个按钮可以改票，同一条回复只记最新一次
5. 再次点击当前已高亮的按钮可取消反馈

## 反馈会被怎么用
- 反馈与该回复引用的帮助文档关联，被频繁点踩的文档会被优先修订
- 重新打开历史会话时，之前的反馈状态会保留显示

## 不支持
- 不支持填写文字原因，只有 👍/👎 两档

## 相关
- AI 查帮助文档：[[ai-assistant.search-help-docs.howto]]
- 会话保存：[[ai-assistant.session-save.howto]]
