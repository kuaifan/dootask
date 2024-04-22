<template>
    <div class="file-history">
        <Table
            :width="(windowWidth - 40) > 480 ? 480 : (windowWidth - 40)"
            :max-height="windowHeight - 180"
            :columns="columns"
            :data="list"
            :loading="loadIng > 0"
            :no-data-text="$L(noText)"
            highlight-row
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
export default {
    name: "FileHistory",
    props: {
        value: {
            type: Boolean,
            default: false
        },
        file: {
            type: Object,
            default: () => {
                return {};
            }
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
                    width: 90,
                    render: (h, {row}) => {
                        return h('AutoTip', $A.bytesToSize(row.size));
                    }
                }, {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, {index, row, column}) => {
                        if (index === 0 && this.page === 1) {
                            return h('div', '-');
                        }
                        return h('TableAction', {
                            props: {
                                column: column,
                                menu: [
                                    {
                                        label: this.$L('查看'),
                                        action: "preview",
                                    }, {
                                        label: this.$L('还原'),
                                        action: "restore",
                                    }
                                ]
                            },
                            on: {
                                action: (name) => {
                                    this.onAction(name, row)
                                }
                            }
                        });
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
        fileId() {
            return this.file.id || 0
        },
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

        onAction(name, row) {
            switch (name) {
                case 'restore':
                    this.$emit('on-restore', row)
                    break;

                case 'preview':
                    const title = $A.getFileName(this.file) + ` [${row.created_at}]`;
                    const path = `/single/file/${this.fileId}?history_id=${row.id}&history_title=${title}`;
                    if (this.$Electron) {
                        this.$store.dispatch('openChildWindow', {
                            name: `file-${this.fileId}-${row.id}`,
                            path: path,
                            userAgent: "/hideenOfficeTitle/",
                            force: false,
                            config: {
                                title,
                                titleFixed: true,
                                parent: null,
                                width: Math.min(window.screen.availWidth, 1440),
                                height: Math.min(window.screen.availHeight, 900),
                            },
                            webPreferences: {
                                nodeIntegrationInSubFrames: this.file.type === 'drawio'
                            },
                        });
                    } else if (this.$isEEUiApp) {
                        this.$store.dispatch('openAppChildPage', {
                            pageType: 'app',
                            pageTitle: title,
                            url: 'web.js',
                            params: {
                                titleFixed: true,
                                allowAccess: true,
                                url: $A.rightDelete(window.location.href, window.location.hash) + `#${path}`
                            },
                        })
                    } else {
                        window.open($A.mainUrl(path.substring(1)))
                    }
                    break;
            }
        },
    }
}
</script>
