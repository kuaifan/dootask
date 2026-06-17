<template>
    <div class="page-share">
        <div class="share-header">
            <div class="share-cancel" @click="onCancel">{{ $L('取消') }}</div>
            <div class="share-title">{{ $L('发送给') }}</div>
            <div class="share-action" @click="onSend" :class="{ disabled: !canSend }">
                {{ sendingState || $L('发送') }}
            </div>
        </div>

        <div v-if="loading" class="share-loading">
            <Loading :content="$L('加载中')"/>
        </div>

        <div v-else-if="errorMessage" class="share-error">
            <p>{{ errorMessage }}</p>
            <Button @click="onCancel">{{ $L('返回') }}</Button>
        </div>

        <template v-else>
            <div class="share-preview">
                <div v-if="payload.text" class="share-preview-text">{{ payload.text }}</div>
                <div v-if="payload.url" class="share-preview-url">{{ payload.url }}</div>
                <div v-if="payload.items && payload.items.length" class="share-preview-items">
                    <div
                        v-for="(item, idx) in payload.items"
                        :key="idx"
                        class="share-preview-item"
                    >
                        <div class="name">{{ item.name }}</div>
                        <div class="meta">{{ formatSize(item.size) }} · {{ item.mime }}</div>
                    </div>
                </div>
            </div>

            <div class="share-search">
                <Input
                    v-model="keyword"
                    :placeholder="$L('搜索会话')"
                    clearable
                    @on-change="loadList"
                />
            </div>

            <div class="share-list">
                <div
                    v-for="item in list"
                    :key="item.name + '_' + (item.extend && item.extend.dialog_ids)"
                    class="share-list-item"
                    :class="{ selected: isSelected(item) }"
                    @click="toggleSelect(item)"
                >
                    <img class="icon" :src="item.icon" alt=""/>
                    <div class="name">{{ item.name }}</div>
                    <div v-if="isSelected(item)" class="check">✓</div>
                </div>
                <div v-if="!list.length" class="share-list-empty">{{ $L('暂无会话') }}</div>
            </div>
        </template>
    </div>
</template>

<script>
import Loading from "../components/Loading.vue";

/**
 * 跨 App 分享接收页（二期 WS11）
 *
 * 流程：原生 (iOS Share Extension / Android Intent) 写共享 payload 到本地
 *   → deep link `dootask://share?token=<x>` → __handleLink → 跳本页
 *   → 调 $A.nativeAppGetSharedPayload(token) 拿元数据
 *   → 拉 api/users/share/list 渲染会话/用户列表（type='text' 接口仅返回会话，简洁）
 *   → 用户选会话 → POST api/dialog/msg/sendfiles 上传
 *   → 调 $A.nativeAppClearSharedPayload(token) 清理 + 跳目标会话
 */
