<template>
    <div class="project-management">
        <div class="management-title">{{$L('所有项目')}}</div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("项目名")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.name" clearable/>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("项目状态")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.status">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="unarchived">{{$L('未归档')}}</Option>
                            <Option value="archived">{{$L('已归档')}}</Option>
                        </Select>
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
    name: "ProjectManagement",
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
                    minWidth: 100,
                    render: (h, {row}) => {
                        const arr = [h('AutoTip', row.name)];
                        if (row.archived_at) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'error'
                                }
                            }, this.$L('已归档')))
                        }
                        return h('div', {
                            class: 'project-name'
                        }, arr)
                    }
                },
                {
                    title: this.$L('项目进度'),
                    minWidth: 100,
                    render: (h, {row}) => {
                        const arr = [
                            h('AutoTip', row.task_complete + '/' + row.task_num),
                            h('Progress', {
                                props: {
                                    percent: row.task_percent,
                                    strokeWidth: 5
                                }
                            }),
                        ]
                        return h('div', {
                            class: 'project-percent'
                        }, arr)
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
                    title: this.$L('创建人'),
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('UserAvatar', {
                            props: {
                                showName: true,
                                size: 22,
                                userid: row.userid,
                            }
                        })
                    }
                },
                {
                    title: this.$L('创建时间'),
                    key: 'created_at',
                    width: 168,
                },
            ]
        },
        getLists() {
            let archived = 'all';
            if (this.keys.status == 'archived') {
                archived = 'yes';
            } else if (this.keys.status == 'unarchived') {
                archived = 'no';
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/lists',
                data: {
                    keys: this.keys,
                    all: 1,
                    archived,
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
    }
}
</script>
