---
name: dootask-release
description: 从 `pro` 分支发布 DooTask 前端新版本：翻译 → 版本号/更新日志 → 构建 → 提交推送，刚性顺序、每步确认、失败即停。
---

# DooTask 发布流程

**刚性技能**——严格按顺序执行，每步向用户确认，任何一步失败立即停止。

## 核心原则

按固定顺序执行，不增删、合并或重排步骤。翻译（Step 1）和更新日志（Step 2）由你直接产出；脚本只做确定性机械工作（算版本号、检测差异、字节级生成语言文件）。

## 前置检查（全部通过才能继续）

执行任何发布步骤前，依次检查：

1. **分支**：必须是 `pro`，否则停止，提示用户切换
2. **工作区**：`git status` 必须干净（无未提交变更、无未跟踪文件），否则**停止**并交由用户处理
3. **Node.js**：`node --version` 必须 ≥ 20
4. **PHP**：`php --version` 必须可用（Step 1 的脚本依赖本地 php，无需容器）。若 host 无 php，停止并提示用户

检查通过后汇报结果，用户确认后再开始执行。

## 发布步骤

**每步执行前**向用户确认；**每步执行后**报告结果。

开始前先把这份清单复制到你的回复里，逐项勾选、跟踪进度：

```
发布进度：
- [ ] 前置检查（分支 pro / 工作区干净 / node≥20 / php 可用）
- [ ] Step 1 翻译（diff → 翻译 → apply → generate）
- [ ] Step 2 版本号 + CHANGELOG
- [ ] Step 3 构建（./cmd prod）
- [ ] 汇总变更 → 用户确认 → commit + push
- [ ] 确认 GitHub Actions Publish 工作流 success
```

---

### Step 1: 翻译

多语言数据流：`language/original-{web,api}.txt`（原文/简体中文）→ 经翻译写入 `language/translate.json`（含 9 种语言）→ 生成 `public/language/{web,api}/*`。

**1.1 检测差异**

```shell
php .claude/skills/dootask-release/scripts/language.php diff
```

输出 JSON：
- `regexErrorCount > 0`：translate.json **已有条目**的占位符与某语言值不一致 → **停止**，报告 `regexErrors`，交用户修复（这是历史数据问题，不要自行猜测修改）
- `redundantCount > 0`：translate.json 里有、但原文已删除的条目 → 仅作提示（apply 时会自动剔除，不致命）
- `needsCount == 0`：无新文案 → **跳到 1.4 直接生成**
- `needsCount > 0`：`needs` 数组即待翻译清单，每项 `key` 已转成占位符形式（如 `(%T1)`）→ 进入 1.2

**1.2 翻译**

对 `needs` 里的每个 `key`，翻成 8 种语言（`zh` 留空、`key` 原样保留）：`zh-CHT` `en` `ko` `ja` `de` `fr` `id` `ru`。

要求：贴合「项目任务管理系统」语境；占位符 `(%T1)`/`(%M1)` 等原样保留、不可增删改，位置可随目标语言语序调整：

| 原文 | 翻成英语 |
|---|---|
| (%T1)的周报[(%T2)][(%T3)月第(%T4)周] | Weekly report of (%T1) [(%T2)] [Week (%T4) of month (%T3)] |
| (%T1)提交的「(%M2)」待你审批 | '(%M2)' submitted by (%T1) is waiting for your approval |

把结果写成一个 JSON 数组文件（建议放 `/tmp/dootask-release-translated.json`，避免污染工作区），每个元素含全部 10 个字段，顺序为：
`key, zh, zh-CHT, en, ko, ja, de, fr, id, ru`（`zh` 写 `""`）。

```json
[
  {"key":"...(%T1)...","zh":"","zh-CHT":"...","en":"...","ko":"...","ja":"...","de":"...","fr":"...","id":"...","ru":"..."}
]
```

**1.3 合并进 translate.json**

```shell
php .claude/skills/dootask-release/scripts/language.php apply /tmp/dootask-release-translated.json
```

脚本会校验字段完整性与占位符完整性、追加新条目、剔除冗余项，并按项目原生格式写回 `translate.json`。任一条不合格会报错停止，按提示修正翻译后重试。

**1.4 生成前端/后端语言文件**

```shell
php .claude/skills/dootask-release/scripts/language.php generate
```

由 `translate.json` 字节级重新生成 `public/language/web/*.js` 与 `public/language/api/*.json`（排序/转义与项目原生工具完全一致，正常情况下 diff 只包含本次新增条目）。

**1.5 报告**：用 `git status --short language public/language` 汇总本步改动，向用户报告新增了多少条翻译。

---

### Step 2: 版本号 + 更新日志

**2.1 计算并写入版本号**

```shell
node .claude/skills/dootask-release/scripts/version_bump.js
```

