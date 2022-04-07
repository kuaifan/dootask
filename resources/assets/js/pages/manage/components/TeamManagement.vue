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
                        <Select v-model="keys.identity" :placeholder="$L('请选择')">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="admin">{{$L('管理员')}}</Option>
                            <Option value="noadmin">{{$L('非管理员')}}</Option>
                            <Option value="disable">{{$L('禁用')}}</Option>
                            <Option value="nodisable">{{$L('非禁用')}}</Option>
                        </Select>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("邮箱认证")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.email_verity" :placeholder="$L('请选择')">
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
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "TeamManagement",
    data() {
        return {
            loadIng: 0,

            keys: {
                identity: 'nodisable'
            },
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
                        const {email_verity, identity} = row;
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
                            arr.push(h('Tag', {
                                props: {
                                    color: 'error'
                                }
                            }, this.$L('禁用')))
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
                        if (identity.includes('disable')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'cleardisable',
                                },
                            }, [h('div', this.$L('取消禁用'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'setdisable',
                                },
                            }, [h('div', this.$L('设为禁用'))]));
                        }
                        dropdownItems.push(...[
                            h('EDropdownItem', {
                                props: {
                                    command: 'password',
                                },
                            }, [h('div', this.$L('修改密码'))]),
                            h('EDropdownItem', {
                                props: {
                                    command: 'delete',
                                },
                                style: {
                                    color: 'red'
                                }
                            }, [h('div', this.$L('删除'))]),
                        ])
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

                case 'delete':
                    $A.modalConfirm({
                        content: '你确定要删除此帐号吗？',
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
                this.loadIng++;
                this.$store.dispatch("call", {
                    url: 'users/operation',
                    data,
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.loadIng--;
                    this.getLists();
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg, 301);
                    this.loadIng--;
                    this.getLists();
                    resolve()
                })
            })
        }
    }
}
</script>
