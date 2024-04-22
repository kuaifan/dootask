<template>
    <div class="task-content-history">
        <Table
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
.task-content-history {
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
    name: "TaskContentHistory",
    props: {
        taskId: {
            type: Number,
            default: 0
        },
        taskName: {
            type: String,
            default: ''
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
                    title: this.$L('描述'),
                    key: 'desc',
                    ellipsis: true,
                    minWidth: 150,
                    render: (h, {row}) => {
                        return h('span', row.desc || '-');
                    }
                }, {
                    title: this.$L('创建人'),
                    width: 120,
                    render: (h, {row}) => {
                        if (!row.userid) {
                            return h('div', '-');
                        }
                        return h('UserAvatar', {
                            props: {
                                showName: true,
                                size: 22,
                                userid: row.userid,
                            }
                        })
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
        taskId: {
            handler(val) {
                if (val) {
                    this.setPage(1);
                }
            },
            immediate: true,
        },
    },

    methods: {
        getLists() {
            if (this.taskId === 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/content_history',
                data: {
                    task_id: this.taskId,
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
                case 'preview':
                    const title = (this.taskName || `ID: ${this.taskId}`) + ` [${row.created_at}]`;
                    const path = `/single/task/content/${this.taskId}?history_id=${row.id}&history_title=${title}`;
                    if (this.$Electron) {
                        this.$store.dispatch('openChildWindow', {
                            name: `task-content-${this.taskId}-${row.id}`,
                            path: path,
                            force: false,
                            config: {
                                title: title,
                                titleFixed: true,
                                parent: null,
                                width: Math.min(window.screen.availWidth, 1440),
                                height: Math.min(window.screen.availHeight, 900),
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
