<template>
    <div class="team-management">
        <div class="management-title">{{$L('团队管理')}}</div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("邮箱")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.email" clearable/>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("昵称")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.nickname" clearable/>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("职位/职称")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.position" clearable/>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("身份")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.identity">
                            <Option value="">{{$L('全部')}}</Option>
                            <Option value="admin">{{$L('管理员')}}</Option>
                            <Option value="disable">{{$L('禁用')}}</Option>
                        </Select>
                    </div>
                </li>
                <li class="search-button">
                    <Button :loading="loadIng > 0" type="primary" icon="ios-search" @click="getLists">{{$L('搜索')}}</Button>
                </li>
            </ul>
        </div>
        <Table :columns="columns" :data="list" :no-data-text="$L(noText)"></Table>
        <Page
            class="page-container"
            :total="total"
            :current="page"
            :pageSize="pageSize"
            :disabled="loadIng > 0"
            :simple="windowMax768"
            showTotal
            @on-change="setPage"
            @on-page-size-change="setPageSize"/>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "TeamManagement",
    data() {
        return {
            loadIng: 0,

            keys: {},

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
    methods: {
        initLanguage() {
            this.columns = [
                {
                    title: this.$L('ID'),
                    minWidth: 50,
                    maxWidth: 70,
                    key: 'userid',
                },
                {
                    title: this.$L('邮箱'),
                    key: 'email',
                    minWidth: 100,
                    render: (h, {row}) => {
                        const arr = [h('AutoTip', row.email)];
                        const identity = row.identity;
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
        getLists() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'users/lists',
                data: {
                    keys: this.keys,
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 20),
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
