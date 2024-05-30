<template>
    <div class="project-management">
        <div class="management-title">
            {{ $L('举报管理') }}
            <div class="title-icon">
                <Loading v-if="loadIng > 0" />
            </div>
        </div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{ $L("举报类型") }}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.type" :placeholder="$L('全部')">
                            <Option value=" ">{{ $L('全部') }}</Option>
                            <Option v-for="(item, index) in typeList" :key="index" :value="item.id">{{ $L(item.label) }}
                            </Option>
                        </Select>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{ $L("举报状态") }}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.status" :placeholder="$L('全部')">
                            <Option value=" ">{{ $L('全部') }}</Option>
                            <Option :value="0">{{ $L('待处理') }}</Option>
                            <Option :value="1">{{ $L('已处理') }}</Option>
                        </Select>
                    </div>
                </li>
                <li class="search-button">
                    <Tooltip theme="light" placement="right" transfer-class-name="search-button-clear" transfer>
                        <Button :loading="loadIng > 0" type="primary" icon="ios-search"
                            @click="onSearch">{{ $L('搜索') }}</Button>
                        <div slot="content">
                            <Button v-if="keyIs" type="text" @click="keyIs = false">{{ $L('取消筛选') }}</Button>
                            <Button v-else :loading="loadIng > 0" type="text" @click="getLists">{{ $L('刷新') }}</Button>
                        </div>
                    </Tooltip>
                </li>
            </ul>
        </div>
        <div class="table-page-box">
            <Table :columns="columns" :data="list" :loading="loadIng > 0" :no-data-text="$L(noText)" stripe />
            <Page :total="total" :current="page" :page-size="pageSize" :disabled="loadIng > 0" :simple="windowPortrait"
                :page-size-opts="[10, 20, 30, 50, 100]" show-elevator show-sizer show-total @on-change="setPage"
                @on-page-size-change="setPageSize" />
        </div>
    </div>
</template>

<script>
export default {
    name: "ComplaintManagement",
    data() {
        const typeList = [
            { id: 10, label: "诈骗诱导转账" },
            { id: 20, label: "引流下载其他APP付费" },
            { id: 30, label: "敲诈勒索" },
            { id: 40, label: "照片与本人不一致" },
            { id: 50, label: "色情低俗" },
            { id: 60, label: "频繁广告骚扰" },
            { id: 70, label: "其他问题" }
        ];

        return {
            loadIng: 0,

            keys: {},
            keyIs: false,

            typeList: typeList,

            columns: [
                {
                    title: 'ID',
                    key: 'id',
                    width: 80,
                    render: (h, { row, column }) => {
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
                    title: this.$L('举报类型'),
                    key: 'type',
                    minWidth: 120,
                    render: (h, { row }) => {
                        const label = this.$L(typeList.find(h => h.id == row.type).label)
                        return h('div', {
                            style: {
                                'overflow': 'hidden',
                                'text-overflow': 'ellipsis',
                                'white-space': 'nowrap'
                            },
                            on: {
                                click: () => {
                                    $A.modalInfo({
                                        language: false,
                                        title: this.$L('举报类型'),
                                        content: label
                                    })
                                }
                            }
                        }, label)
                    }
                },
                {
                    title: this.$L('状态'),
                    key: 'status',
                    minWidth: 80,
                    render: (h, { row }) => {
                        let  text = row.status == 0 ? '未处理': '已处理';
                        return h('div', {
                            style: {
                                color: row.status == 0 ? '#f00' : 'inherit',
                            }
                        }, [h('AutoTip', this.$L(text))])
                    }
                },
                {
                    title: this.$L('举报原因'),
                    minWidth: 150,
                    render: (h, { row }) => {
                        return h('div', {
                            style: {
                                'overflow': 'hidden',
                                'text-overflow': 'ellipsis',
                                'white-space': 'nowrap'
                            },
                            on: {
                                click: () => {
                                    $A.modalInfo({
                                        language: false,
                                        title: this.$L('举报原因'),
                                        content: row.reason
                                    })
                                }
                            }
                        }, row.reason)
                    }
                },
                {
                    title: this.$L('举报图'),
                    minWidth: 85,
                    render: (h, { row }) => {
                        const list = JSON.parse(row.imgs)?.map(path => {
                            return {
                                src: $A.apiUrl("../" + path),
                            }
                        });
                        if (list.length === 0) {
                            return h('div', '-')
                        }
                        return h('div', {
                            style: {
                                color: '#1890ff',
                            },
                            on: {
                                click: () => {
                                    this.$store.dispatch("previewImage", { index: 0, list })
                                }
                            }
                        }, [h('AutoTip', this.$L('点击查看'))])
                    }
                },
                {
                    title: this.$L('举报人'),
                    minWidth: 100,
                    render: (h, { row }) => {
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
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, params) => {
                        const vNode = [
                            params.row.status == 0 && h('Poptip', {
                                props: {
                                    title: this.$L('你确定要处理吗？'),
                                    confirm: true,
                                    transfer: true,
                                    placement: 'left',
                                    okText: this.$L('确定'),
                                    cancelText: this.$L('取消'),
                                },
                                style: {
                                    fontSize: '13px',
                                    cursor: 'pointer',
                                    color: '#84C56A',
                                },
                                on: {
                                    'on-ok': () => {
                                        this.handle(params.row);
                                    }
                                },
                            }, this.$L('处理')),
                            h('Poptip', {
                                props: {
                                    title: this.$L('你确定要删除吗？'),
                                    confirm: true,
                                    transfer: true,
                                    placement: 'left',
                                    okText: this.$L('确定'),
                                    cancelText: this.$L('取消'),
                                },
                                style: {
                                    marginLeft: params.row.status == 0 ? '8px' : '0',
                                    fontSize: '13px',
                                    cursor: 'pointer',
                                    color: '#f00',
                                },
                                on: {
                                    'on-ok': () => {
                                        this.delete(params.row);
                                    }
                                },
                            }, this.$L('删除'))
                        ];
                        return h('TableAction', {
                            props: {
                                column: params.column
                            }
                        }, vNode);
                    },
                }
            ],
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
            this.page = 1;
            this.getLists();
        },

        getLists() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'complaint/lists',
                data: {
                    type: this.keys.type,
                    status: this.keys.status,
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 10),
                },
            }).then(({ data }) => {
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

        handle(row) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'complaint/action',
                data: {
                    id: row.id,
                    type: 'handle'
                },
            }).then(() => {
                this.getLists();
            }).catch(({ msg }) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            })
        },

        delete(row) {
            this.list = this.list.filter(({ id }) => id != row.id);
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'complaint/action',
                data: {
                    id: row.id,
                    type: 'delete'
                },
            }).then(() => {
                this.getLists();
            }).catch(({ msg }) => {
                $A.modalError(msg);
                this.getLists();
            }).finally(_ => {
                this.loadIng--;
            })
        }
    }
}
</script>
