<template>
    <div class="report-list-wrap">
        <Row class="reportmy-row report-row-header">
            <Col  span="2"><p class="reportmy-titles">{{ $L("汇报类型") }}</p></Col>
            <Col span="6">
                <Select
                    v-model="reportType"
                    style="width:95%"
                    :placeholder="this.$L('全部')"
                    @on-change="typePick"
                >
                    <Option v-for="item in reportTypeList" :value="item.value" :key="item.value">{{ item.label }}</Option>
                </Select>
            </Col>
            <Col  span="1"></Col>
            <Col  span="2"><p class="reportmy-titles">{{ $L("汇报时间") }}</p></Col>
            <Col span="6">
                <DatePicker
                    type="daterange"
                    split-panels
                    :placeholder="this.$L('请选择时间')"
                    style="width: 95%;"
                    @on-change="timePick"
                ></DatePicker>
            </Col>
            <Col  span="1"></Col>
            <Col  span="4"><Button type="primary" icon="ios-search" @click="searchTab">{{ $L("搜索") }}</Button></Col>
        </Row>
        <Table class="tableFill report-row-content" ref="tableRef"
               :columns="columns" :data="lists"
               :loading="loadIng > 0"
               :no-data-text="$L(noDataText)" stripe></Table>
        <Page class="page-box report-row-foot" :total="listTotal" :current="listPage" :disabled="loadIng > 0"
              @on-change="setPage" @on-page-size-change="setPageSize" :page-size-opts="[10,20,30,50,100]"
              placement="top" show-elevator show-sizer show-total transfer />
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
            reportType:'',
            reportTypeList:[
                {value:"weekly",label:'周报' },
                {value:"daily",label:'日报' },
            ],
        }
    },
    mounted() {
        this.getLists();
    },
    methods: {
        initLanguage() {
            this.noDataText = this.noDataText || "数据加载中.....";
            this.columns = [{
                "title": this.$L("名称"),
                "key": 'title',
                sortable: true,
                "minWidth": 120,
            }, {
                "title": this.$L("类型"),
                "key": 'type',
                "align": 'center',
                sortable: true,
                "maxWidth": 80,
            }, {
                "title": this.$L("汇报时间"),
                "key": 'created_at',
                "align": 'center',
                sortable: true,
                "maxWidth": 180,
            }, {
                "title": "操作",
                "key": 'action',
                "align": 'right',
                "width": 80,
                render: (h, params) => {
                    if (!params.row.id) {
                        return null;
                    }
                    let arr = [
                        h('ETooltip', {
                            props: { content: this.$L('编辑'), transfer: true, delay: 600 }
                        }, [h('Icon', {
                            props: { type: 'md-create', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.$emit("edit", params.row.id);
                                }
                            }
                        })]),
                        h('ETooltip', {
                            props: { content: this.$L('查看'), transfer: true, delay: 600 },
                            style: { position: 'relative' },
                        }, [h('Icon', {
                            props: { type: 'md-eye', size: 16 },
                            style: { margin: '0 3px', cursor: 'pointer' },
                            on: {
                                click: () => {
                                    this.$emit("detail", params.row);
                                }
                            }
                        })]),
                    ];
                    return h('div', arr);
                },
            }];
        },
        getLists() {
            this.loadIng = 1;
            this.$store.dispatch("call", {
                url: 'report/my',
                data: {
                    page: this.listPage,
                    created_at: this.createAt,
                    type: this.reportType
                },
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                this.lists = data.data;
                this.listTotal = data.total;
                if ( this.lists.length <= 0 ) {
                    this.noDataText = this.$L("无数据");
                }
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            }).finally( () => {
                this.loadIng = 0;
            } );
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
        timePick(e){
            // console.log(e)
            this.createAt = e;
        },
        typePick(e){
            // console.log(e)
        },

        searchTab() {
            this.getLists();
        },
    }
}
</script>

<style scoped>

</style>
