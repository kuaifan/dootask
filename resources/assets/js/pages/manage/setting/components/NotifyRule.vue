<template>
    <div class="setting-notify-item">
        <div class="search-expand">
            <div class="search-container lr">
                <ul>
                    <li>
                        <div class="search-label">
                            {{$L("关键词")}}
                        </div>
                        <div class="search-content">
                            <Input v-model="keys.name" :placeholder="$L('名称')" clearable/>
                        </div>
                    </li>
                    <li class="search-button">
                        <Tooltip
                            theme="light"
                            placement="right"
                            transfer-class-name="search-button-clear"
                            transfer>
                            <Button :loading="loadIng > 0" type="primary" icon="ios-search" @click="getLists">{{$L('搜索')}}</Button>
                            <div slot="content">
                                <Button v-if="keyIs" type="text" @click="keys={};keyIs=false;setPage(1)">{{$L('取消筛选')}}</Button>
                                <Button v-else :loading="loadIng > 0" type="text" @click="getLists">{{$L('刷新')}}</Button>
                            </div>
                        </Tooltip>
                    </li>
                </ul>
            </div>
            <div class="expand-button-group">
                <Button type="primary" icon="md-add" @click="showAdd(false)">{{$L('添加规则')}}</Button>
            </div>
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

        <!--添加规则-->
        <Modal
            v-model="addShow"
            :title="$L(addData.id > 0 ? '修改规则': '添加规则')"
            :mask-closable="false">
            <Form ref="add" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('规则名称')">
                    <Input type="text" v-model="addData.name" :placeholder="$L('输入规则名称')"></Input>
                </FormItem>
                <FormItem prop="mode" :label="$L('推送方式')">
                    <Select
                        v-model="addData.mode"
                        :placeholder="$L('选择推送类型')"
                        @on-change="generateContent">
                        <Option v-for="item in modeLists" :value="item.mode" :key="item.mode">{{ $L(item.label) }}</Option>
                    </Select>
                </FormItem>
                <FormItem v-if="addData.mode === 'webhook'" prop="webhook_url" :label="$L('推送URL')">
                    <Input type="text" v-model="addData.webhook_url" :placeholder="$L('请输入Webhook地址')"></Input>
                </FormItem>
                <FormItem v-if="eventItems.length > 0" prop="event" :label="$L('触发条件')">
                    <Select
                        v-model="addData.event"
                        :placeholder="$L('选择推送触发条件')"
                        @on-change="generateContent">
                        <Option v-for="item in eventItems" :value="item.event" :key="item.event">{{ $L(item.label) }}</Option>
                    </Select>
                    <div v-if="eventData && eventData.receiver" class="setting-item-notify-tip">{{$L('接收对象')}}: {{$L(eventData.receiver)}}</div>
                </FormItem>
                <FormItem v-if="['taskExpireBefore', 'taskExpireAfter'].includes(addData.event)" prop="expire_hours" :label="$L('时间条件')">
                    <label>{{ $L(addData.event === 'taskExpireAfter' ? '任务超期后' : '任务到期前') }}</label>
                    <InputNumber v-model="addData.expire_hours" :min="0.5" :max="720" :step="0.5"/>
                    <label>{{ $L('小时') }}</label>
                </FormItem>
                <FormItem v-if="modeData && eventData" prop="content" :label="$L('推送内容')">
                    <Input
                        v-model="addData.content"
                        type="textarea"
                        :rows="5"
                        :autosize="{ minRows: 5, maxRows: 20 }"
                        :placeholder="$L('请输入推送内容')">
                    </Input>
                    <div class="setting-item-notify-add-vars">
                        {{$L('支持语法')}}: <span>{{modeData.contentType}}</span>, {{$L('支持变量')}}: <em v-for="v in eventData.vars" v-html="`{${v}}`"></em>
                    </div>
                </FormItem>
                <FormItem prop="status" :label="$L('是否启用')">
                    <RadioGroup v-model="addData.status">
                        <Radio :label="1">{{$L('启用')}}</Radio>
                        <Radio :label="0">{{$L('关闭')}}</Radio>
                    </RadioGroup>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAdd">{{$L(addData.id > 0 ? '修改' : '保存')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "NotifyRule",
    props: {
        eventTypes: {
            type: Array,
            default: () => []
        },
        modeLists: {
            type: Array,
            default: () => []
        },
    },
    data() {
        return {
            loadIng: 0,

            keys: {},
            keyIs: false,

            columns: [
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
                    title: this.$L("规则名称"),
                    key: 'name',
                    minWidth: 100,
                    render: (h, {row}) => {
                        return h('AutoTip', row.name || '-');
                    }
                },
                {
                    title: this.$L("推送方式"),
                    key: 'mode',
                    minWidth: 90,
                    maxWidth: 200,
                    render: (h, {row}) => {
                        const data = this.modeLists.find(({mode}) => mode === row.mode);
                        return h('AutoTip', data ? data.label : row.mode);
                    }
                },
                {
                    title: this.$L("触发条件"),
                    key: 'mode',
                    minWidth: 90,
                    maxWidth: 200,
                    render: (h, {row}) => {
                        const data = this.eventTypes.find(({event}) => event === row.event);
                        let text = data ? data.label : row.event;
                        if (['taskExpireBefore', 'taskExpireAfter'].includes(row.event)) {
                            text += ` (${row.expire_hours}${this.$L('小时')})`
                        }
                        return h('AutoTip', text);
                    }
                },
                {
                    title: this.$L("累计通知"),
                    key: 'total',
                    width: 100,
                },
                {
                    title: this.$L("最后通知"),
                    key: 'last_at',
                    width: 168,
                    render: (h, {row}) => {
                        return h('div', row.last_at || '-');
                    }
                },
                {
                    title: this.$L("启用"),
                    align: 'center',
                    key: 'status',
                    width: 70,
                    render: (h, {row}) => {
                        return h('Icon', {
                            style: {
                                fontSize: '16px',
                                color: row.status ? '#8bcf70' : '#FF7070'
                            },
                            props: {
                                type: row.status ? 'md-checkmark' : 'md-close'
                            }
                        });
                    }
                },
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 90,
                    render: (h, {row, column}) => {
                        let menu = [
                            {
                                title: this.$L('修改'),
                                icon: "md-create",
                                action: "update",
                            },
                            {
                                title: this.$L('删除'),
                                icon: "md-trash",
                                action: "delete",
                            }
                        ];
                        return h('TableAction', {
                            props: {
                                autoWidth: false,
                                column: column,
                                menu,
                            },
                            on: {
                                action: (name) => {
                                    this.actionNotify(name, row)
                                }
                            }
                        });
                    }
                }
            ],
            list: [],

            page: 1,
            pageSize: 20,
            total: 0,
            noText: '',

            addShow: false,
            addData: {
                mode: 'mail',
                name: '',
                event: '',
                content: '',
                webhook_url: '',
                expire_hours: 1,
                status: 1
            },
            addRule: {},
        }
    },

    mounted() {
        this.getLists();
    },

    computed: {
        ...mapState(['windowMax768']),

        eventItems() {
            const data = this.modeLists.find(({mode}) => mode === this.addData.mode);
            if (!data) {
                return []
            }
            return $A.cloneJSON(this.eventTypes).filter(event => {
                const tmp = data.events.find(item => {
                    return item.event === event.event || (item.event === 'allTaskEvent' && $A.leftExists(event.event, 'task'))
                })
                if (tmp) {
                    event.receiver = tmp.receiver
                    return true;
                }
                return false;
            });
        },

        eventData() {
            return this.eventItems.find(({event}) => event === this.addData.event) || null;
        },

        modeData() {
            return this.modeLists.find(({mode}) => mode === this.addData.mode) || null;
        },
    },

    methods: {
        generateContent() {
            if (!this.eventData) {
                return;
            }
            let content;
            if (this.addData.mode === 'webhook') {
                content = {};
                this.eventData.vars.some(v => {
                    content[v] = `{${v}}`
                })
                content = JSON.stringify(content, null, 4)
            } else {
                content = this.$L(this.eventData.example)
            }
            this.$set(this.addData, 'content', content)
        },

        getLists() {
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            this.$store.dispatch("call", {
                url: 'system/notify/lists',
                data: {
                    keys: this.keys,
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

        actionNotify(name, row) {
            switch (name) {
                case 'update':
                    this.showAdd(row)
                    break;

                case 'delete':
                    this.deleteNotify(row.id);
                    break;
            }
        },

        deleteNotify(id) {
            $A.modalConfirm({
                title: '删除规则',
                content: '你确定要删除选择的通知规则吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'system/notify/delete',
                        data: {
                            id
                        },
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.getLists();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                        this.getLists();
                    })
                }
            });
        },

        showAdd(info) {
            this.$refs.add.resetFields();
            this.addData = info ? $A.cloneJSON(info) : {
                mode: 'mail',
                name: '',
                event: '',
                content: '',
                webhook_url: '',
                expire_hours: 1,
                status: 1
            };
            this.addShow = true;
        },

        onAdd() {
            this.$refs.add.validate((valid) => {
                if (!valid) {
                    return;
                }
                //
                if (this.loadIng > 0) {
                    return;
                }
                this.loadIng++;
                //
                let data = $A.cloneJSON(this.addData);
                this.$store.dispatch("call", {
                    url: 'system/notify/add',
                    data,
                    method: "post",
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.loadIng--;
                    this.addShow = false;
                    this.setPage(1);
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.loadIng--;
                })
            })
        },
    }
}
</script>
