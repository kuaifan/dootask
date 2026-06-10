# ai-kb — DooTask AI 助手知识库

这是**专为大语言模型（LLM）检索使用**的 DooTask 功能知识库，不是给人类阅读的产品文档（那个在 [dootask-website](https://github.com/dootask/dootask-website) 仓库的 `help/docs`）。

它的唯一消费者是 AI 助手：用户问"看板列怎么改名 / 审批可以分支吗 / 5.4 有什么新功能"时，助手通过 RAG 检索这里的内容来作答。

## 目录结构

```
ai-kb/
├── _schema/                    写作规范（必读）
│   ├── frontmatter.md          frontmatter 字段规范 + 受控词表
│   └── chunk-style.md          chunk 写作风格 + 正反例
├── _meta/                      元数据（lint / eval 脚本读取）
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
5. 提交 PR、review 后合入。内容进索引不需要额外操作：AI 插件容器每次启动会按文件 hash 对账（reconcile），自动增量收敛新增/变更/删除；想免重启即时生效可手动调 `POST /kb/reindex`

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

内容同步机制：容器每次启动按文件 hash 对账（reconcile），自动增量收敛新增/变更/删除的 markdown——客户实例更新 DooTask 后重启插件容器即生效。需要免重启即时生效时手动触发：
```bash
curl -X POST 'http://ai-service/kb/reindex' \
  -H "X-Ingest-Token: $KB_INGEST_TOKEN" \
  -d '{"mode":"reconcile"}'
```

## 用检索打点与用户反馈数据迭代内容

主程序记录了两类质量数据（mariadb，表前缀以实际 `DB_PREFIX` 为准，下例用 `pre_`）：

- `pre_ai_assistant_search_logs` — 每次 `search_help_docs` 检索一行（query、命中 source、分数、是否空结果）
- `pre_ai_assistant_feedbacks` — 用户对 AI 回复的 👍/👎（含回复引用的 source id 列表）

**口径 1 —— 近 14 天低质量检索 top 问题（直接产出待补 chunk 清单）：**

注意：向量 KNN 检索几乎总能返回 top-5（胡乱提问也会命中分数偏低的近邻），所以 `empty=1`
基本只在所查语种库为空时出现（如英文库未起草）。"知识库覆盖不到"的主信号是 **top_score 低**。

```sql
SELECT query, locale, ROUND(AVG(top_score),3) AS avg_score, COUNT(*) AS cnt
FROM pre_ai_assistant_search_logs
WHERE (empty = 1 OR top_score < 0.8) AND created_at >= NOW() - INTERVAL 14 DAY
GROUP BY query, locale ORDER BY cnt DESC LIMIT 30;
```

**口径 2 —— 分数阈值校准（先看全局分布再定口径 1 的阈值）：**
```sql
SELECT ROUND(top_score,1) AS bucket, COUNT(*) AS cnt
FROM pre_ai_assistant_search_logs
WHERE created_at >= NOW() - INTERVAL 14 DAY
GROUP BY bucket ORDER BY bucket;
```

**口径 3 —— 👎 最多的 source（待修订清单）：**
```sql
SELECT jt.sid, COUNT(*) AS dislikes
FROM pre_ai_assistant_feedbacks f
JOIN JSON_TABLE(f.source_ids, '$[*]' COLUMNS (sid VARCHAR(191) PATH '$')) jt
WHERE f.feedback = 'dislike' AND f.created_at >= NOW() - INTERVAL 30 DAY
GROUP BY jt.sid ORDER BY dislikes DESC LIMIT 20;
```

钻取某条 👎 当时检索到了什么：`SELECT * FROM pre_ai_assistant_search_logs WHERE context_key = '<feedback.session_id>'`。

**迭代流程**：每周跑口径 1/3 → 空结果高频 query 补新 chunk 或给现有 chunk 加 aliases → 👎 集中的 source 重写正文/negative → 提 PR → 容器重启 reconcile（或手动 `/kb/reindex`）→ 下周对比同 query 的 empty 率与 👎 数验证收效。

## 维护责任

- **内容**：产品功能负责人 / PM / 技术写作者按 `_meta/feature-map.yaml` 中的 `owner` 列认领
- **schema 与受控词表**：架构组维护，修改需走 PR
- **lint / ingest / retriever 代码**：AI 插件维护组
