---
id: ai-assistant.mobile-entry.concept
title: 移动端 AI 助手入口
type: concept
feature: ai-assistant
scope: end-user
locale: zh
aliases:
  - 手机上怎么用 AI
  - 移动端 AI 入口
  - 手机 AI 助手
  - 移动端 AI 浮球
  - 手机 AI 弹窗
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 ai 插件
  - 管理员已配置至少一个 AI 模型
negative:
  - 移动端没有键盘快捷键
  - 浮按钮位置不在桌面 / 移动间同步
  - AI 弹窗在移动端走全屏，不能调整大小
last_verified: v1.7.90
---

# 移动端 AI 助手入口

## 定义
移动端指通过 iOS / Android App 或手机浏览器访问 DooTask（`isMobile = true`，含竖横屏窄屏）。AI 助手在移动端有两类入口：**浮按钮小球** 和 **业务页面内嵌按钮**。

## 浮按钮（主入口）
- 屏幕右侧默认距底约 1/4 屏高悬浮
- 加载后自动收起为屏幕边缘 48px 高竖条
- 单指按住可拖动到任何位置；松开后靠近左右边缘会再次自动收起
- 单击展开 AI 助手；展开后**全屏**弹窗，覆盖整个视口

## 全屏对话
- 移动端 AI 弹窗强制全屏（`is-mobile-fullscreen`）
- 顶部有标题栏 + 关闭按钮 + 历史会话下拉
- 不能调整大小、不能拖动窗口

## 业务嵌入入口
- 任务详情底部输入区 AI 图标
- 工作汇报编辑器工具栏「AI 生成」
- 消息会话群里 @AI
- 创建项目名右侧 AI 按钮

## 与桌面端的差异
| 维度 | 桌面端 | 移动端 |
|---|---|---|
| 弹窗形态 | 浮窗或 modal | 全屏 modal |
| 浮按钮自动收起延迟 | 1 秒 | 5 秒（加载即收起） |
| 快捷键 | Cmd/Ctrl + I | 无 |
| 默认浮按钮位置 | 右下距底 100px | 右侧距底 1/4 屏高 |

## 不支持
- 不支持移动端快捷收起 / 展开浮按钮（必须靠拖动）
- 不支持移动端关闭浮按钮本身
- 不支持移动端浮窗与桌面浮窗位置同步

## 相关
- [[ai-assistant.float-button.concept]]
- [[ai-assistant.modal.concept]]
