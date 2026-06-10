---
id: system-setting.meeting.howto
title: 会议设置（Agora）
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 会议设置
  - 配置会议
  - 声网
  - Agora
  - appid
  - 会议参数
  - 怎么开启视频会议
  - 视频会议配置
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 已申请声网 Agora 项目并拿到 AppID / 证书
negative:
  - 必须填 appid 和 app_certificate 才能保存为「open」，否则报「请填写基本配置」
  - 不支持 Jitsi、Zoom、WebRTC 自建等其他后端，目前仅适配声网
  - SYSTEM_SETTING=disabled 时配置不可修改且会被部分打码显示
last_verified: v1.7.90
---

# 会议设置（Agora）

## 入口
桌面端：左上角头像 →「系统设置」→「会议」。
对应后端：`POST api/system/setting/meeting`，参数 `type=save`。

## 字段说明

- **open** — 会议总开关（`open` / `close`）。关闭时全员无法发起会议
- **appid** — 声网项目 App ID（必填，开启时校验）
- **app_certificate** — 声网项目 App 证书（必填）
- **api_key** — 声网 RESTful API Key（用于云录制等高级功能，可选）
- **api_secret** — 声网 RESTful API Secret（与 api_key 配对，可选）

服务端只接收上述 5 个字段，其他键会被忽略。

## 操作步骤
1. 登录 [Agora 控制台](https://console.agora.io/) 创建项目
2. 项目证书选「App ID + App Certificate」（鉴权）
3. 把 App ID、App Certificate 复制到 DooTask 表单
4. 「open」选项设为 `open`
5. 点击「保存」，提示「保存成功」即生效
6. 测试：随便在群里点「视频会议」按钮，能拉到画面即配置正确

## 字段保护
- 当部署环境变量 `SYSTEM_SETTING=disabled`，本页所有字段会以「前 4 位 + 星号 + 后 4 位」打码返回，且禁止修改
- 这是给演示环境用的保护机制，避免敏感凭证泄露

## 不支持
- 不支持自建 SFU / MCU，只对接声网 SaaS
- 会议时长 / 人数限制由声网套餐决定，DooTask 这边不限制
- 关闭会议（`open=close`）后无法使用「视频会议」入口和「会议」应用（会被同时禁用）
