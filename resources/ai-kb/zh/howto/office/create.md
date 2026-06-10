---
id: office.create.howto
title: 创建在线文档（Word/Excel/PPT）
type: howto
feature: office
scope: end-user
locale: zh
aliases:
  - 怎么建在线 Word
  - 新建在线表格
  - 新建在线 PPT
  - 创建 docx
  - 怎么在 DooTask 写文档
  - 在线 office 怎么开
related_tools: []
related_pages: [file]
prerequisites:
  - 应用市场已安装 office（OnlyOffice）插件
  - 当前用户对目标文件夹/项目文件区有「编辑」权限
negative:
  - 创建后无法把在线 Word 转成 DooTask 自带的 `document` 文件类型
  - 创建后文件扩展名固定（docx/xlsx/pptx），不能在 DooTask 内改后缀
  - 不能在任务详情页直接「附件 → 新建 Word」，需先在文件页创建再链接
last_verified: v1.7.90
---

# 创建在线文档（Word/Excel/PPT）

## 入口
在线文档作为「文件」存在，入口都在文件页：

- 桌面端：左侧栏「文件」→ 选定目标文件夹 → 右上角「新建」按钮 →「Word 文档 / Excel 工作表 / PPT 演示文稿」
- 桌面端（右键）：文件列表空白处右键 →「新建」→ 同上三选一
- 移动端：底部 Tabbar「文件」→ 进入目标文件夹 → 右下角「+」→ 同上三选一
- 项目文件：进入项目 →「文件」标签页 → 新建菜单 → 同上三选一

## 操作步骤
1. 点击「新建 → Word 文档（或 Excel / PPT）」，文件列表多出一个待命名行
2. 输入文件名（≤ 100 字符），回车保存——文件扩展名按所选类型自动决定（docx/xlsx/pptx）
3. 双击或单击文件名进入编辑器（iframe 加载 OnlyOffice）
4. 直接在浏览器编辑：体验与桌面 Office 接近，支持公式、批注、修订、格式刷等
5. 自动保存：OnlyOffice 在内容停止变更后约 10 秒触发保存，关闭也会触发

## 上传已有文档
- 上传扩展名在 `doc/docx/xls/xlsx/ppt/pptx/odt/ods/odp/csv/rtf` 等列表内的文件，双击即可在线编辑（不需要先转格式）

## 字段默认值
- 文件名：默认「未命名」（扩展名自动加 .docx / .xlsx / .pptx）
- 模板：空白文档/工作表/演示文稿

## 不支持
- 不能在任务附件区直接「新建 Word」，需先在文件页建好再附加
- 不能跨类型转换（docx 改不成 xlsx）

## 相关
- 入口与权限：[[office.entry.menu-map]]
- 多人协作能力：[[office.collaboration.concept]]
