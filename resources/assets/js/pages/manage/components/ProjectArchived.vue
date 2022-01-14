<template>
    <div class="project-archived">
        <div class="archived-title">
            {{$L('归档的项目')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
                <Icon v-else type="ios-refresh" @click="refresh"/>
            </div>
        </div>
        <div class="search-container auto">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("项目名")}}
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
        <Table :columns="columns" :data="list" :no-data-text="$L(noText)"></Table>
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
    name: "ProjectArchived",
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
        this.getLists();
    },
    computed: {
        ...mapState(['windowMax768'])
    },
    methods: {
        initLanguage() {
            this.columns = [
                {
                    title: this.$L('ID'),
                    key: 'id',
                    minWidth: 50,
                    maxWidth: 70,
                },
                {
                    title: this.$L('项目名称'),
                    key: 'name',
                    minWidth: 200,
                    render: (h, {row}) => {
                        return h('AutoTip', row.name);
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
                    minWidth: 80,
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
                    title: this.$L('负责人'),
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('UserAvatar', {
                            props: {
                                showName: true,
                                size: 22,
                                userid: row.owner_userid,
                            }
                        })
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
                                title: this.$L('你确定要删除项目吗？'),
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
                    },
                }
            ]
        },

        refresh() {
            this.keys = [];
            this.getLists();
        },

        getLists() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/lists',
                data: {
                    keys: this.keys,
                    archived: 'yes',
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
                url: 'project/archived',
                data: {
                    project_id: row.id,
                    type: 'recovery'
                },
            }).then(() => {
                this.loadIng--;
                this.getLists();
                this.$store.dispatch("getProjectOne", row.id).catch(() => {});
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
                this.getLists();
            })
        },

        delete(row) {
            this.list = this.list.filter(({id}) => id != row.id);
            this.loadIng++;
            this.$store.dispatch("removeProject", row.id).then(({msg}) => {
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
