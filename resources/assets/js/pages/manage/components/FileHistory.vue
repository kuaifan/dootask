<template>
    <div class="file-history">
        <Table
            :width="460"
            :max-height="windowHeight - 180"
            :columns="columns"
            :data="list"
            :loading="loadIng > 0"
            :no-data-text="$L(noText)"
            stripe/>
        <Page
            v-if="total > pageSize"
            :total="total"
            :current="page"
            :page-size="pageSize"
            :disabled="loadIng > 0"
            :simple="true"
            @on-change="setPage"
            @on-page-size-change="setPageSize"/>
    </div>
</template>

<style lang="scss" scoped>
.file-history {
    .ivu-page {
        margin-top: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
<script>
import {mapState} from "vuex";

export default {
    name: "FileHistory",
    props: {
        value: {
            type: Boolean,
            default: false
        },
        fileId: {
            type: Number,
            default: 0
        },
    },

    data() {
        return {
            loadIng: 0,

            columns: [
                {
                    title: this.$L('日期'),
                    key: 'created_at',
                    width: 168,
                }, {
                    title: this.$L('创建人'),
                    width: 120,
                    render: (h, {row}) => {
                        return h('UserAvatar', {
                            props: {
                                showName: true,
                                size: 22,
                                userid: row.userid,
                            }
                        })
                    }
                }, {
                    title: this.$L('大小'),
                    key: 'size',
                    width: 80,
                    render: (h, {row}) => {
                        return h('AutoTip', $A.bytesToSize(row.size));
                    }
                }, {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, {row, column}) => {
                        const vNodes = [
                            h('div', {
                                style: {
                                    fontSize: '13px',
                                    cursor: 'pointer',
                                    color: '#8bcf70',
                                },
                                on: {
                                    'click': () => {
                                        this.$emit('on-select', row)
                                    }
                                },
                            }, this.$L('读取')),
                        ];
                        return h('TableAction', {
                            props: {
                                column: column
                            }
                        }, vNodes);
                    }
                }
            ],
            list: [],

            page: 1,
            pageSize: 10,
            total: 0,
            noText: ''
        }
    },

    mounted() {

    },

    watch: {
        value: {
            handler(val) {
                if (val) {
                    this.setPage(1);
                }
            },
            immediate: true,
        },
    },

    computed: {
        ...mapState(['windowHeight'])
    },

    methods: {
        getLists() {
            if (this.fileId === 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'file/content/history',
                data: {
                    id: this.fileId,
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 10),
                },
            }).then(({data}) => {
                this.page = data.current_page;
                this.total = data.total;
                this.list = data.data;
                this.noText = '没有相关的数据';
            }).catch(() => {
                this.noText = '数据加载失败';
            }).finally(_ => {
                this.loadIng--;
            })
        },

        setPage(page) {
            this.page = page;
            this.getLists();
        },

        setPageSize(pageSize) {
            this.page = 1;
            this.pageSize = pageSize;
            this.getLists();
        },
    }
}
</script>
