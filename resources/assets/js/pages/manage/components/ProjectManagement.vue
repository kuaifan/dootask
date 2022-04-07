<template>
    <div class="project-management">
        <div class="management-title">
            {{$L('所有项目')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
            </div>
        </div>
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
                        <Select v-model="keys.status" :placeholder="$L('请选择')">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="unarchived">{{$L('未归档')}}</Option>
                            <Option value="archived">{{$L('已归档')}}</Option>
                        </Select>
                    </div>
                </li>
                <li class="search-button">
                    <Tooltip
                        theme="light"
                        placement="right"
                        transfer-class-name="search-button-clear"
                        transfer>
                        <Button :loading="loadIng > 0" type="primary" icon="ios-search" @click="onSearch">{{$L('搜索')}}</Button>
                        <div slot="content">
                            <Button v-if="keyIs" type="text" @click="keyIs=false">{{$L('取消筛选')}}</Button>
                            <Button v-else :loading="loadIng > 0" type="text" @click="getLists">{{$L('刷新')}}</Button>
                        </div>
                    </Tooltip>
                </li>
            </ul>
        </div>
        <div class="table-page-box">
            <Table
                :columns="columns"
                :data="list"
                :loading="loadIng > 0"
                :no-data-text="$L(noText)"
                stripe/>
            <Page
                :total="total"
                :current="page"
                :page-size="pageSize"
                :disabled="loadIng > 0"
                :simple="windowMax768"
                :page-size-opts="[10,20,30,50,100]"
                show-elevator
                show-sizer
                show-total
                @on-change="setPage"
                @on-page-size-change="setPageSize"/>
        </div>
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
            keyIs: false,

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
    watch: {
        keyIs(v) {
            if (!v) {
                this.keys = {}
                this.setPage(1)
            }
        }
    },
    methods: {
        initLanguage() {
            this.columns = [
                {
                    title: 'ID',
                    key: 'id',
                    width: 80,
                    render: (h, {row, column}) => {
                        return h('TableAction', {
                            props: {
                                column: column,
                                align: 'left'
                            }
                        }, [
                            h("div", row.id),
                        ]);
                    }
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

        onSearch() {
            this.page = 1;
            this.getLists();
        },

        getLists() {
            let archived = 'all';
            if (this.keys.status == 'archived') {
                archived = 'yes';
            } else if (this.keys.status == 'unarchived') {
                archived = 'no';
            }
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            this.$store.dispatch("call", {
                url: 'project/lists',
                data: {
                    keys: this.keys,
                    all: 1,
                    archived,
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 10),
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
