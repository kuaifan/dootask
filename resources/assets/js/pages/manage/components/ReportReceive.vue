<template>
    <div class="report-list-wrap">
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{ $L("关键词") }}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.key" :placeholder="$L('输入关键词搜索')" clearable/>
                    </div>
                </li>
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

        <div class="table-page-box">
            <Table
                :columns="columns"
                :data="lists"
                :loading="loadIng > 0"
                :no-data-text="$L(noDataText)"
                @on-selection-change="selectChange"
                stripe/>
            <div class="table-attach">
                <!-- 选择执行 -->
                <div class="select-box">
                    <Select v-model="selectAction" :disabled="selectIds.length==0" @on-change="groupSelect=true" :placeholder="$L('请选择')" transfer>
                        <Option value="read">{{ $L('标记已读') }}</Option>
                        <Option value="unread">{{ $L('标记未读') }}</Option>
                    </Select>
                    <Button :loading="loadIng > 0" type="primary" @click="selectClick" :disabled="selectAction=='' || selectIds.length==0">{{$L('执行')}}</Button>
                </div>
                <!-- 分页 -->
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
    </div>
</template>

<script>
export default {
    name: "ReportReceive",
    data() {
        return {
            loadIng: 0,
            columns: [{
                type: 'selection',
                width: 50,
                align: 'right'
            }, {
                title: this.$L("标题"),
                key: 'title',
                sortable: true,
                minWidth: 180,
                render: (h, {row}) => {
                    let arr = []
                    const myUser = row.receives_user.find(({userid}) => userid == this.userId)
                    if (myUser && myUser.pivot.read == 0) {
                        arr.push(
                            h('Tag', {
                                props: {   //传递参数
                                    color: "orange",
                                },
                                style: {
                                    flexShrink: 0,
                                }
                            }, this.$L("未读")),
                            h('AutoTip', row.title)
                        )
                    } else {
                        arr.push(
                            h('AutoTip', row.title)
                        )
                    }
                    return h('div', {
                        style: {
                            display: 'flex',
                            alignItems: 'center',
                        }
                    }, arr)
                }
            }, {
                title: this.$L("类型"),
                key: 'type',
                sortable: true,
                width: 90,
            }, {
                title: this.$L("接收时间"),
                key: 'receive_time',
                align: 'center',
                sortable: true,
                width: 180,
            }, {
                title: this.$L("操作"),
                align: 'center',
                width: 90,
                minWidth: 90,
                render: (h, {column, row}) => {
                    if (!row.id) {
                        return null;
                    }
                    return h('TableAction', {
                        props: {
                            column,
                            menu: [
                                {
                                    icon: "md-eye",
                                    action: "view",
                                }
                            ]
                        },
                        on: {
                            action: (name) => {
                                if (name === 'view') {
                                    this.$emit("on-view", row)
                                    const myUser = row.receives_user.find(({userid}) => userid == this.userId)
                                    if (myUser) {
                                        this.$set(myUser.pivot, 'read', 1)
                                    }
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

            selectIds: [],
            selectAction: '',

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
                url: 'report/receive',
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

        selectChange(items) {
            this.selectIds = items.map(({id}) => id);
        },

        selectClick() {
            if (this.selectIds.length === 0) {
                $A.messageWarning('请选择线路');
                return;
            }
            switch (this.selectAction) {
                case 'read':
                case 'unread':
                    this.readReport(this.selectIds, this.selectAction)
                    break;
                default:
                    $A.messageWarning('请选择执行方式');
                    break;
            }
        },

        readReport(id, action) {
            const label = action === 'read' ? '标记已读' : '标记未读'
            $A.modalConfirm({
                content: `你确定要【${label}】吗？`,
                cancelText: '取消',
                okText: '确定',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'report/mark',
                            data: {
                                id,
                                action,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.getLists();
                            this.$emit("on-read")
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },
    }
}
</script>
