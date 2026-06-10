---
id: file.create.howto
title: 新建文件夹 / 在线文档 / 表格 / 思维导图
type: howto
feature: file
scope: end-user
locale: zh
aliases:
  - 新建文件夹
  - 建文档
  - 在线表格
  - 怎么建思维导图
  - 新建空白文件
  - 加一个文档
related_tools: [list_files]
related_pages: [file]
prerequisites:
  - 当前文件夹有写权限
negative:
  - "文件名长度 2-100 字符；不能包含特殊字符（反斜杠、斜杠、冒号、星号、问号、双引号、尖括号、竖线）"
  - 单个文件夹下最多 300 个直接子项
  - mind / drawio 需要管理员在应用市场安装 minder / drawio 插件，否则无法保存
  - word / excel / ppt 新建空白文件需要 office 插件（OnlyOffice）
last_verified: v1.7.90
---

# 新建文件夹 / 在线文档 / 表格 / 思维导图

## 入口
- 桌面端 Web：进入「文件」页 → 任意文件夹内 → 顶部「+」按钮
- 移动端：「文件」页右上角「+」

## 可新建的类型
点击「+」后弹出菜单，可选：

- **folder**：文件夹
- **document**：在线文档（Markdown 富文本，扩展名 md）
- **word / excel / ppt**：在线 Office 文档（需要 office 插件，扩展名 docx / xlsx / pptx）
- **mind**：思维导图（需要 minder 插件，扩展名 mind）
- **drawio**：流程图 / 架构图（需要 drawio 插件，扩展名 drawio）

## 操作步骤
1. 选择类型
2. 输入文件名（必填，2-100 字符，不能含 `\ / : * ? " < > |`）
3. 回车或点确认；系统检测同名时会自动加上 `(2)`、`(3)` 等后缀
4. 文档类型创建后自动打开编辑器，可立即编辑

## 字段默认值
- 拥有者：所在文件夹的拥有者（共享文件夹内则归该文件夹的所有者）
- 创建人：当前用户
- 父目录：当前所在文件夹

## 不支持
- 不支持新建 PDF / 图片 / 压缩包等二进制类型（只能上传）
- 不支持批量新建（一次只能建一个）
- 不支持复制文件夹（只能复制单个文件，详见上传后用复制功能）
