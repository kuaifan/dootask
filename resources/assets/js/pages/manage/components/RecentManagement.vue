<template>
    <div class="recent-management">
        <div class="management-title">
            {{$L('最近打开')}}
            <div class="title-icon">
                <Loading v-if="loading > 0"/>
            </div>
        </div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">{{$L('类型')}}</div>
                    <div class="search-content">
                        <Select v-model="filters.type" clearable :placeholder="$L('全部类型')" @on-change="handleTypeChange">
                            <Option v-for="item in typeOptions" :key="item.value" :value="item.value">{{$L(item.label)}}</Option>
                        </Select>
                    </div>
                </li>
                <li class="search-button">
                    <Button type="primary" :loading="loading > 0" @click="refreshList">{{$L('刷新')}}</Button>
                </li>
            </ul>
        </div>
        <div class="table-page-box">
            <Table
                :columns="columns"
                :data="records"
                :loading="loading > 0"
                :no-data-text="$L(noDataText)"
                stripe/>
            <Page
                :total="total"
                :current="page"
                :page-size="pageSize"
                :page-size-opts="[10,20,30,50,100]"
                :simple="windowPortrait"
                :disabled="loading > 0"
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
import {openFileInClient} from "../../../utils/file";

export default {
    name: "RecentManagement",
    data() {
        return {
            loading: 0,
            records: [],
            total: 0,
            page: 1,
            pageSize: 20,
            filters: {
                type: ''
            },
            noDataText: '暂无打开记录'
        }
    },
    computed: {
        ...mapState(['windowPortrait']),
        typeMap() {
            return {
                task: {label: '任务', color: 'success'},
                file: {label: '文件库', color: 'warning'},
                task_file: {label: '任务文件', color: 'primary'},
                message_file: {label: '聊天文件', color: '#f87cbd'}
            }
        },
        typeOptions() {
            return [
                {value: '', label: '全部类型'},
                {value: 'task', label: this.typeMap.task.label},
                {value: 'file', label: this.typeMap.file.label},
                {value: 'task_file', label: this.typeMap.task_file.label},
                {value: 'message_file', label: this.typeMap.message_file.label},
            ]
        },
        columns() {
            return [
                {
                    title: this.$L('类型'),
                    key: 'type',
                    width: 120,
                    render: (h, {row}) => {
                        const info = this.getTypeInfo(row.type);
                        return h('Tag', {
                            class: 'recent-type-tag',
                            props: {
                                color: info.color || 'primary'
                            }
                        }, this.$L(info.label || row.type));
                    }
                },
                {
                    title: this.$L('名称'),
                    key: 'name',
                    minWidth: 200,
                    render: (h, {row}) => {
                        const text = row.name || this.$L('未命名');
                        return h('div', {
                            class: 'recent-name',
                            on: {
                                click: () => this.openItem(row)
                            }
                        }, [
                            h('AutoTip', text)
                        ]);
                    }
                },
                {
                    title: this.$L('来源'),
                    minWidth: 220,
                    render: (h, {row}) => {
                        return h('AutoTip', this.getSourceText(row));
                    }
                },
                {
                    title: this.$L('最近访问时间'),
                    key: 'browsed_at',
                    width: 168,
                },
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 120,
                    render: (h, params) => {
                        const actions = [
                            h('Poptip', {
                                props: {
                                    title: this.$L(`确定要删除记录"${params.row.name || this.$L('未命名')}"吗？`),
                                    confirm: true,
                                    transfer: true,
                                    placement: 'left',
                                    okText: this.$L('确定'),
                                    cancelText: this.$L('取消'),
                                },
                                style: {
                                    fontSize: '13px',
                                    cursor: 'pointer',
                                    color: '#f00',
                                },
                                on: {
                                    'on-ok': () => this.removeItem(params.row)
                                },
                            }, this.$L('删除'))
                        ];
                        return h('TableAction', {
                            props: {
                                column: params.column
                            }
                        }, actions);
                    }
                }
            ]
        }
    },
    mounted() {
        this.getLists();
    },
    methods: {
        getTypeInfo(type) {
            return this.typeMap[type] || {label: type, color: 'default'};
        },
        getSourceText(row) {
            switch (row.type) {
                case 'task': {
                    const project = row.project_name ? `${this.$L('项目')}: ${row.project_name}` : this.$L('项目');
                    const status = this.getTaskStatus(row);
                    return status ? `${project} | ${status}` : project;
                }
                case 'file':
                    return this.$L('文件库');
                case 'task_file': {
                    const parts = [];
                    if (row.project_name) {
                        parts.push(`${this.$L('项目')}: ${row.project_name}`);
                    }
                    if (row.task_name) {
                        parts.push(`${this.$L('任务')}: ${row.task_name}`);
                    }
                    return parts.length > 0 ? parts.join(' | ') : this.$L('任务文件');
                }
                case 'message_file':
                    if (row.dialog_name) {
                        return `${this.$L('聊天')}: ${row.dialog_name}`;
                    }
                    return this.$L('聊天文件');
            }
            return this.$L('未知');
        },
        getTaskStatus(row) {
            if (row.flow_item_name) {
                return row.flow_item_name;
            }
            if (row.complete_at) {
                return this.$L('已完成');
            }
            return this.$L('进行中');
        },
        getLists(page = this.page) {
            this.loading++;
            const params = {
                page,
                page_size: this.pageSize,
            };
            if (this.filters.type) {
                params.type = this.filters.type;
            }
            this.$store.dispatch('getRecentBrowseHistory', params).then(({data}) => {
                if ($A.isJson(data)) {
                    this.records = data.list || [];
                    this.total = data.total || 0;
                    this.page = data.page || page;
                    this.pageSize = data.page_size || this.pageSize;
                } else {
                    this.records = [];
                    this.total = 0;
                }
            }).catch(({msg}) => {
                if (msg) {
                    $A.modalError(msg);
                }
            }).finally(() => {
                this.loading--;
            });
        },
        refreshList() {
            this.getLists(1);
        },
        handleTypeChange() {
            this.page = 1;
            this.getLists(1);
        },
        setPage(page) {
            this.page = page;
            this.getLists(page);
        },
        setPageSize(size) {
            this.pageSize = size;
            this.getLists(1);
        },
        openItem(row) {
            switch (row.type) {
                case 'task':
                    this.$store.dispatch('openTask', row);
                    break;
                case 'file':
                    openFileInClient(this, row);
                    break;
                case 'task_file':
                    openFileInClient(this, row, {
                        path: `/single/file/task/${row.id}`,
                        windowName: `file-task-${row.id}`,
                        title: row.name,
                    });
                    break;
                case 'message_file':
                    openFileInClient(this, row, {
                        path: `/single/file/msg/${row.id}`,
                        windowName: `file-msg-${row.id}`,
                        title: row.name,
                    });
                    break;
            }
        },
        removeItem(row) {
            if (!row.record_id) {
                return;
            }

            const targetPage = this.records.length === 1 && this.page > 1 ? this.page - 1 : this.page;
            this.loading++;
            this.$store.dispatch('removeRecentBrowseRecord', row.record_id).then(({msg}) => {
                $A.messageSuccess(msg || this.$L('删除成功'));
                this.page = targetPage;
                this.getLists(targetPage);
            }).catch(({msg}) => {
                if (msg) {
                    $A.modalError(msg);
                }
            }).finally(() => {
                this.loading--;
            });
        }
    }
}
</script>