export default {
    name: "share",
    components: { Loading },
    data() {
        return {
            token: "",
            payload: { items: [], text: undefined, url: undefined },
            list: [],
            keyword: "",
            selectedIds: [],
            loading: true,
            errorMessage: "",
            sendingState: "",
        };
    },
    computed: {
        canSend() {
            if (!this.selectedIds.length) return false;
            const hasAttachment =
                (this.payload.items && this.payload.items.length > 0) ||
                this.payload.text ||
                this.payload.url;
            return !!hasAttachment && !this.sendingState;
        },
    },
    mounted() {
        this.token = String(this.$route.query.token || "");
        if (!this.token) {
            this.errorMessage = this.$L("缺少分享凭据");
            this.loading = false;
            return;
        }
        this.loadPayload();
    },
    methods: {
        async loadPayload() {
            try {
                const result = await this.callBridge("getSharedPayload", { token: this.token });
                if (!result) {
                    this.errorMessage = this.$L("分享内容不存在或已过期");
                    this.loading = false;
                    return;
                }
                this.payload = result;
                await this.loadList();
            } catch (e) {
                this.errorMessage = (e && e.message) || this.$L("加载失败");
            } finally {
                this.loading = false;
            }
        },
        async loadList() {
            // type='text' 仅返回会话列表（无文件夹），用于普通会话分享场景；
            // 若用户希望分享到"文件"模块需用 type='file'，二期暂不暴露切换。
            const params = { url: "users/share/list", data: { type: "text", key: this.keyword } };
            try {
                const { data } = await this.$store.dispatch("call", params);
                const lists = Array.isArray(data) ? data : (data && data.lists) || [];
                this.list = lists.filter(it => it.type === "item");
            } catch (e) {
                this.errorMessage = (e && e.msg) || this.$L("加载会话列表失败");
            }
        },
        isSelected(item) {
            const id = item.extend && item.extend.dialog_ids;
            return !!id && this.selectedIds.indexOf(id) !== -1;
        },
        toggleSelect(item) {
            const id = item.extend && item.extend.dialog_ids;
            if (!id) return;
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) this.selectedIds.push(id);
            else this.selectedIds.splice(idx, 1);
        },
        formatSize(bytes) {
            if (!bytes && bytes !== 0) return "";
            if (bytes < 1024) return bytes + " B";
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
            return (bytes / (1024 * 1024)).toFixed(1) + " MB";
        },
        callBridge(method, args) {
            if (window.__nativeBridge && typeof window.__nativeBridge.call === "function") {
                return window.__nativeBridge.call(method, args);
            }
            return Promise.resolve(null);
        },
        async onSend() {
            if (!this.canSend) return;
            const dialogIds = this.selectedIds.join(",");

            try {
                if (this.payload.items && this.payload.items.length) {
                    this.sendingState = this.$L("正在发送");
                    for (const item of this.payload.items) {
                        await this.uploadOne(dialogIds, item);
                    }
                }
                // 文本/URL：拼成普通消息发到每个会话
                const extraText = [this.payload.text, this.payload.url].filter(Boolean).join("\n");
                if (extraText) {
                    await this.$store.dispatch("call", {
                        url: "dialog/msg/sendtext",
                        method: "post",
                        data: { dialog_ids: dialogIds, text: extraText, text_type: "text" },
                    });
                }

                // 清理共享文件
                await this.callBridge("clearSharedPayload", { token: this.token });

                // 跳到第一个目标会话
                this.$store.state.dialogId = this.selectedIds[0];
                this.$router.replace({ name: "manage-messenger" });
            } catch (e) {
                $A.modalError({ content: (e && e.msg) || this.$L("发送失败") });
                this.sendingState = "";
            }
        },
        async uploadOne(dialogIds, item) {
            // file:// uri → Blob → multipart upload
            const res = await fetch(item.uri);
            const blob = await res.blob();
            const form = new FormData();
            form.append("dialog_ids", dialogIds);
            form.append("files", blob, item.name || "shared");
            await this.$store.dispatch("call", {
                url: "dialog/msg/sendfiles",
                method: "post",
                data: form,
                headers: { "Content-Type": "multipart/form-data" },
            });
        },
        async onCancel() {
            if (this.token) {
                try { await this.callBridge("clearSharedPayload", { token: this.token }); } catch (e) { /* ignore */ }
            }
            this.$router.replace({ name: "index" });
        },
    },
};
</script>

<style lang="scss" scoped>
.page-share {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--ee-bg-color, #f7f8fa);

    .share-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #fff;
        border-bottom: 1px solid #eaecef;

        .share-title {
            flex: 1;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }

        .share-cancel,
        .share-action {
            font-size: 14px;
            color: #1890ff;
            cursor: pointer;
        }
        .share-action.disabled {
            color: #c0c4cc;
            cursor: not-allowed;
        }
    }

    .share-loading,
    .share-error {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px;
        text-align: center;
    }

    .share-preview {
        background: #fff;
        padding: 12px 16px;
        border-bottom: 1px solid #eaecef;

        .share-preview-text,
        .share-preview-url {
            padding: 8px 0;
            font-size: 14px;
            word-break: break-all;
        }
        .share-preview-url { color: #1890ff; }

        .share-preview-items {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .share-preview-item {
            padding: 6px 8px;
            background: #f0f2f5;
            border-radius: 4px;
            .name { font-size: 13px; }
            .meta { font-size: 11px; color: #8c8c8c; margin-top: 2px; }
        }
    }

    .share-search {
        padding: 8px 12px;
        background: #fff;
        border-bottom: 1px solid #eaecef;
    }

    .share-list {
        flex: 1;
        overflow-y: auto;
        background: #fff;

        .share-list-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;

            &.selected { background: #e6f7ff; }

            .icon {
                width: 36px;
                height: 36px;
                border-radius: 18px;
                margin-right: 12px;
                object-fit: cover;
            }
            .name {
                flex: 1;
                font-size: 14px;
            }
            .check {
                font-weight: 600;
                color: #1890ff;
            }
        }

        .share-list-empty {
            padding: 32px;
            text-align: center;
            color: #8c8c8c;
            font-size: 13px;
        }
    }
}
</style>
