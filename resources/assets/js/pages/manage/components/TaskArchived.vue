<template>
    <div class="task-archived">
        <div class="archived-title">
            {{$L('归档的任务')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
                <Icon v-else type="ios-refresh" @click="refresh"/>
            </div>
        </div>
        <div class="search-container auto">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("任务名")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.name" clearable/>
                    </div>
                </li>
                <li class="search-button">
                    <Button :loading="loadIng > 0" type="primary" icon="ios-search" @click="getLists">{{$L('搜索')}}</Button>
                </li>
            </ul>
        </div>
        <Table :columns="columns" :data="list" :loading="loadIng > 0" :no-data-text="$L(noText)"></Table>
        <Page
            class="page-container"
            :total="total"
            :current="page"
            :pageSize="pageSize"
            :disabled="loadIng > 0"
            :simple="windowMax768"
            showTotal
            @on-change="setPage"
            @on-page-size-change="setPageSize"/>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "TaskArchived",
    props: {
        projectId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            loadIng: 0,

            keys: {},

            columns: [],
            list: [],

            page: 1,
            pageSize: 20,
            total: 0,
            noText: ''
        }
    },
    mounted() {

    },
    computed: {
        ...mapState(['windowMax768'])
    },
    watch: {
        projectId: {
            handler() {
                this.getLists();
            },
            immediate: true
        }
    },
    methods: {
        initLanguage() {
            this.columns = [
                {
                    title: this.$L('ID'),
                    minWidth: 50,
                    maxWidth: 70,
                    key: 'id',
                },
                {
                    title: this.$L('任务名称'),
                    key: 'name',
                    minWidth: 200,
                    render: (h, {row}) => {
                        return h('AutoTip', row.name);
                    }
                },
                {
                    title: this.$L('完成时间'),
                    key: 'complete_at',
                    width: 168,
                    render: (h, {row}) => {
                        return h('div', {
                            style: {
                                color: row.complete_at ? '' : '#f00'
                            }
                        }, row.complete_at || this.$L('未完成'));
                    }
                },
                {
                    title: this.$L('归档时间'),
                    key: 'archived_at',
                    width: 168,
                },
                {
                    title: this.$L('归档会员'),
                    key: 'archived_userid',
                    minWidth: 100,
                    render: (h, {row}) => {
                        if (!row.archived_userid) {
                            return h('Tag', this.$L('系统自动'));
                        }
                        return h('UserAvatar', {
                            props: {
                                userid: row.archived_userid,
                                size: 24,
                                showName: true
                            }
                        });
                    }
                },
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, params) => {
                        const recoveryNode = h('Poptip', {
                            props: {
                                title: this.$L('你确定要还原归档吗？'),
                                confirm: true,
                                transfer: true,
                                placement: 'left',
                            },
                            style: {
                                fontSize: '13px',
                                cursor: 'pointer',
                                color: '#8bcf70',
                            },
                            on: {
                                'on-ok': () => {
                                    this.recovery(params.row);
                                }
                            },
                        }, this.$L('还原'));
                        const deleteNode = h('Poptip', {
                            props: {
                                title: this.$L('你确定要删除任务吗？'),
                                confirm: true,
                                transfer: true,
                                placement: 'left',
                            },
                            style: {
                                marginLeft: '6px',
                                fontSize: '13px',
                                cursor: 'pointer',
                                color: '#f00',
                            },
                            on: {
                                'on-ok': () => {
                                    this.delete(params.row);
                                }
                            },
                        }, this.$L('删除'));
                        return h('TableAction', {
                            props: {
                                column: params.column
                            }
                        }, [
                            recoveryNode,
                            deleteNode,
                        ]);
                    }
                }
            ]
        },

        refresh() {
            this.keys = {};
            this.getLists()
        },

        getLists() {
            if (!this.projectId) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/lists',
                data: {
                    keys: this.keys,
                    project_id: this.projectId,
                    parent_id: -1,
                    archived: 'yes',
                    sorts: {
                        archived_at: 'desc'
                    },
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 20),
                },
            }).then(({data}) => {
                this.loadIng--;
                this.page = data.current_page;
                this.total = data.total;
                this.list = data.data;
                this.noText = '没有相关的数据';
            }).catch(() => {
                this.loadIng--;
                this.noText = '数据加载失败';
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

        recovery(row) {
            this.list = this.list.filter(({id}) => id != row.id);
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/archived',
                data: {
                    task_id: row.id,
                    type: 'recovery'
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.loadIng--;
                this.getLists();
                this.$store.dispatch("openTask", row);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
                this.getLists();
            })
        },

        delete(row) {
            this.list = this.list.filter(({id}) => id != row.id);
            this.loadIng++;
            this.$store.dispatch("removeTask", row.id).then(({msg}) => {
                $A.messageSuccess(msg);
                this.loadIng--;
                this.getLists();
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
                this.getLists();
            });
        }
    }
}
</script>
