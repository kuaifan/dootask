<template>
    <div class="report-list-wrap">
        <div class="search-expand">
            <div class="search-container lr">
                <ul>
                    <li>
                        <div class="search-label">
                            {{ $L("汇报类型") }}
                        </div>
                        <div class="search-content">
                            <Select
                                v-model="keys.type"
                                :placeholder="$L('全部')">
                                <Option v-for="item in reportTypeList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                            </Select>
                        </div>
                    </li>
                    <li>
                        <div class="search-label">
                            {{ $L("汇报时间") }}
                        </div>
                        <div class="search-content">
                            <DatePicker
                                v-model="keys.created_at"
                                type="daterange"
                                split-panels
                                :placeholder="$L('请选择时间')"/>
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
            <div class="expand-button-group">
                <Button type="primary" icon="md-add" @click="addReport">{{ $L("新增报告") }}</Button>
            </div>
        </div>

        <div class="table-page-box">
            <Table
                :columns="columns"
                :data="lists"
                :loading="loadIng > 0"
                :no-data-text="$L(noDataText)"
                stripe/>
            <Page
                :total="listTotal"
                :current="listPage"
                :page-size="listPageSize"
                :disabled="loadIng > 0"
                :simple="windowPortrait"
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
export default {
    name: "ReportMy",
    data() {
        return {
            loadIng: 0,
            columns: [{
                title: this.$L("名称"),
                key: 'title',
                minWidth: 180,
                render: (h, {row}) => {
                    return h('AutoTip', row.title);
                }
            }, {
                title: this.$L("类型"),
                key: 'type',
                align: 'center',
                width: 90,
            }, {
                title: this.$L("汇报时间"),
                key: 'created_at',
                align: 'center',
                width: 180,
            }, {
                title: this.$L("汇报对象"),
                key: 'receives',
                align: 'center',
                width: 90,
                render: (h, {row}) => {
                    if (row.receives.length === 0) {
                        return h('div', '-')
                    }
                    const array = [];
                    if (row.receives.length <= 2) {
                        row.receives.some(userid => {
                            array.push(h('UserAvatar', {
                                props: {
                                    size: 22,
                                    userid: userid,
                                }
                            }))
                        })
                    } else {
                        array.push(h('UserAvatar', {
                            props: {
                                size: 22,
                                userid: row.receives[0],
                            }
                        }))
                        array.push(h('div', {
                            class: "more-avatar"
                        }, `+${row.receives.length - 1}`))
                    }
                    return h('div', {
                        class: "report-table-avatar"
                    }, array)
                }
            }, {
                title: this.$L("操作"),
                align: 'center',
                width: 100,
                minWidth: 100,
                render: (h, {column, row}) => {
                    if (!row.id) {
                        return null;
                    }
                    return h('TableAction', {
                        props: {
                            column,
                            menu: [
                                {
                                    icon: "md-create",
                                    action: "edit",
                                },
                                {
                                    icon: "md-eye",
                                    action: "view",
                                }
                            ]
                        },
                        on: {
                            action: (name) => {
                                if (name === 'edit') {
                                    this.$emit("on-edit", row.id);
                                } else if (name === 'view') {
                                    this.$emit("on-view", row);
                                }
                            }
                        }
                    });
                },
            }],
            lists: [],
            listPage: 1,
            listTotal: 0,
            listPageSize: 20,
            noDataText: "数据加载中.....",

            keys: {},
            keyIs: false,

            reportTypeList: [
                {value: "", label: this.$L('全部')},
                {value: "weekly", label: this.$L('周报')},
                {value: "daily", label: this.$L('日报')},
            ],
        }
    },
    mounted() {
        this.getLists();
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
        onSearch() {
            this.listPage = 1;
            this.getLists();
        },

        getLists() {
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            this.$store.dispatch("call", {
                url: 'report/my',
                data: {
                    keys: this.keys,
                    page: Math.max(this.listPage, 1),
                    pagesize: Math.max($A.runNum(this.listPageSize), 10),
                },
            }).then(({data}) => {
                // data 结果数据
                this.lists = data.data;
                this.listTotal = data.total;
                this.noDataText = "没有相关的数据";
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
                this.noDataText = '数据加载失败';
            }).finally(() => {
                this.loadIng--;
            });
        },

        setPage(page) {
            this.listPage = page;
            this.getLists();
        },

        setPageSize(size) {
            if (Math.max($A.runNum(this.listPageSize), 10) !== size) {
                this.listPageSize = size;
                this.getLists();
            }
        },

        addReport() {
            this.$emit("on-edit", 0);
        }
    }
}
</script>
