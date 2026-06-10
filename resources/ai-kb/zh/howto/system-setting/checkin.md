---
id: system-setting.checkin.howto
title: 签到规则设置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 签到设置
  - 打卡规则
  - 配置签到
  - 签到时间
  - 上下班时间
  - 地理围栏
  - 人脸打卡
  - 配置打卡
  - 跨天打卡
  - 签到地图
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限
  - 启用人脸模式必须先安装 face 插件
  - 启用定位模式必须有对应地图服务商的 Key
negative:
  - 提前 + 延后时间之和必须 < 24h-工时，否则报「提前和延后时间设置存在重叠」
  - 关闭签到（open=close）时 key 会自动轮换（再开启需要重新分发设备）
  - 不支持给不同部门设置不同打卡规则，全公司共享一套
  - 不支持自动按公历节假日跳过打卡
last_verified: v1.7.90
---

# 签到规则设置

## 入口
桌面端：左上角头像 →「系统设置」→「签到」。
对应后端：`POST api/system/setting/checkin`，参数 `type=save`。

## 字段说明

- **open** — 签到总开关（`open` / `close`）
- **time** — 上下班时间数组，如 `["09:00","18:00"]`（支持跨天班次）
- **advance** — 允许提前打卡分钟数（默认 120）
- **delay** — 允许延后打卡分钟数（默认 120）
- **remindin** — 上班前 X 分钟提醒（默认 5）
- **remindexceed** — 下班后超 X 分钟未打卡提醒（默认 10）
- **edit** — 是否允许员工自行补卡（`open` / `close`）
- **modes** — 支持的打卡方式数组，取值 `auto` / `manual` / `locat` / `face`
- **manual_remark** — 手动签到的展示文案
- **face_upload / face_remark / face_retip** — 人脸打卡配置
- **locat_map_type** — 地图服务商（`baidu` / `amap` / `tencent`）
- **locat_bd_lbs_key / locat_amap_key / locat_tencent_key** — 对应地图 Key
- **locat_*_point** — `{lng, lat, radius}` 单点电子围栏
- **key** — 签到机器自动生成的访问密钥（关闭再开启会重置）

## 操作步骤
1. 选择支持的打卡方式（多选）
2. 填写上下班时间、提前/延后窗口
3. 启用「定位」时选地图服务商并填 Key + 中心点+半径
4. 启用「人脸」时确认 face 插件已装
5. 保存后系统会自动创建/复用 `check-in` 机器人账号
6. 服务端返回 `cmd` 字段（base64），是签到机/考勤机的接入命令

## 不支持
- 不支持单日多班次（如午休拆成 4 个时间点），只取 `time[0]` 和 `time[1]`
- 不支持给部门 / 项目单独配置规则
- 不允许提前 + 延后窗口之和超过「24 小时 - 工时长度」（会校验失败）
- 人脸模式必须依赖 face 插件，不能用浏览器 WebRTC 直采
