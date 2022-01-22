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
                                v-model="reportType"
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
                                v-model="createAt"
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
                            <Button :loading="loadIng > 0" type="primary" icon="ios-search" @click="searchTab">{{$L('搜索')}}</Button>
                            <div slot="content">
                                <Button :loading="loadIng > 0" type="text" @click="getLists">{{$L('刷新')}}</Button>
                            </div>
                        </Tooltip>
                    </li>
                </ul>
            </div>
            <div class="expand-button-group">
                <Button type="primary" icon="md-add" @click="addReport">{{ $L("新增报告") }}</Button>
            </div>
        </div>

        <Table
            class="tableFill report-row-content"
            ref="tableRef"
            :columns="columns" :data="lists"
            :loading="loadIng > 0"
            :no-data-text="$L(noDataText)"
            stripe/>
        <Page
            class="page-box report-row-foot"
            :total="listTotal"
            :current="listPage"
            :disabled="loadIng > 0"
            @on-change="setPage"
            @on-page-size-change="setPageSize"
            :page-size-opts="[10,20,30,50,100]"
            placement="top"
            show-elevator
            show-sizer
            show-total
            transfer/>
    </div>
</template>

<script>
export default {
    name: "ReportMy",
    data() {
        return {
            loadIng: 0,
            columns: [],
            lists: [],
            listPage: 1,
            listTotal: 0,
            listPageSize: 10,
            noDataText: "",
            createAt: [],
            reportType: '',
            reportTypeList: [],
        }
    },
    mounted() {
        this.getLists();
    },
    methods: {
        initLanguage() {
            this.noDataText = this.noDataText || "数据加载中.....";
            this.columns = [{
                title: this.$L("名称"),
                key: 'title',
                sortable: true,
                minWidth: 120,
            }, {
                title: this.$L("类型"),
                key: 'type',
                align: 'center',
                sortable: true,
                maxWidth: 80,
            }, {
                title: this.$L("汇报时间"),
                key: 'created_at',
                align: 'center',
                sortable: true,
                maxWidth: 180,
            }, {
                title: this.$L("操作"),
                align: 'center',
                width: 100,
                minWidth: 100,
                render: (h, {column, row}) => {
                    if (!row.id) {
                        return null;
                    }
                    const vNodes = [
                        h('ETooltip', {
                            props: {content: this.$L('编辑'), transfer: true, delay: 600}
                        }, [h('Icon', {
                            props: {type: 'md-create', size: 16},
                            style: {margin: '0 3px', cursor: 'pointer'},
                            on: {
                                click: () => {
                                    this.$emit("on-edit", row.id);
                                }
                            }
                        })]),
                        h('ETooltip', {
                            props: {content: this.$L('查看'), transfer: true, delay: 600},
                            style: {position: 'relative', marginLeft: '6px'},
                        }, [h('Icon', {
                            props: {type: 'md-eye', size: 16},
                            style: {margin: '0 3px', cursor: 'pointer'},
                            on: {
                                click: () => {
                                    this.$emit("on-view", row);
                                }
                            }
                        })]),
                    ];
                    return h('TableAction', {
                        props: {
                            column
                        }
                    }, vNodes);
                },
            }];
            this.reportTypeList = [
                {value: "", label: this.$L('全部')},
                {value: "weekly", label: this.$L('周报')},
                {value: "daily", label: this.$L('日报')},
            ]
        },

        getLists() {
            this.loadIng = 1;
            this.$store.dispatch("call", {
                url: 'report/my',
                data: {
                    page: this.listPage,
                    pagesize: this.listPageSize,
                    created_at: this.createAt,
                    type: this.reportType
                },
            }).then(({data, msg}) => {
                // data 结果数据
                this.lists = data.data;
                this.listTotal = data.total;
                if (this.lists.length <= 0) {
                    this.noDataText = this.$L("无数据");
                }
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            }).finally(() => {
                this.loadIng = 0;
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

        searchTab() {
            this.getLists();
        },

        addReport() {
            this.$emit("on-edit", 0);
        }
    }
}
</script>

<style scoped>

</style>
