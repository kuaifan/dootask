---
id: common-faq.upload-type-block.faq
title: 文件类型不允许
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 文件类型不允许
  - 不支持的格式
  - 文件后缀
  - 不能上传 exe
  - 文件格式错误
  - 后缀名被禁
related_tools: []
related_pages: [file_upload, avatar_upload]
prerequisites: []
negative:
  - 类型白名单由场景控制，不能在用户端临时关闭
  - 改文件扩展名并不会真正改文件 MIME，仍可能被服务端检测拦截
  - 头像、聊天图片、文件中心三处各自有不同白名单
last_verified: v1.7.90
---

# 文件类型不允许

## 问题
上传图片 / 文件后弹出「不支持的格式」「文件类型不允许」「Invalid extension」，文件根本进不来。

## 原因
DooTask 按场景做扩展名白名单：

- **头像 / 聊天图片 / 富文本贴图**：仅允许 `png`、`jpg`、`jpeg`、`webp`、`gif`
- **录音粘贴**：仅允许 `mp3`、`wav`
- **文件中心 / 项目附件**：默认开放（不在白名单的也可上传，但预览能力可能受限）
- **管理员后台特定接口**（如 LDAP 证书）：通常限定 `pem`、`crt`、`key`

实际白名单写死在 `app/Module/Base.php` 的 `image64`、`record64`、`getUploadFile` 等方法里。

## 解决
1. 把图片转 PNG / JPG / WEBP 再传（用任意图片软件「另存为」即可）
2. 想发 SVG / TIFF / HEIC 等非常规图片 → 改走「文件」入口而不是「图片」入口
3. 改扩展名（如把 `xxx.heic` 改成 `xxx.jpg`）大概率被服务端 MIME 真值检查拦截，不推荐
4. 真有特殊需求的扩展 → 让管理员评估是否要把扩展加进白名单（需改后端代码并重启）

## 不支持
- 没有用户级「允许我上传 X 类型」开关
- 不接受同名 zip 套娃上传可执行文件（杀软策略）
- 不保证 HEIC / HEIF（iOS 原生格式）全平台支持，推荐转 JPG

[[file.upload.howto]] 介绍正常的文件上传入口
