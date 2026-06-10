---
id: file.version.concept
title: 文件版本历史
type: concept
feature: file
scope: end-user
locale: zh
aliases:
  - 文件历史版本
  - 恢复旧版本
  - 文件回滚
  - 谁改过这个文件
  - 文档历史
related_tools: [get_file_detail]
related_pages: [file]
prerequisites: []
negative:
  - 版本历史仅对在线编辑类（document / word / excel / ppt / mind / drawio）有效
  - 普通上传文件覆盖后只会替换最新版，无完整历史
  - 没有版本数量上限，但定期清理由系统设置决定
  - 不支持「版本备注」字段（只看保存人 + 时间 + 大小）
last_verified: v1.7.90
---

# 文件版本历史

## 定义
对支持在线编辑的文件，每次保存都会在 `file_contents` 表插入新记录（FileContent），形成版本历史。文件本身（File 表）只指向最新版，但通过 `whereFid + orderByDesc('id')` 可查到所有历史版本。

## 关键属性
- **fid**：所属文件 ID
- **content**：JSON 元数据，包含真实存储路径 url、类型 type、扩展 ext
- **text**：提取出的纯文本（用于全文检索）
- **size**：本版本大小（字节）
- **userid**：本次保存人
- **created_at**：保存时间

## 哪些文件类型有版本
- document（在线文档）
- word / excel / ppt（通过 OnlyOffice 在线编辑）
- mind（思维导图）
- drawio（流程图）

## 哪些文件类型没有版本
- 上传二进制（pdf / archive / picture / media 等）覆盖上传只保留最新一版
- folder 本身无内容，无版本

## 查看与恢复
- 入口：文件预览页 / 编辑页 → 顶部菜单「历史记录」
- 列表显示每一版的保存人头像 + 时间 + 大小
- 选某一版可「预览」或「恢复」（恢复 = 把该历史版复制为新的最新版，保留中间版本）

## 与"文件协作锁"
DooTask 在线 Office 编辑通过 OnlyOffice 提供多人协作，多人编辑同一文档时由 OnlyOffice 自身的协同逻辑保证一致性，并在最后一个编辑者关闭后回写一版到 FileContent。
