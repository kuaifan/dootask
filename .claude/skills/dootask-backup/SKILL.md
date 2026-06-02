---
description: 备份 DooTask 数据：数据库（必须）+ public/uploads（排除 tmp，可选）+ docker/appstore/config（可选）。汇总到临时目录并附 README 说明，打包到 backup/ 按日期命名。只读取源数据、绝不删改，失败即停。
---

# DooTask 数据备份

**刚性技能**——前置检查 → 选可选项 → 确认 → 执行 → 报告。只读取源数据生成归档，**绝不删除或修改任何源数据/既有备份**。任何一步失败立即停止。

## 备份范围

| 项 | 来源 | 是否必须 | 说明 |
|----|------|---------|------|
| 数据库 | `./cmd mysql backup` 产出的 `.sql.gz` | **必须** | 脚本内部用 mysqldump 导出当前库 |
| 上传文件 | `public/uploads`（**排除 `public/uploads/tmp`**） | 可选 | 头像/聊天/任务/文件等真实上传数据；`tmp` 是临时目录，可重建，不备份 |
| 应用配置 | `docker/appstore/config` | 可选 | 应用市场各应用的配置；含 **root 属主子目录**，收集时可能需 sudo |

> `docker/appstore/apps` **不在备份范围**——可从应用市场重新安装，无需备份。

## 前置检查（全部通过才能继续）

1. **工作目录**：在项目根（存在 `cmd`、`docker-compose.yml`）
2. **数据库容器**：`mariadb` 容器在跑（DB 备份依赖它；不在则提示用户先 `./cmd up` 起服务）
3. **磁盘空间**：确认 `backup/` 所在盘空间足够（数据库 dump 可能较大）
4. **选可选项**：询问用户本次是否包含 `public/uploads` 和 `docker/appstore/config`（**默认两个都含**）

检查通过、可选项确定后，汇报本次将备份哪些项，**向用户确认一次**再执行。

## 执行

用一个统一时间戳贯穿全程：`TS=$(date +%Y%m%d_%H%M%S)`，临时目录 `WORK="tmp/dootask-backup-${TS}"`。

### 1) 建临时工作目录
```shell
mkdir -p "$WORK"
```
（`tmp/` 已被 gitignore，安全）

### 2) 数据库（必须）
```shell
./cmd mysql backup
```
脚本会把 dump 写到 `docker/mysql/backup/<库名>_<时间戳>.sql.gz` 并打印「备份文件：...」。**取该次产出的最新 dump** 复制进工作目录（不用关心它原始落在哪）：
```shell
DB_FILE=$(ls -t docker/mysql/backup/*.sql.gz | head -1)
cp "$DB_FILE" "$WORK/"
```

### 3) public/uploads（可选，排除 tmp）
```shell
rsync -a --exclude='tmp' public/uploads/ "$WORK/uploads/"
```
> 无 rsync 时用 tar 管道：`mkdir -p "$WORK/uploads" && tar cf - --exclude='./tmp' -C public/uploads . | tar xf - -C "$WORK/uploads"`

### 4) docker/appstore/config（可选）
```shell
cp -a docker/appstore/config "$WORK/appstore-config"
```
> 含 root 属主子目录，若报 `permission denied`：改用 `sudo cp -a ...`，随后把整个工作目录属主归还当前用户，保证后续打包/清理不受阻：
> ```shell
> sudo chown -R "$(id -u):$(id -g)" "$WORK"
> ```

### 5) 写 README.md（备份说明）
在 `$WORK/README.md` 写明本次备份信息，便于日后识别与还原。模板：
```markdown
# DooTask 备份 — <TS>

- 备份时间：<人类可读时间>
- DooTask 版本：<取自 package.json 的 version>
- 包含内容：
  - 数据库：<DB dump 文件名>（来源 mysqldump 当前库）
  - 上传文件：uploads/（来源 public/uploads，已排除 tmp）  ← 未选则写「未包含」
  - 应用配置：appstore-config/（来源 docker/appstore/config）  ← 未选则写「未包含」
- 各项大小：<du -sh 列出工作目录内各项>

## 还原提示
- 数据库：`gunzip < <db>.sql.gz | mysql -u<user> -p<pass> <库名>`，或用 `./cmd mysql recovery` 选对应文件还原。
- 上传文件：将 uploads/ 内容覆盖回项目 public/uploads/。
- 应用配置：将 appstore-config/ 覆盖回 docker/appstore/config/。
```

### 6) 打包到 backup/，清理临时目录
```shell
mkdir -p backup
tar czf "backup/dootask_backup_${TS}.tar.gz" -C tmp "dootask-backup-${TS}"
rm -rf "$WORK"
```

## 报告

向用户报告：
- 最终归档路径：`backup/dootask_backup_<TS>.tar.gz`
- 归档大小（`ls -lh`）
- 实际包含了哪些项（数据库 + 视选择含/不含 uploads、appstore-config）

## 失败处理

- 任何步骤失败立即停止，原样报告错误
- **不要**自动重试、不要静默跳过某一项（可选项是否包含由前置确认决定，不在执行中临时变更）
- DB 备份失败（如 mariadb 未运行）→ 停止，提示用户起服务后重试
- 打包前若工作目录有 root 属主残留导致 tar/rm 失败 → `sudo chown` 归还属主后继续，不要删源数据

## 禁止项

| 错误做法 | 正确做法 |
|---------|---------|
| 为"省空间"删除源数据或既有备份 | 只读取源数据生成归档，源数据一律不动 |
| 备份 `public/uploads/tmp` | 排除 tmp（临时、可重建） |
| 把 `docker/appstore/apps` 也打进去 | 不在范围，可从应用市场重装 |
| 遇 config 的 root 子目录就跳过该项 | `sudo` 收集后 chown 归还，完整备份 |
| 不写 README 直接打包 | 每个归档自带 README，便于日后识别还原 |
| 把归档写进 git | 归档放 `backup/`（已 gitignore），不提交 |

## Red Flags —— 出现这些念头立即停下

- "源数据太大，删点旧的再备份" → 不，备份只读不删
- "config 有 root 目录，跳过算了" → 不，sudo 收集后归还属主
- "apps 也一起备了更全" → 不，apps 不在范围
- "tmp 里临时文件顺手也备了" → 不，明确排除 `public/uploads/tmp`
