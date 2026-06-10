---
id: common-faq.upload-image-error.faq
title: 图片预览失败
type: faq
feature: common-faq
scope: end-user
locale: zh
aliases:
  - 图片传上去打不开
  - 图片裂了
  - 缩略图加载不出
  - 显示损坏图标
  - 图片 404
  - 图片预览失败
related_tools: []
related_pages: [file_detail, dialog_image_viewer]
prerequisites: []
negative:
  - 图片上传成功不代表预览一定成功——缩略图是异步生成的，可能慢几秒
  - HEIC / HEIF / TIFF / RAW / PSD 等格式默认不生成缩略图
  - 浏览器层缓存被污染后即使服务端图片正常也可能裂；得清缓存
last_verified: v1.7.90
---

# 图片预览失败

## 问题
图片成功上传，但消息气泡 / 文件详情显示破图、灰底叹号、加载圈一直转，或点开提示「图片加载失败」。

## 原因
- **缩略图未生成**：服务端 GD/Imagick 在后台异步生成缩略图，前 1-3 秒内显示原图占位
- **格式不被识别**：HEIC、TIFF、PSD、RAW 等格式 GD 默认无法解码
- **GIF 自动转静图**：GIF 上传时若 `autoThumb` 关闭，则保留原文件不做缩略图
- **图片实际损坏**：截图工具偶尔输出未关闭的字节流；上传成功但服务端解码失败
- **Token 过期**：图片 URL 带签名 / cookie，长时间停留页面后凭证失效返回 401/403
- **CDN / 反代缓存了坏图**：之前传过损坏图，CDN 把坏字节缓存下来

## 解决
1. 先等 3 秒刷新页面看是否是缩略图未及时生成
2. 改 PNG/JPG/WEBP 重新上传（不要传 HEIC）
3. 刷新页面（Ctrl/Cmd + Shift + R 强刷绕过缓存）后重试
4. 重新登录或重新打开标签让 token 续期
5. 链接拷出来直接在浏览器打开，能下载但不能渲染 → 图片本身坏；能下能渲染 → 前端缓存问题
6. 服务端管理员：检查 `public/uploads/` 目录权限、磁盘剩余空间、`docker logs dootask-php` 看是否报 GD 错误

## 不支持
- 用户端没有「重建缩略图」按钮，重传一次即可触发
- 富文本 / 聊天里的过期签名链接不会自动续签，需重新打开整个消息

通用上传卡顿见 [[common-faq.upload-stuck.faq]]
