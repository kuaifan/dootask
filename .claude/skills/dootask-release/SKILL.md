---
name: dootask-release
description: 从 `pro` 分支发布 DooTask 前端新版本：刚性顺序流程 translate → version → build → commit → push，前置检查 + 每步确认、失败即停。
---

# DooTask 发布流程

**刚性技能**——严格按顺序执行，每步向用户确认，任何一步失败立即停止。

## 核心原则

**违反字面规则 = 违反流程精神。** 不要擅自增加、省略、合并或重排步骤。

## 前置检查（全部通过才能继续）

执行任何发布步骤前，依次检查：

1. **分支**：必须是 `pro`，否则停止，提示用户切换
2. **工作区**：`git status` 必须干净（无未提交变更、无未跟踪文件），否则**停止**并交由用户处理
3. **Node.js**：必须 ≥ 20，否则停止

检查通过后汇报结果，用户确认后再开始执行。

## 发布步骤

**每步执行前**向用户确认；**每步执行后**报告结果。

### Step 1: 翻译
```shell
npm run translate
```
更新多语言翻译文件。

### Step 2: 版本号
```shell
npm run version
```
更新版本号。

### Step 3: 构建前端
```shell
npm run build
```
构建前端生产版本。

> **已知失败**：若 build 报 `public/uploads/...` 的 `EACCES: permission denied, copyfile`，是 vite 复制 `public/` 目录时碰到运行时残留的上传文件——这些文件常为 **root 属主**（容器内 root 进程写入），复制需覆盖写入，构建用户没有写权限。报错路径可能落在 `public/uploads` 下任意子目录（`tmp`、`avatar` 等），不限于 `tmp`。这不是代码问题。**补救（赋权，不删数据）**：把整个 uploads 的属主改回当前用户后重试 build：
> ```shell
> sudo chown -R "$(id -u):$(id -g)" public/uploads
> ```
> 需 root（本机可免密 sudo；或经 docker 以 root 改权限）。**优先赋权，不要删**——`public/uploads` 含真实上传数据。即便用户要求清理，也只清临时目录 `public/uploads/tmp`，切勿删 uploads 下其他内容。

## 最终：提交并推送

所有步骤完成后：

1. 通过 `git diff` + `git status` 汇总所有变更，向用户报告摘要
2. **询问用户是否提交并推送**
3. 用户明确确认后才执行 `git add`、`git commit`、`git push`
4. 未确认一律不执行

提交规范：
- 提交信息使用 `release: v<新版本号>`（与历史提交风格一致，参见 `git log --oneline | grep '^release:'`）
- **只 add 本次发布相关改动**，按文件名显式添加（例如 `git add package.json public/js/...`），**不要用 `git add -A` 或 `git add .`**，以免卷入未跟踪的本地实验文件

## push 之后：确认发布工作流（CI 才是真正出包）

push 到 `pro` 只是触发器，真正的构建/出包由 GitHub Actions 完成——**push 成功 ≠ 发布完成**：

- **Publish**（`.github/workflows/publish.yml`，push→pro 触发）跑完才算出包；成功后会自动触发 **Sync to Gitee**（镜像同步）。
- push 完成后**主动确认** Publish 工作流 `conclusion=success`。优先用 `gh`（未装可临时装；公开仓库也可用 GitHub REST API 免鉴权读取 runs）：
  ```shell
  gh run list --workflow=publish.yml -R kuaifan/dootask -L 1
  gh run view <run-id> -R kuaifan/dootask --json status,conclusion,url
  ```
- 工作流仍在跑时，挂后台轮询、结束即通知用户，**不要在前台死等**。

### 可选：iOS 发布

`ios-publish.yml` 是**独立的手动工作流**（`workflow_dispatch`），不随 push 触发。**仅当用户明确要求**发 iOS 时执行：
```shell
gh workflow run ios-publish.yml --ref pro -R kuaifan/dootask
```
需 `gh` 已登录且 token 含 `workflow` 权限。触发后同样可挂后台轮询其结果。

## 失败处理

- 任何步骤失败立即停止，报告错误信息
- **不要**自动重试
- **不要**自动跳过失败步骤
- 由用户决定如何处理

## 禁止项（基线测试暴露的反模式）

| 错误做法 | 正确做法 |
|---------|---------|
| 遇到脏工作区主动提出修复方案（加 `.gitignore`、先 push 等） | **停下**，报告脏工作区事实，交用户决定 |
| 增加 `git tag v1.7.xx` 步骤 | DooTask 现行发布流程**不打 tag**，不要擅自添加 |
| `git add -A` / `git add .` | 按文件名显式添加发布相关改动 |
| 一次性 add + commit + push，不给确认机会 | 摘要 → 问确认 → 再 add/commit/push 三步分离 |
| 把 translate/version/build 顺序自作主张调整 | 顺序固定为 translate → version → build |
| 失败后"我再试一次"或"跳过这步" | 立即停止，交还给用户 |

## Red Flags —— 出现这些念头立即停下

- "这个脏工作区我来帮 TA 搞定一下" → 停下，交用户
- "顺便打个 tag 吧" → 不，没有这一步
- "`git add -A` 省事" → 不，显式 add
- "翻译这步没改动可以跳" → 不，按顺序执行、执行后报告结果即可
- "一起 commit + push 一气呵成" → 必须先让用户确认
- "push 上去了，发布就完成了" → 不，push 只是触发器，要确认 GitHub Actions 的 Publish 工作流 success
- "build 报 uploads 权限错，我直接删掉" → 优先 `chown` 赋权整个 `public/uploads`（不丢数据）；真要删也只删 `tmp`，别碰 uploads 下真实上传数据
