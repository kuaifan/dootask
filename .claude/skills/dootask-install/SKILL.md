---
name: dootask-install
description: 首次部署 DooTask：前置检查后执行 `sudo ./cmd install`（建库 + migrate --seed 的重操作），刚性流程、单次确认、失败即停。
---

# DooTask 安装流程

**刚性技能**——前置检查 → 向用户确认一次 → 执行 → 报告结果。任何一步失败立即停止。

## 核心原则

**违反字面规则 = 违反流程精神。** 不要擅自增加、省略、合并步骤，不要为"省事"绕过 sudo 或确认。

`./cmd install` 已把整套安装封装为单条命令（赋权→起容器→`composer install`→`key:generate`→`migrate --seed`→`up -d`）。本技能的职责是**安装前把关、选对参数、执行前确认、已知失败处理**，而不是把脚本逻辑拆开重做。

## 前置检查（全部通过才能继续）

执行前依次确认：

1. **工作目录**：必须在项目根（存在 `cmd`、`docker-compose.yml`、`.env.docker`）
2. **Docker**：`docker` 与 `docker-compose`/`docker compose`(v2+) 可用且 daemon 在跑（脚本 `check_docker` 也会查，但提前确认能更早报错）
3. **Node.js ≥ 20**（脚本 `check_node` 会查）
4. **APP_ID 不冲突**：若 `.env` 已有 `APP_ID` 且被其他实例占用，脚本 `check_instance` 会报错——此时**停止**，提示用户先清空 `.env` 里的 `APP_ID` 和 `APP_IPPR` 再装
5. **sudo**：`./cmd install` 需 root（`check_sudo`），用 `sudo ./cmd install` 执行

⚠️ **这是重操作**：会创建数据库并执行 `migrate --seed`（灌入种子数据）。在已有数据的环境上重装前务必和用户确认，避免覆盖。

检查通过后汇报结果，**向用户确认一次**再执行。

## 参数选择

| 参数 | 作用 | 何时用 |
|------|------|--------|
| `--port <端口>` | 指定 HTTP 端口（脚本会做端口占用检测） | 用户要自定义端口，或默认端口被占 |
| `--relock` | 删除 `node_modules`/`package-lock.json`/`vendor`/`composer.lock` 后重装 | **谨慎**：仅在依赖锁损坏、用户明确要求重建锁时用，会拖慢安装 |

不确定时不要自作主张加参数，按需询问用户。

## 执行

确认后执行（按用户选择带上参数）：

```shell
sudo ./cmd install
# 或： sudo ./cmd install --port 8080
```

成功后脚本会输出访问地址并调用 `repassword.sh`。执行完向用户报告：访问地址（`http://127.0.0.1:<APP_PORT>`）、以及数据库密码提示。

## 失败处理

- 任何步骤失败立即停止，原样报告错误信息
- **不要**自动重试，**不要**自动跳过
- 常见失败与对应处理：
  - `APP_ID（xxx）已被其他实例使用` → 停止，让用户清空 `.env` 的 `APP_ID`/`APP_IPPR` 再装
  - `端口 xxx 已被占用` → 停止，让用户换 `--port`
  - `目录【xxx】权限不足` / 目录权限检测失败 → 这是目录属主/权限问题，引导用户用 **dootask-fix-permission** 技能修复后重装
  - `安装依赖失败`（composer）→ 报告，交用户决定（常因网络/镜像源）

## 禁止项

| 错误做法 | 正确做法 |
|---------|---------|
| 不加 sudo 直接 `./cmd install` | 用 `sudo ./cmd install`（脚本强制 root） |
| 失败后"我再试一次"或自动跳过 | 立即停止，交还用户 |
| 在已有数据环境上不问就重装 | 先确认会 `migrate --seed`，可能影响现有数据 |
| 遇权限报错自己乱 `chmod`/`chown` | 走 dootask-fix-permission 技能统一处理 |
| 不问就加 `--relock` | 默认不加；仅用户明确要求或锁损坏时用 |

## Red Flags —— 出现这些念头立即停下

- "端口/权限报错了我顺手帮 TA 改一下别的" → 停下，只处理本次报的问题，按指引走对应技能
- "种子数据应该没事，直接重装" → 不，先确认是否会覆盖现有数据
- "sudo 麻烦，先试试不加" → 不，install 必须 root
