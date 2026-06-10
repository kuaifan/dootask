# AI 内联深链 · 插件侧改动规格（dootask-ai，独立仓库）

> 本仓库（主程序）已完成：深链目录、前端渲染/执行、移除 driver.js 引导。
> 本文是配套的 **AI 插件仓库** 改动规格——只有插件侧改完，AI 才会真正在回复里输出深链。
> 在插件改动落地前，主程序侧深链链路可用 `window.__openDeepLink('setting_system')` 或手工构造回复文本验证。

## 背景
原「带我去」分步引导（driver.js + `show_guide` MCP 工具）已下线。替代方案：AI 在回复正文里把"可定位的页面/面板"词包成 **标准 markdown 链接**，前端渲染成可点 chip，点击直达那一屏。

## 需要做的改动

### 1. 下线引导
- 删除/注销 `show_guide` MCP 工具（工具定义 + 注册 + system prompt 里对它的说明）。
- 删除 system prompt 里关于 ` ```ai-guide ` 围栏脚本（steps/target/pre_action）的全部指导。

### 2. 启用深链
- 把主程序 `resources/ai-kb/_meta/page-links.yaml` 的目录（`id / title / aliases / description`）注入 system prompt。建议在 ingest ai-kb `_meta` 时一并读取，渲染成一张"可用深链 id 表"。
- 在 prompt 中约定输出语法与硬约束：

  ```
  当回答涉及"某功能/设置在哪、怎么去某页面"时，把页面/面板名写成 markdown 链接：
    [显示文字](dootask://link/<id>)
  规则：
  - <id> 只能取下表中的值（闭集，禁止臆造；不确定就不要加链接，写普通文字）
  - 一个目的地在一段话里最多链一次，不要给同一名词重复加链接
  - 只链"页面/面板"这类导航目的地；不要给动作词（点击/保存/开启）加链接
  - 深链只把用户送到那一屏，页面内具体控件仍用文字说明
  ```

- 典型例子（供 few-shot）：
  - 问「在哪里设置端到端加密」→ 「……在 [系统设置](dootask://link/setting_system) 的消息相关里开启。」
  - 问「怎么改个人资料」→ 「打开 [个人设置](dootask://link/setting_personal) 即可修改。」

### 3. 性质
- 与原「带我去」一样属**建议性**：AI 是否生成深链取决于是否遵循 prompt。
- 前端已对**非法 id 做兜底**（渲染时退化为纯文字，绝不出现死链），故 prompt 约束 + 前端校验双保险。

## 与主程序的契约
- id 闭集 = `page-links.yaml` 的 `links:` 键集合，当前 21 个；主程序 `deep-links.js` 与之逐一对应（CI 校验 `tests/deep-links-parity.mjs`）。
- 新增/调整深链目的地时：先改主程序 `page-links.yaml` + `deep-links.js`（同一提交），再同步插件注入的 id 表。
