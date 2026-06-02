---
name: dootask-fix-permission
description: 修复 DooTask 可写目录（bootstrap/cache、docker、public、storage）的属主/权限：chown 回当前用户 + 目录 chmod 775，对齐 install 的赋权逻辑，赋权不删数据。
---

# DooTask 目录权限修复

容器内进程常以 **root** 写入挂载目录（`storage`、`public/uploads`、`bootstrap/cache` 等），导致宿主机当前用户对这些文件**没有写权限**，进而触发：

- `./cmd install` 报「目录【xxx】权限不足」/ 目录权限检测失败
- `./cmd build`（vite）报 `EACCES: permission denied, copyfile`（复制 `public/uploads/...` 时）
- Laravel 运行时写 `storage`/`bootstrap/cache` 失败

本技能**对齐 `./cmd install` 的目录赋权逻辑**：对四个可写目录做 `chmod 775`（目录）+ `chown` 回当前用户。

## 适用目录

与 install 一致的四个：

```
bootstrap/cache
docker
public          # 含 public/uploads（真实上传数据）
storage
```

## 核心原则：赋权，不删数据

`public/uploads` 含真实上传文件（头像、附件等）。**永远优先 `chown` 改属主，不要删数据。** 即便用户说"清理一下"，也只允许清临时目录 `public/uploads/tmp`，**切勿**删 uploads 下其他内容。

## 前置检查

1. **工作目录**：在项目根（存在 `cmd` 且这四个目录在）
2. **sudo**：改属主需 root（当前文件多为 root 属主）。本机一般可免密 sudo；不行则经 docker 以 root 改权限
3. 确认要修的范围：默认四个目录全修；若用户只想解 build 报错，也可只针对 `public`（含 `public/uploads`）

检查通过后汇报将执行的命令，**向用户确认一次**再执行。

## 执行

确认后执行（属主修回当前用户，目录权限 775）：

```shell
# 1) 属主修回当前用户（递归）
sudo chown -R "$(id -u):$(id -g)" bootstrap/cache docker public storage

# 2) 目录权限 775（仅目录，对齐 install 的 `find -type d -exec chmod 775`）
find bootstrap/cache docker public storage -type d -exec chmod 775 {} \;
```

> 只想解 build 的 uploads 报错时，可只对 `public`：
> ```shell
> sudo chown -R "$(id -u):$(id -g)" public/uploads
> ```

执行后报告：改了哪些目录、属主/权限现状（可 `ls -ld` 抽查），并提示用户可重试之前失败的 install/build/update。

## 失败处理

- `chown` 报权限不足 → 当前用户无 sudo 权限，提示用户用有 root 权限的账户，或经 docker 以 root 执行；不要静默跳过
- 任何步骤失败立即停止报告，不自动重试

## 禁止项

| 错误做法 | 正确做法 |
|---------|---------|
| build 报 uploads EACCES 就 `rm` 删文件 | `chown` 修属主，保留数据 |
| 删整个 `public/uploads` 清场 | 最多清 `public/uploads/tmp`，别碰真实上传数据 |
| 对文件无差别 `chmod 777` | 目录 `chmod 775` + `chown` 回当前用户即可 |
| 不加 sudo 直接 chown root 文件 | 改属主需 root |

## Red Flags —— 出现这些念头立即停下

- "uploads 复制失败，删掉再 build" → 不，`chown` 赋权，不丢数据
- "777 一把梭最省事" → 不，按 install 的 775（目录）+ chown
- "权限不够就跳过这个目录" → 不，报告交用户处理 sudo