脚本据 git 历史算出新 `version` 与 `codeVerson` 并写入 `package.json`，输出 JSON 含：`version`、`prevVersion`、`changelogRange`（如 `<上次release提交>..HEAD`，用于下一步圈定本次更新范围）。

**2.2 撰写 CHANGELOG**

读取本次区间的提交：

```shell
git log <changelogRange> --stat
```

`--stat` 会带上每个提交的完整描述正文 + 改动文件清单；光看标题不够时用 `git show <hash>` 看具体代码改动。

按 `CHANGELOG.md` 现有格式，在文件顶部 `# Changelog` 说明段之后、紧挨上一个 `## [...]` 之前，插入新版本区段：

```markdown
## [<version>]

### Features

- ...

### Bug Fixes

- ...

### Performance

- ...
```

撰写要求（对齐项目历史风格）：
- 小节标题用**英文 Title Case**：`Features` / `Bug Fixes` / `Performance` / `Documentation` / `Security` / `Miscellaneous`，**不要译成中文**；**没有内容的小节整段省略**。
- 条目正文用**通俗友好的简体中文**，面向**普通用户**描述更新带来的直接好处，**避免技术术语**（如 refactor、merge branch、commit lint、bump deps 等）。
- 过滤掉对用户无意义的提交（纯构建/依赖/CI/合并提交、本技能自身的脚手架改动等）。
- 仅凭提交标题无法判断是否对用户有价值时，结合提交的完整描述正文和实际代码改动（`git show <hash>`）再决定，不要只看一行就下结论。
- 合并相似项；每个小节内**按用户价值与影响范围排序，重要的在前**。

**2.3 报告**：展示新版本号与你写的 changelog 区段，请用户过目。

---

### Step 3: 构建前端

```shell
./cmd prod
```

构建前端生产版本。用 `./cmd prod`，不要换成裸跑 vite（它还负责 node 检查、清 `public/js/build`、debug 切换）。

> **已知失败**：build 报 `public/uploads/...` 的 `EACCES: permission denied, copyfile`，是 vite 复制 `public/` 时撞到 root 属主的运行时上传文件（不限于 `tmp`，`avatar` 等都可能）。补救是赋权、不是删数据——把 uploads 属主改回当前用户后重试：
> ```shell
> sudo chown -R "$(id -u):$(id -g)" public/uploads
> ```
> `public/uploads` 是真实上传数据，**不要删**；即便要清也只清 `public/uploads/tmp`。

---

## 最终：提交并推送

所有步骤完成后：

1. 通过 `git diff` + `git status` 汇总所有变更，向用户报告摘要
2. **询问用户是否提交并推送**
3. 用户明确确认后才执行 `git add`、`git commit`、`git push`
4. 未确认一律不执行

提交规范：
- 提交信息使用 `release: v<新版本号>`（与历史一致，参见 `git log --oneline | grep '^release:'`）
- **只 add 本次发布相关改动**，按文件名/目录显式添加（例如 `git add package.json CHANGELOG.md language/translate.json public/language public/js`），不要用 `git add -A` / `git add .`，以免卷入未跟踪的本地实验文件
- 不打 git tag（现行发布流程不使用 tag）
- 确认前先核对：`/tmp/dootask-release-translated.json` 等临时文件不在仓库内，工作区不应残留发布无关的未跟踪文件

## push 之后：确认发布工作流（CI 才是真正出包）

push 到 `pro` 只是触发器，真正的构建/出包由 GitHub Actions 完成——**push 成功 ≠ 发布完成**：

- **Publish**（`.github/workflows/publish.yml`，push→pro 触发）跑完才算出包；成功后会自动触发 **Sync to Gitee**（镜像同步）。
- push 完成后**主动确认** Publish 工作流 `conclusion=success`。优先用 `gh`（未装可临时装；公开仓库也可用 GitHub REST API 免鉴权读取 runs）：
  ```shell
  gh run list --workflow=publish.yml -R kuaifan/dootask -L 1
  gh run view <run-id> -R kuaifan/dootask --json status,conclusion,url
  ```
- 工作流仍在跑时，挂后台轮询、结束即通知用户，**不要在前台死等**。

### iOS 发布（询问后决定）

`ios-publish.yml` 是**独立的手动工作流**（`workflow_dispatch`），不随 push 触发。Publish 成功后，用 options 或 AskUserQuestion 形式提问是否同时发布 iOS（选项：发布 iOS / 不发布）：

- 选「发布 iOS」才执行：
  ```shell
  gh workflow run ios-publish.yml --ref pro -R kuaifan/dootask
  ```
  需 `gh` 已登录且 token 含 `workflow` 权限；触发后可挂后台轮询结果。
- 选「不发布」则结束。

## 失败处理

任何步骤失败立即停止、报告错误信息，交用户决定；不要自动重试或跳过。
