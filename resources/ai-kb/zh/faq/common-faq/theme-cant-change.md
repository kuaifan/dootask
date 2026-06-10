---
id: common-faq.theme-cant-change.faq
title: 切换主题 / 深色模式失败
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 切换主题失败
  - 深色模式不生效
  - 黑夜模式
  - 主题颜色
  - 主题没保存
  - 暗色模式
related_tools: []
related_pages: []
prerequisites: []
negative:
  - 不支持自定义主题色，只在预设主题里选
  - 部分微应用 / 插件没适配深色模式，会出现明暗夹杂
  - 跟随系统主题受浏览器对 prefers-color-scheme 的支持
last_verified: v1.7.90
---

# 切换主题 / 深色模式失败

## 问题
- 「个人设置 → 主题」切了但界面没变
- 切完一半元素是深色、一半浅色
- 跟随系统切不动
- 刷新后又变回去

## 操作
1. 个人头像 → 「个人设置」找「主题」
2. 选浅色 / 深色 / 跟随系统
3. 选完立即生效，无需刷新

## 不生效常见原因

**浏览器缓存**：F5 强刷、无痕模式、清站点缓存。

**部分插件 / 微应用没适配**
- OnlyOffice、流程图、思维导图用独立 iframe，主程序的深色模式传不进去
- 表现：聊天深色但 office 文档白底
- 是已知限制

**跟随系统不切**
- 浏览器 / 操作系统的 `prefers-color-scheme` 设置不正确
- Linux 桌面 / 部分国产浏览器可能不支持
- 改用「深色」/「浅色」固定选项

**多端不一致**
- 主题存到后端 `user.theme`，其他端登录会拉
- 桌面端 / 移动端 App 可能有独立开关，需各端单独设

**浏览器扩展冲突**
- Dark Reader 等扩展会覆盖站点 CSS
- 把 DooTask 域名加扩展白名单

## 不支持
- 不支持自定义主色 / 渐变
- 不支持按时间自动切换（如日落变暗）
- 不支持单页面单主题（必须全局）

## 相关
- 个人主题设置：[[user-settings.theme.howto]]
- 语言切换问题：[[common-faq.language-cant-change.faq]]
