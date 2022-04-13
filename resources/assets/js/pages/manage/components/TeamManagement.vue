<template>
    <div class="team-management">
        <div class="management-title">
            {{$L('团队管理')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
            </div>
        </div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("关键词")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.key" :placeholder="$L('邮箱、昵称、职位')" clearable/>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("身份")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.identity" :placeholder="$L('全部')">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="admin">{{$L('管理员')}}</Option>
                            <Option value="noadmin">{{$L('非管理员')}}</Option>
                        </Select>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("在职状态")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.disable" :placeholder="$L('在职')">
                            <Option value="">{{$L('在职')}}</Option>
                            <Option value="yes">{{$L('离职')}}</Option>
                            <Option value="all">{{$L('全部')}}</Option>
                        </Select>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("邮箱认证")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.email_verity" :placeholder="$L('全部')">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="yes">{{$L('已邮箱认证')}}</Option>
                            <Option value="no">{{$L('未邮箱认证')}}</Option>
                        </Select>
                    </div>
                </li>
                <li class="search-button">
                    <Tooltip
                        theme="light"
                        placement="bottom"
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

        <!--操作离职-->
        <Modal
            v-model="disableShow"
            class="operate-left"
            :title="$L('操作离职')">
            <Form :model="disableData" label-width="auto" @submit.native.prevent>
                <Alert type="error" style="margin-bottom:18px">{{$L(`正在进行帐号【ID:${disableData.userid}，${disableData.nickname}】离职操作。`)}}</Alert>
                <FormItem :label="$L('离职时间')">
                    <DatePicker
                        v-model="disableData.disable_time"
                        :editable="false"
                        :placeholder="$L('选择离职时间')"
                        style="width:100%"
                        format="yyyy/MM/dd HH:mm"
                        type="datetime"/>
                </FormItem>
                <FormItem :label="$L('交接人')">
                    <UserInput v-model="disableData.transfer_userid" :disabled-choice="[disableData.userid]" :multiple-max="1" :placeholder="$L('选择交接人')"/>
                    <div class="form-tip">{{ $L(`${disableData.nickname} 负责的项目、任务和文件将移交给交接人`) }}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="disableShow=false">{{$L('取消')}}</Button>
                <Poptip
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    @on-ok="operationUser(disableData)"
                    transfer>
                    <div slot="title">
                        <p>{{$L('注意：离职操作不可逆！')}}</p>
                    </div>
                    <Button type="primary" :loading="disableLoading > 0">{{$L('确定离职')}}</Button>
                </Poptip>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import UserInput from "../../../components/UserInput";

export default {
    name: "TeamManagement",
    components: {UserInput},
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
            noText: '',

            disableShow: false,
            disableLoading: 0,
            disableData: {},
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
                    key: 'userid',
                    width: 80,
                    render: (h, {row, column}) => {
                        return h('TableAction', {
                            props: {
                                column: column,
                                align: 'left'
                            }
                        }, [
                            h("div", row.userid),
                        ]);
                    }
                },
                {
                    title: this.$L('邮箱'),
                    key: 'email',
                    minWidth: 100,
                    render: (h, {row}) => {
                        const arr = [h('AutoTip', row.email)];
                        const {email_verity, identity, disable_at} = row;
                        if (email_verity) {
                            arr.push(h('Icon', {
                                props: {
                                    type: 'md-mail'
                                }
                            }))
                        }
                        if (identity.includes("admin")) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'warning'
                                }
                            }, this.$L('管理员')))
                        }
                        if (identity.includes("disable")) {
                            arr.push(h('Tooltip', {
                                props: {
                                    content: this.$L('离职时间') + ': ' + disable_at,
                                },
                            }, [
                                h('Tag', {
                                    props: {
                                        color: 'error'
                                    }
                                }, this.$L('离职'))
                            ]))
                        }
                        return h('div', {
                            class: 'team-email'
                        }, arr)
                    }
                },
                {
                    title: this.$L('昵称'),
                    key: 'nickname',
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('QuickEdit', {
                            props: {
                                value: row.nickname_original,
                            },
                            on: {
                                'on-update': (val, cb) => {
                                    this.operationUser({
                                        userid: row.userid,
                                        nickname: val
                                    }).then(cb);
                                }
                            }
                        }, [
                            h('AutoTip', row.nickname_original || '-')
                        ]);
                    }
                },
                {
                    title: this.$L('职位/职称'),
                    key: 'profession',
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('QuickEdit', {
                            props: {
                                value: row.profession,
                            },
                            on: {
                                'on-update': (val, cb) => {
                                    this.operationUser({
                                        userid: row.userid,
                                        profession: val
                                    }).then(cb);
                                }
                            }
                        }, [
                            h('AutoTip', row.profession || '-')
                        ]);
                    },
                },
                {
                    title: this.$L('最后在线'),
                    key: 'line_at',
                    width: 168,
                },
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, params) => {
                        const identity = params.row.identity;
                        const dropdownItems = [];
                        if (identity.includes('admin')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'clearadmin',
                                },
                            }, [h('div', this.$L('取消管理员'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'setadmin',
                                },
                            }, [h('div', this.$L('设为管理员'))]));
                        }

                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'password',
                            },
                        }, [h('div', this.$L('修改密码'))]))

                        if (identity.includes('disable')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'cleardisable',
                                },
                                style: {
                                    color: '#f90'
                                }
                            }, [h('div', this.$L('恢复帐号（已离职）'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'setdisable',
                                },
                                style: {
                                    color: '#f90'
                                }
                            }, [h('div', this.$L('操作离职'))]));
                        }

                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'delete',
                            },
                            style: {
                                color: 'red'
                            }
                        }, [h('div', this.$L('删除'))]))

                        const dropdownMenu = h('EDropdown', {
                            props: {
                                size: 'small',
                                trigger: 'click',
                            },
                            on: {
                                command: (name) => {
                                    this.dropUser(name, params.row)
                                }
                            }
                        }, [
                            h('Button', {
                                props: {
                                    type: 'primary',
                                    size: 'small'
                                },
                                style: {
                                    fontSize: '12px',
                                },
                            }, this.$L('操作')),
                            h('EDropdownMenu', {slot: 'dropdown'}, [dropdownItems]),
                        ])
                        return h('TableAction', {
                            props: {
                                column: params.column
                            }
                        }, [
                            dropdownMenu,
                        ]);
                    }
                }
            ]
        },

        onSearch() {
            this.page = 1;
            this.getLists();
        },

        getLists() {
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            this.$store.dispatch("call", {
                url: 'users/lists',
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

        dropUser(name, row) {
            switch (name) {
                case 'password':
                    $A.modalInput({
                        title: "修改密码",
                        placeholder: "请输入新的密码",
                        onOk: (value) => {
                            if (value) {
                                this.operationUser({
                                    userid: row.userid,
                                    password: value
                                });
                            }
                            return true;
                        }
                    });
                    break;

                case 'setdisable':
                    this.disableData = {
                        type: 'setdisable',
                        userid: row.userid,
                        nickname: row.nickname,
                    };
                    this.disableShow = true;
                    break;

                case 'cleardisable':
                    $A.modalConfirm({
                        content: `你确定恢复已离职帐号【ID:${row.userid}，${row.nickname}】吗？（注：此操作仅恢复帐号状态，无法恢复操作离职时移交的数据）`,
                        onOk: () => {
                            this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'delete':
                    $A.modalConfirm({
                        content: `你确定要删除帐号【ID:${row.userid}，${row.nickname}】吗？`,
                        onOk: () => {
                            this.operationUser({
                                userid: row.userid,
                                type: name,
                            });
                        }
                    });
                    break;

                default:
                    this.operationUser({
                        userid: row.userid,
                        type: name
                    });
                    break;
            }
        },

        operationUser(data) {
            return new Promise((resolve) => {
                if (data.type == 'setdisable') {
                    this.disableLoading++;
                } else {
                    this.loadIng++;
                }
                this.$store.dispatch("call", {
                    url: 'users/operation',
                    data,
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.getLists();
                    resolve()
                    if (data.type == 'setdisable') {
                        this.disableShow = false;
                    }
                }).catch(({msg}) => {
                    $A.modalError(msg, 301);
                    this.getLists();
                    resolve()
                }).finally(_ => {
                    if (data.type == 'setdisable') {
                        this.disableLoading--;
                    } else {
                        this.loadIng--;
                    }
                })
            })
        }
    }
}
</script>
