<?php

/*
|--------------------------------------------------------------------------
| DooTask AI 助手灰度配置
|--------------------------------------------------------------------------
|
| RAG（帮助知识库检索）功能上线时按以下顺序灰度：
|   Stage 1 — staging：RAG_ENABLED=true 仅 staging 环境，全体可用
|   Stage 2 — canary：RAG_ENABLED=true + RAG_CANARY_USERIDS="1,2,3,4,5"
|                     仅白名单 user 命中 RAG
|   Stage 3 — broad：清空 RAG_CANARY_USERIDS，全局启用
|
| 紧急关停（kill switch，5 分钟生效）：
|   1) 改容器 env RAG_ENABLED=false
|   2) ./cmd php restart 让 swoole 重读 config
|   3) AI 容器收到 rag_enabled=0 时跳过 RAG hint 注入与 search_help_docs 工具挂载
|
| 灰度判定语义：
|   rag_enabled (env total switch)
|     ├─ false  → 所有人都不走 RAG（kill switch）
|     └─ true   → 进一步看 canary：
|           ├─ rag_canary_userids 为空（默认）→ 全员启用
|           └─ rag_canary_userids 有值        → 仅白名单 userid 启用
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | RAG 总开关
    |--------------------------------------------------------------------------
    | true  - 默认开启，按 canary 白名单进一步过滤
    | false - 紧急 kill switch，所有用户都不走 RAG
    */
    'rag_enabled' => filter_var(env('RAG_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | RAG canary 白名单
    |--------------------------------------------------------------------------
    | 逗号分隔的 userid 列表。
    | 留空表示 全员启用（Stage 3 broad rollout）。
    | 有值表示 仅白名单 userid 命中 RAG（Stage 2 canary）。
    */
    'rag_canary_userids' => env('RAG_CANARY_USERIDS', ''),
];
