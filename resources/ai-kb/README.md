# ai-kb — DooTask AI 助手知识库

这是**专为大语言模型（LLM）检索使用**的 DooTask 功能知识库，不是给人类阅读的产品文档（那个在 [dootask-website](https://github.com/dootask/dootask-website) 仓库的 `help/docs`）。

它的唯一消费者是 AI 助手：用户问"看板列怎么改名 / 审批可以分支吗 / 5.4 有什么新功能"时，助手通过 RAG 检索这里的内容来作答。

## 目录结构

```
ai-kb/
├── _schema/                    写作规范（必读）
│   ├── frontmatter.md          frontmatter 字段规范 + 受控词表
│   └── chunk-style.md          chunk 写作风格 + 正反例
├── _meta/                      元数据（CI 与脚本读取）
│   ├── feature-map.yaml        feature 全集 + 每个 feature 的 chunk 清单
│   └── tool-binding.yaml       chunk ↔ MCP 工具映射
├── _eval/                      回归测试
│   └── golden-50q.yaml         50 题评估集
├── zh/                         中文知识库（P0 主战场）
│   ├── concept/                「是什么」
│   ├── howto/                  「怎么做」（含 apps/ 子目录覆盖应用中心）
│   ├── faq/                    「为什么 / 出错怎么办」
│   ├── menu-map/               「X 入口在哪」
│   ├── glossary/               术语 + 别名
│   └── shortcut/               快捷键、移动端手势
└── en/                         英文知识库（P1 起草，P0 保留空目录）
```

## 为什么不复用 dootask-website 的人类文档

| 维度 | 人类文档 | ai-kb |
|---|---|---|
| 阅读单位 | 一篇文章 | 一个 chunk（128-512 token） |
| 自包含性 | 假设从头读 | 每个 chunk 独立可懂 |
| 跨章节指代 | 「如上图所示」可以 | 禁止 |
| 截图 | 必要 | 禁止依赖（用文字描述） |
| 同义词 | 一处定义 | 显式列别名 |
| 否定信息 | 少 | 必备 |
| 元数据 | 标题即可 | 严格 frontmatter |

直接对人类文档做 RAG 召回率低、易编造，所以这里独立维护。

## 怎么开始写一个 chunk

1. 通读 [`_schema/frontmatter.md`](./_schema/frontmatter.md) — 字段规范与受控词表
2. 通读 [`_schema/chunk-style.md`](./_schema/chunk-style.md) — 写作风格与正反例
3. 在 [`_meta/feature-map.yaml`](./_meta/feature-map.yaml) 找到对应 feature 的 chunk 清单和归属批次
4. 在对应 `zh/<type>/<feature>/<id>.md` 路径下新建文件
5. 提交 PR，CI 会自动跑 lint；通过且 review 完毕后合入 main，CI 自动触发 AI 插件的 `POST /kb/reindex` 入库

## 改 DooTask 主程序后必须同步更新这里

**这是硬性约束** —— 详见主仓库根目录 `CLAUDE.md` 中「DooTask AI 知识库 (ai-kb) 同步规则」章节。新增/修改/删除任何用户可见的功能、菜单、按钮、流程、字段、API 行为、权限角色，都必须在**同一 PR**里更新对应 chunk 并把 frontmatter 的 `last_verified` 改成当前版本号。

不更新的代价是 AI 助手给用户讲错路径，比 PR 多写两行成本高得多。

## 工程接口（代码在 AI 插件那一侧）

ingest、检索、lint、eval 的实现在 `dootask-plugins/system-plugins/ai/src/helper/kb/`。本目录纯内容，不放 Python 代码。

AI 插件容器通过只读 volume mount 看到本目录：
```yaml
volumes:
  - ../../../dootask/resources/ai-kb:/app/kb-content:ro
```

触发入库（CI 或运维手动）：
```bash
curl -X POST 'http://ai-service/kb/reindex' \
  -H "X-Ingest-Token: $KB_INGEST_TOKEN" \
  -d '{"paths":["zh/howto/task-create.md"], "mode":"incremental"}'
```

容器启动时 lifespan 会自动跑一次 `ingest_all` 作为兜底。

## 维护责任

- **内容**：产品功能负责人 / PM / 技术写作者按 `_meta/feature-map.yaml` 中的 `owner` 列认领
- **schema 与受控词表**：架构组维护，修改需走 PR
- **lint / ingest / retriever 代码**：AI 插件维护组
