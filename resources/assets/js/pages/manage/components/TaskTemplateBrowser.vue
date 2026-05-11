<template>
    <Modal
        v-model="show"
        :title="$L('搜索模板')"
        :footer-hide="true"
        :width="640"
        class="task-template-browser">
        <div class="search-wrap">
            <Input
                ref="search"
                v-model="keyword"
                :placeholder="$L('搜索模板')"
                clearable
                @on-change="onKeywordChange"
                @keydown.native.up.prevent="moveSelection(-1)"
                @keydown.native.down.prevent="moveSelection(1)"
                @keydown.native.enter.prevent="confirmSelection" />
        </div>
        <div ref="listWrap" class="list-wrap" @scroll="onScroll">
            <div
                v-for="(item, idx) in items"
                :key="item.id"
                :class="['item', {selected: idx === selectedIndex}]"
                @click="pick(item)"
                @mouseenter="selectedIndex = idx">
                <div class="item-name">{{ item.name }}</div>
                <div class="item-meta">
                    <span class="origin">{{ $L('来自(*)', item.project_name || '') }}</span>
                    <span v-if="item.user_name" class="creator">· @{{ item.user_name }}</span>
                </div>
            </div>
            <div v-if="loading" class="loading">
                <Loading />
                {{ $L('加载中') }}
            </div>
            <div v-if="!loading && items.length === 0" class="empty">{{ $L('暂无可用模板') }}</div>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'TaskTemplateBrowser',
    props: {
        value: { type: Boolean, default: false },
        currentProjectId: { type: Number, default: 0 },
    },
    data() {
        return {
            keyword: '',
            page: 1,
            pageSize: 20,
            total: 0,
            items: [],
            loading: false,
            selectedIndex: 0,
            keywordTimer: null,
            fetchSeq: 0,
        }
    },
    beforeDestroy() {
        clearTimeout(this.keywordTimer)
    },
    computed: {
        show: {
            get() { return this.value },
            set(v) { this.$emit('input', v) },
        },
    },
    watch: {
        show(v) {
            if (v) {
                this.keyword = ''
                this.page = 1
                this.items = []
                this.selectedIndex = 0
                this.fetch()
                this.$nextTick(() => {
                    this.$refs.search && this.$refs.search.focus && this.$refs.search.focus()
                })
            }
        },
    },
    methods: {
        onKeywordChange() {
            clearTimeout(this.keywordTimer)
            this.keywordTimer = setTimeout(() => {
                this.page = 1
                this.items = []
                this.selectedIndex = 0
                this.fetch()
            }, 200)
        },
        onScroll() {
            const el = this.$refs.listWrap
            if (!el || this.loading) return
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
                if (this.items.length < this.total) {
                    this.page += 1
                    this.fetch(true)
                }
            }
        },
        async fetch(append = false) {
            const seq = ++this.fetchSeq
            this.loading = true
            try {
                const {data} = await this.$store.dispatch('call', {
                    url: 'project/task/template_search',
                    data: {
                        keyword: this.keyword,
                        current_project_id: this.currentProjectId || 0,
                        page: this.page,
                        page_size: this.pageSize,
                    },
                })
                if (seq !== this.fetchSeq) return  // 后续请求已发起，丢弃此次结果
                this.total = data.total || 0
                this.items = append ? [...this.items, ...(data.items || [])] : (data.items || [])
            } finally {
                if (seq === this.fetchSeq) this.loading = false
            }
        },
        moveSelection(delta) {
            if (this.items.length === 0) return
            const next = this.selectedIndex + delta
            this.selectedIndex = Math.max(0, Math.min(this.items.length - 1, next))
        },
        confirmSelection() {
            const item = this.items[this.selectedIndex]
            if (item) this.pick(item)
        },
        pick(item) {
            this.$emit('pick', item)
            this.show = false
        },
    },
}
</script>

<style lang="scss" scoped>
.task-template-browser {
    .search-wrap {
        margin-bottom: 8px;
    }
    .list-wrap {
        max-height: 420px;
        overflow-y: auto;
        > div {
            &:last-child {
                margin-bottom: 12px;
            }
        }
    }
    .item {
        padding: 10px 12px;
        border-radius: 4px;
        cursor: pointer;
        &.selected {
            background: rgba(64, 158, 255, 0.1);
        }
        .item-name {
            font-weight: 500;
        }
        .item-meta {
            margin-top: 4px;
            font-size: 12px;
            color: #909399;
            .creator {
                margin-left: 4px;
            }
        }
    }
    .loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        .common-loading {
            width: 18px;
            height: 18px;
            margin: 0;
        }
    }
    .loading, .empty {
        text-align: center;
        padding: 20px;
        color: #909399;
    }
}
</style>
