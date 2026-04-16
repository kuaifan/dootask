---
name: release
description: Use when releasing a new DooTask frontend version from the `pro` branch. Rigid sequential workflow (translate → version → build → commit → push) with strict pre-checks (branch, clean worktree, Node 20+) and per-step user confirmation. Use when user says "发布新版本", "release", "出新版本", "打版本". Stop on any failure; do NOT auto-fix dirty worktree, do NOT add tag step, do NOT use `git add -A`.
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

## 最终：提交并推送

所有步骤完成后：

1. 通过 `git diff` + `git status` 汇总所有变更，向用户报告摘要
2. **询问用户是否提交并推送**
3. 用户明确确认后才执行 `git add`、`git commit`、`git push`
4. 未确认一律不执行

提交规范：
- 提交信息使用 `release: v<新版本号>`（与历史提交风格一致，参见 `git log --oneline | grep '^release:'`）
- **只 add 本次发布相关改动**，按文件名显式添加（例如 `git add package.json public/js/...`），**不要用 `git add -A` 或 `git add .`**，以免卷入未跟踪的本地实验文件

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
