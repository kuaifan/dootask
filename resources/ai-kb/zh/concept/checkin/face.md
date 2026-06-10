---
id: checkin.face.concept
title: 人脸签到原理与依赖
type: concept
feature: checkin
scope: end-user
locale: zh
aliases:
  - 人脸识别原理
  - face 插件
  - 人脸签到依赖
  - 刷脸打卡怎么工作的
related_tools: []
related_pages: []
prerequisites:
  - 应用市场已安装 face 插件
last_verified: v1.7.90
---

# 人脸签到原理与依赖

## 定义
人脸签到是 DooTask 签到的一种方式，依赖独立的 **face 插件**提供人脸识别后端服务（容器名 `face`，内部端口 7788）。主程序只负责存储人脸图片地址 (`UserCheckinFace.faceimg`) 和触发签到记录写入，**所有特征提取、比对都由 face 插件完成**。

## 工作流程
1. 成员在签到设置上传人脸图片
2. 主程序把图片 base64 后 POST 到 `http://face:7788/user`，参数含 `enrollid`（成员 ID）、`name`（昵称）、`backupnum=50`
3. face 容器把特征存入设备库
4. 现场人脸识别一体机扫到该成员 → 调主程序 API 写一条 `UserCheckinRecord`

## 关键依赖
- **face 插件**：人脸识别引擎容器，未安装会抛 `Apps::isInstalledThrow('face')` 异常
- **人脸识别一体机硬件**：插件 README 注明「需配合指定硬件设备使用」
- **管理员开关**：「签到方式」勾选「人脸签到」+「允许修改」开启「允许成员上传人脸图片」

## 不支持
- 主程序自带的浏览器 / WebRTC 摄像头不能直接当人脸打卡器
- face 插件镜像较大（约 15MB+），首次安装下载较慢
- 删除成员或卸载插件后，face 容器内的特征不会自动清空，需要管理员手动删除
