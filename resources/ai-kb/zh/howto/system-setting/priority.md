---
id: system-setting.priority.howto
title: 任务优先级配置
type: howto
feature: system-setting
scope: admin
locale: zh
aliases:
  - 任务优先级
  - 优先级颜色
  - 自定义优先级
  - 改优先级
  - 设置优先级
  - 加一个优先级
  - 优先级天数
  - 紧急程度
related_tools: []
related_pages: []
prerequisites:
  - 需要系统管理员权限（普通用户只能 get 读取）
negative:
  - 不能给单个项目自定义独立优先级，全局共享一套
  - 不支持按优先级自动指派负责人 / 自动通知
  - 普通用户调用保存接口会被拒绝（仅 admin）
last_verified: v1.7.90
---

# 任务优先级配置

## 入口
桌面端：左上角头像 →「系统设置」→「任务优先级」。
对应后端：`POST api/system/priority`，`type=get` 读取，`type=save` 写入（限 admin）。

## 是什么
全系统共享的优先级清单。每条优先级有名称、颜色、自动截止天数、排序权重。任务创建时下拉里出现的就是这套清单。

## 字段结构
保存时上传 `list` 数组，每条形如：

```json
{
  "name": "紧急",
  "color": "#FF4D4F",
  "days": 1,
  "priority": 100
}
```

- **name** — 显示名（必填）
- **color** — Hex 颜色码（用于卡片左边色条 / 标签）
- **days** — 自动建议截止天数（创建任务时按"今天 + days"预填截止）
- **priority** — 排序权重，数字越大越靠前

服务端使用 `Setting::normalizeTaskPriorityList()` 校验并归一化（去重 / 截断 / 字段裁剪），空数组会拒绝。

## 操作步骤
1. 进入「任务优先级」页
2. 「新增」追加一行 → 填写名称 / 颜色 / 天数 / 权重
3. 拖拽或修改 `priority` 字段调整顺序
4. 点击「保存」即时生效，所有项目共享

## 默认数据
首次访问会返回系统默认的优先级（紧急 / 高 / 普通 / 低）。修改后保存即覆盖默认。

## 不支持
- 不能按项目维度独立配置
- 不支持「按优先级触发自动化规则」
- 删除某个优先级时已经使用它的旧任务仍保留旧名称（不会自动迁移）
