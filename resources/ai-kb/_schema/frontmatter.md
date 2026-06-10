# Frontmatter 规范

每个 chunk 文件必须以 YAML frontmatter 开头，被 `---` 包围，紧接正文。

## 完整字段表

```yaml
---
id: app.approve                    # 必填，全局唯一，kebab.dot 格式
title: 审批中心                     # 必填，单行
type: howto                        # 必填，枚举：concept | howto | faq | menu-map | glossary | shortcut
feature: approve                   # 必填，受控词表（见下）
scope: end-user                    # 必填，枚举：end-user | admin | super-admin
locale: zh                         # 必填，枚举：zh | en
aliases:                           # 必填，≥1 条；用户可能怎么问
  - 审批
  - 走流程
  - 报销审批
related_tools: []                  # 可空；关联的 MCP 工具名（来自 dootask-mcp）
related_pages: [application]       # 可空；与页面弱提示词联动的 page id
prerequisites:                     # 可空；前置条件
  - 应用市场已安装 approve 插件
negative: []                       # 可空但鼓励 ≥1 条；显式列出不支持项
last_verified: v1.7.90             # 必填，当前主程序版本号
---
```

## 字段详解

| 字段 | 类型 | 必填 | 约束 |
|---|---|---|---|
| `id` | string | 是 | 全局唯一；小写 + `.` 分隔；与 `_meta/feature-map.yaml` 中条目对应；推荐格式 `<feature>.<sub>.<type>` 如 `task.create.howto` |
| `title` | string | 是 | 单行，不超过 60 字符；用作 chunk 检索时的展示标题 |
| `type` | enum | 是 | `concept`（是什么）/ `howto`（怎么做）/ `faq`（为什么/出错怎么办）/ `menu-map`（在哪里）/ `glossary`（术语）/ `shortcut`（快捷键） |
| `feature` | enum | 是 | 受控词表见下；新增 feature 需先改 feature-map.yaml |
| `scope` | enum | 是 | `end-user`（普通成员）/ `admin`（管理员）/ `super-admin`（超管） |
| `locale` | enum | 是 | `zh` / `en`；与文件所在子目录一致 |
| `aliases` | list[string] | 是（≥1） | 用户可能怎么提问；用于 dense 检索 + 同义词桥接；用自然口语，不要堆关键词 |
| `related_tools` | list[string] | 否 | 关联的 MCP 工具名（来自 `_meta/tool-binding.yaml`），便于检索时联动工具调用 |
| `related_pages` | list[string] | 否 | 当用户在哪个页面提问最相关；与前端 page-context 系统联动 |
| `prerequisites` | list[string] | 否 | 前置条件，如"需要管理员权限"、"插件已安装" |
| `negative` | list[string] | 否（鼓励） | 显式列出不支持的能力，如"暂不支持嵌套子审批" |
| `last_verified` | string | 是 | 当前主程序版本号，如 `v1.7.90`；改了 chunk 必须同步刷新 |

## 受控词表

### `type` 全集

| type | 用途 | 示例 id |
|---|---|---|
| `concept` | 解释「是什么」 | `task.subtask.concept` |
| `howto` | 教「怎么做」 | `task.create.howto` |
| `faq` | 「为什么 / 出错怎么办」 | `auth.permission-denied.faq` |
| `menu-map` | 「X 入口在哪」 | `approve.entry.menu-map` |
| `glossary` | 术语 + 别名表（通常一个 feature 一篇） | `task.glossary` |
| `shortcut` | 快捷键、移动端手势 | `global.shortcut` |

### `scope` 全集

- `end-user` — 所有登录用户都能用
- `admin` — 系统管理员（`userIsAdmin`）
- `super-admin` — 超级管理员（只有第一个用户）

### `feature` 全集

由 `_meta/feature-map.yaml` 的顶层 `features:` 列表定义。**新增 feature 必须先改 feature-map.yaml 再写 chunk**，否则 lint 失败。

### `related_tools` 取值

必须是 `_meta/tool-binding.yaml` 已定义的工具名，否则 lint 失败。

## lint 强制规则

由 `dootask-plugins/system-plugins/ai/src/helper/kb/lint.py` 强制执行：

1. frontmatter 必填字段非空
2. `id` 全局唯一
3. `id` 与文件路径匹配规则：`zh/<type>/<feature-path>/<id-tail>.md`
4. `feature` 在 `_meta/feature-map.yaml` 的受控词表内
5. `type` / `scope` / `locale` 在各自枚举内
6. `aliases` 至少 1 条且每条 ≥ 2 字符
7. `related_tools` 中每条都在 `_meta/tool-binding.yaml` 已声明
8. `last_verified` 匹配 `v\d+\.\d+\.\d+` 模式
9. 正文（去掉 frontmatter 后）长度 200-1500 字符
10. 正文不含跨 chunk 指代词：`如上图`、`如上所述`、`如前文`、`在前面的`、`参见上节`
11. 引用其他 chunk 用 `[[id]]` 语法；id 必须存在于本仓库
12. frontmatter 与正文之间必须有一个空行

## 示例（最简骨架）

```markdown
---
id: task.create.howto.quick
title: 快速创建任务
type: howto
feature: task
scope: end-user
locale: zh
aliases:
  - 怎么建任务
  - 新建待办
  - 加一条 todo
related_tools: [create_task]
related_pages: [project_detail, task_list]
prerequisites: []
negative:
  - 快速创建不支持设置截止时间，需用完整创建
last_verified: v1.7.90
---

# 快速创建任务

## 入口
- 桌面端：项目详情页右上角「+」→「快速添加任务」
- 移动端：底部 Tabbar「+」按钮

## 操作步骤
1. 在输入框输入任务名（必填，≤ 200 字）
2. 回车提交

## 字段默认值
- 负责人：当前用户
- 状态：列表第一列（未开始）
- 优先级：普通
- 截止时间：无

## 想设置更多字段
切到完整创建模式：[[task.create.howto.full]]
```
