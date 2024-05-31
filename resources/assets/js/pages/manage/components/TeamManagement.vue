<template>
    <div class="team-management">
        <div class="management-title">
            {{$L('团队管理')}}
            <div class="title-zoom" @click="minBox=!minBox">
                <i v-if="minBox" class="taskfont">&#xe7d4;</i>
                <i v-else class="taskfont">&#xe7d3;</i>
            </div>
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
            </div>
        </div>
        <div class="management-box" :class="{'min-box':minBox}">
            <div class="management-department">
                <ul>
                    <li :class="[`level-1`, departmentSelect === 0 ? 'active' : '']" @click="onSelectDepartment(0)">
                        <i class="taskfont department-icon">&#xe766;</i>
                        <div class="department-title">{{$L('默认部门')}}</div>
                        <EDropdown
                            size="medium"
                            trigger="click"
                            @command="onOpDepartment">
                            <i @click.stop="" class="taskfont department-menu">&#xe6e9;</i>
                            <EDropdownMenu slot="dropdown">
                                <EDropdownItem command="add_0">
                                    <div>{{$L('添加子部门')}}</div>
                                </EDropdownItem>
                            </EDropdownMenu>
                        </EDropdown>
                    </li>
                    <li
                        v-for="item in departmentList"
                        :key="item.id"
                        :class="[`level-${item.level}`, departmentSelect === item.id ? 'active' : '']"
                        @click="onSelectDepartment(item.id)">
                        <UserAvatarTip :userid="item.owner_userid" :size="20" class="department-icon">
                            <p><strong>{{$L('部门负责人')}}</strong></p>
                        </UserAvatarTip>
                        <div class="department-title">{{item.name}}</div>
                        <EDropdown
                            size="medium"
                            trigger="click"
                            @command="onOpDepartment">
                            <i @click.stop="" class="taskfont department-menu">&#xe6e9;</i>
                            <EDropdownMenu slot="dropdown">
                                <EDropdownItem v-if="item.level <= 2" :command="`add_${item.id}`">
                                    <div>{{$L('添加子部门')}}</div>
                                </EDropdownItem>
                                <EDropdownItem :command="`edit_${item.id}`">
                                    <div>{{$L('编辑')}}</div>
                                </EDropdownItem>
                                <EDropdownItem :command="`del_${item.id}`">
                                    <div style="color:#f00">{{$L('删除')}}</div>
                                </EDropdownItem>
                            </EDropdownMenu>
                        </EDropdown>
                    </li>
                </ul>
                <div class="department-buttons">
                    <Button type="primary" icon="md-add" @click="onShowDepartment(null)">{{$L('新建部门')}}</Button>
                </div>
            </div>
            <div class="management-user" :style="userStyle">
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
                                    <Option value="temp">{{$L('临时帐号')}}</Option>
                                    <Option value="notemp">{{$L('非临时帐号')}}</Option>
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
                        <li v-if="checkinMac">
                            <div class="search-label">
                                {{$L("MAC地址")}}
                            </div>
                            <div class="search-content">
                                <Input v-model="keys.checkin_mac" :placeholder="$L('MAC地址')" clearable/>
                            </div>
                        </li>
                        <li v-else>
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

        <!--新建部门、修改部门-->
        <Modal
            v-model="departmentShow"
            :title="$L(departmentData.id > 0 ? '修改部门' : '新建部门')"
            :mask-closable="false">
            <Form ref="addProject" :model="departmentData" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('部门名称')">
                    <Input type="text" v-model="departmentData.name" :placeholder="$L('请输入部门名称')"></Input>
                </FormItem>
                <FormItem prop="parent_id" :label="$L('上级部门')">
                    <Select v-model="departmentData.parent_id" :disabled="departmentParentDisabled" :placeholder="$L('请选择上级部门')">
                        <Option :value="0">{{ $L('默认部门') }}</Option>
                        <Option v-for="(item, index) in departmentList" v-if="item.parent_id == 0 && item.id != departmentData.id" :value="item.id" :key="index" :label="item.name">&nbsp;&nbsp;&nbsp;&nbsp;{{ item.name }}</Option>
                    </Select>
                    <div v-if="departmentParentDisabled" class="form-tip" style="margin-bottom:-16px">{{$L('含有子部门无法修改上级部门')}}</div>
                </FormItem>
                <FormItem prop="owner_userid" :label="$L('部门负责人')">
                    <UserSelect v-model="departmentData.owner_userid" :multiple-max="1" :title="$L('请选择部门负责人')"/>
                </FormItem>
                <template v-if="departmentData.id == 0">
                    <Divider orientation="left">{{$L('群组设置')}}</Divider>
                    <FormItem prop="dialog_group" :label="$L('部门群聊')">
                        <RadioGroup v-model="departmentData.dialog_group">
                            <Radio label="new">{{$L('创建部门群')}}</Radio>
                            <Radio label="use">{{$L('使用现有群')}}</Radio>
                        </RadioGroup>
                    </FormItem>
                    <FormItem v-if="departmentData.dialog_group === 'use'" prop="dialog_useid" :label="$L('选择群组')">
                        <Select
                            v-model="departmentData.dialog_useid"
                            filterable
                            :remote-method="dialogRemote"
                            :placeholder="$L('输入关键词搜索群')"
                            :loading="dialogLoad">
                            <Option v-for="(option, index) in dialogList" :value="option.id" :label="option.name" :key="index">
                                <div class="team-department-add-dialog-group">
                                    <div class="dialog-name">{{option.name}}</div>
                                    <UserAvatar :userid="option.owner_id" :size="20"/>
                                </div>
                            </Option>
                        </Select>
                        <div class="form-tip">{{$L('仅支持选择个人群转为部门群')}}</div>
                    </FormItem>
                </template>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="departmentShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="departmentLoading > 0" @click="onSaveDepartment">{{$L(departmentData.id > 0 ? '保存' : '新建')}}</Button>
            </div>
        </Modal>

        <!--修改MAC-->
        <Modal
            v-model="checkinMacEditShow"
            :title="$L('修改签到MAC地址')">
            <Form :model="checkinMacEditData" label-width="auto" @submit.native.prevent>
                <Alert type="error" style="margin-bottom:18px">{{$L(`正在进行帐号【ID:${checkinMacEditData.userid}，${checkinMacEditData.nickname}】MAC地址修改。`)}}</Alert>
                <Row class="team-department-checkin-item">
                    <Col span="12">{{$L('设备MAC地址')}}</Col>
                    <Col span="12">{{$L('备注')}}</Col>
                </Row>
                <Row v-for="(item, key) in checkinMacEditData.checkin_macs" :key="key" class="team-department-checkin-item">
                    <Col span="12">
                        <Input
                            v-model="item.mac"
                            :maxlength="20"
                            :placeholder="$L('请输入设备MAC地址')"
                            clearable
                            @on-clear="delCheckinDatum(key)"/>
                    </Col>
                    <Col span="12">
                        <Input v-model="item.remark" :maxlength="100" :placeholder="$L('备注')"/>
                    </Col>
                </Row>
                <Button type="default" icon="md-add" @click="addCheckinDatum">{{$L('添加设备')}}</Button>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="checkinMacEditShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="checkinMacEditLoading > 0" @click="operationUser(checkinMacEditData, true)">{{$L('确定修改')}}</Button>
            </div>
        </Modal>

        <!--修改部门-->
        <Modal
            v-model="departmentEditShow"
            :title="$L('修改部门')">
            <Form :model="departmentEditData" label-width="auto" @submit.native.prevent>
                <Alert type="error" style="margin-bottom:18px">{{$L(`正在进行帐号【ID:${departmentEditData.userid}，${departmentEditData.nickname}】部门修改。`)}}</Alert>
                <FormItem :label="$L('修改部门')">
                    <Select v-model="departmentEditData.department" multiple :multiple-max="10" :placeholder="$L('留空为默认部门')">
                        <Option v-for="(item, index) in departmentList" :value="item.id" :key="index">{{ item.name }}</Option>
                    </Select>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="departmentEditShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="departmentEditLoading > 0" @click="operationUser(departmentEditData, true)">{{$L('确定修改')}}</Button>
            </div>
        </Modal>

        <!--操作离职-->
        <Modal
            v-model="disableShow"
            :title="$L('操作离职')">
            <Form :model="disableData" label-width="auto" @submit.native.prevent>
                <Alert type="error" style="margin-bottom:18px">{{$L(`正在进行帐号【ID:${disableData.userid}，${disableData.nickname}】离职操作。`)}}</Alert>
                <FormItem :label="$L('离职时间')">
                    <DatePicker
                        ref="disableTime"
                        v-model="disableData.disable_time"
                        :editable="false"
                        :placeholder="$L('选择离职时间')"
                        :options="disableOptions"
                        style="width:100%"
                        format="yyyy/MM/dd HH:mm"
                        type="datetime"/>
                </FormItem>
                <FormItem :label="$L('交接人')">
                    <UserSelect v-model="disableData.transfer_userid" :disabled-choice="[disableData.userid]" :multiple-max="1" :title="$L('选择交接人')"/>
                    <div class="form-tip">{{ $L(`${disableData.nickname} 负责的部门、项目、任务和文件将移交给交接人；同时退出所有群（如果是群主则转让给交接人）`) }}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="disableShow=false">{{$L('取消')}}</Button>
                <Poptip
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    :ok-text="$L('确定')"
                    :cancel-text="$L('取消')"
                    @on-ok="operationUser(disableData, true)"
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
import UserSelect from "../../../components/UserSelect.vue";
import UserAvatarTip from "../../../components/UserAvatar/tip.vue";

export default {
    name: "TeamManagement",
    components: {UserAvatarTip, UserSelect},
    props: {
        checkinMac: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            loadIng: 0,
            minBox: false,
            minWidth: 0,

            keys: {},
            keyIs: false,
            keyDisable: false,

            columns: [
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
                    minWidth: 160,
                    render: (h, {row}) => {
                        const arr = [h('AutoTip', row.email)];
                        const {email_verity, identity, disable_at, is_principal} = row;
                        if (email_verity) {
                            arr.push(h('Icon', {
                                props: {
                                    type: 'md-mail'
                                }
                            }))
                        }
                        if (is_principal) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'blue'
                                }
                            }, this.$L('负责人')))
                        }
                        if (identity.includes("ldap")) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'orange'
                                }
                            }, 'LDAP'))
                        }
                        if (identity.includes("admin")) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'warning'
                                }
                            }, this.$L('管理员')))
                        }
                        if (identity.includes("temp")) {
                            arr.push(h('Tag', {
                                props: {
                                    color: 'success'
                                }
                            }, this.$L('临时')))
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
                    title: this.$L('电话'),
                    key: 'tel',
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('QuickEdit', {
                            props: {
                                value: row.tel,
                            },
                            on: {
                                'on-update': (val, cb) => {
                                    this.operationUser({
                                        userid: row.userid,
                                        tel: val
                                    }, true).finally(cb);
                                }
                            }
                        }, [
                            h('AutoTip', row.tel || '-')
                        ]);
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
                                    }, true).finally(cb);
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
                                    }, true).finally(cb);
                                }
                            }
                        }, [
                            h('AutoTip', row.profession || '-')
                        ]);
                    },
                },
                {
                    title: this.$L('部门'),
                    key: 'department',
                    minWidth: 80,
                    render: (h, {row}) => {
                        let departments = []
                        row.department.some(did => {
                            const data = this.departmentList.find(d => d.id == did)
                            if (data) {
                                departments.push(data.name)
                            }
                        })
                        if (departments.length === 0) {
                            return h('div', this.$L('默认部门'));
                        } else {
                            const tmp = []
                            tmp.push(h('span', {
                                domProps: {
                                    title: departments[0]
                                }
                            }, departments[0]))
                            if (departments.length > 1) {
                                departments = departments.splice(1)
                                tmp.push(h('ETooltip', [
                                    h('div', {
                                        slot: 'content',
                                        domProps: {
                                            innerHTML: departments.join("<br/>")
                                        }
                                    }),
                                    h('div', {
                                        class: 'department-tag-num'
                                    }, ` +${departments.length}`)
                                ]))
                            }
                            return h('div', {
                                class: 'team-table-department-warp'
                            }, tmp);
                        }
                    },
                },
                {
                    key: 'line_at',
                    width: 168,
                    renderHeader: (h) => {
                        const arr = [];
                        if (this.keyDisable) {
                            arr.push(h('span', {
                                style: {
                                    color: '#f90'
                                }
                            }, this.$L('离职时间')))
                            arr.push(h('span', '/'))
                        }
                        arr.push(h('span', this.$L('最后在线')))
                        return h('div', arr)
                    },
                    render: (h, params) => {
                        const {line_at, disable_at} = params.row;
                        const arr = [];
                        if (this.keyDisable) {
                            arr.push(h('div', {
                                style: {
                                    color: '#f90'
                                }
                            }, disable_at || '-'))
                        }
                        arr.push(h('div', line_at || '-'))
                        return h('div', arr);
                    }
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


                        if (identity.includes('temp')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'cleartemp',
                                },
                            }, [h('div', this.$L('取消临时身份'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'settemp',
                                },
                            }, [h('div', this.$L('设为临时帐号'))]));
                        }

                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'email',
                            },
                        }, [h('div', this.$L('修改邮箱'))]))

                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'password',
                            },
                        }, [h('div', this.$L('修改密码'))]))

                        if (this.checkinMac) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'checkin_mac',
                                },
                            }, [h('div', this.$L('修改MAC'))]))
                        }

                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'department',
                            },
                        }, [h('div', this.$L('修改部门'))]))

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
            ],
            list: [],

            page: 1,
            pageSize: 20,
            total: 0,
            noText: '',

            checkinMacEditShow: false,
            checkinMacEditLoading: 0,
            checkinMacEditData: {},

            departmentEditShow: false,
            departmentEditLoading: 0,
            departmentEditData: {},

            disableShow: false,
            disableLoading: 0,
            disableData: {},
            disableOptions: {
                shortcuts: [
                    {
                        text: '12:00',
                        value () {
                            return $A.Date($A.formatDate("Y-m-d 12:00:00"));
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '17:00',
                        value () {
                            return $A.Date($A.formatDate("Y-m-d 17:00:00"));
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '18:00',
                        value () {
                            return $A.Date($A.formatDate("Y-m-d 18:00:00"));
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '19:00',
                        value () {
                            return $A.Date($A.formatDate("Y-m-d 19:00:00"));
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: this.$L('现在'),
                        value () {
                            return new Date();
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                ]
            },

            departmentShow: false,
            departmentLoading: 0,
            departmentSelect: -1,
            departmentData: {
                id: 0,
                name: '',
                parent_id: 0,
                owner_userid: [],
                dialog_group: 'new',
                dialog_useid: 0
            },
            departmentList: [],

            dialogLoad: false,
            dialogList: [],

            nullCheckinDatum: {
                'mac': '',
                'remark': '',
            },
        }
    },
    created() {
        if (this.checkinMac) {
            this.columns.splice(5, 0, {
                title: this.$L('MAC地址'),
                key: 'checkin_mac',
                minWidth: 80,
                render: (h, {row}) => {
                    let checkin_macs = $A.cloneJSON(row.checkin_macs || [])
                    if (checkin_macs.length === 0) {
                        return h('div', '-');
                    } else {
                        const desc = (item) => {
                            if (item.remark) {
                                return `${item.mac} (${item.remark})`
                            }
                            return item.mac
                        }
                        const tmp = []
                        tmp.push(h('AutoTip', desc(checkin_macs[0])))
                        if (checkin_macs.length > 1) {
                            checkin_macs = checkin_macs.splice(1)
                            tmp.push(h('ETooltip', [
                                h('div', {
                                    slot: 'content',
                                    domProps: {
                                        innerHTML: checkin_macs.map(item => {
                                            return desc(item)
                                        }).join("<br/>")
                                    }
                                }),
                                h('div', {
                                    class: 'department-tag-num'
                                }, ` +${checkin_macs.length}`)
                            ]))
                        }
                        return h('div', {
                            class: 'team-table-department-warp'
                        }, tmp);
                    }
                },
            })
        }
    },
    mounted() {
        this.getLists();
        this.getDepartmentLists();
    },
    watch: {
        keyIs(v) {
            if (!v) {
                this.keys = {}
                this.setPage(1)
            }
        },
        departmentSelect() {
            this.setPage(1)
        },
        windowPortrait: {
            handler(v) {
                this.minBox = v
            },
            immediate: true
        },
        minBox: {
            handler() {
                this.$nextTick(_=> {
                    if (this.$el && this.$el.clientWidth > 0) {
                        this.minWidth = this.$el.clientWidth
                    }
                });
            },
            immediate: true
        }
    },
    computed: {
        departmentParentDisabled() {
            return !!(this.departmentData.id > 0 && this.departmentList.find(({parent_id}) => parent_id == this.departmentData.id));
        },
        userStyle({minWidth, windowPortrait}) {
            const style = {}
            if (minWidth > 0 && windowPortrait) {
                style.minWidth = (minWidth - 40) + 'px'
            }
            return style
        }
    },
    methods: {
        onSearch() {
            this.page = 1;
            this.getLists();
        },

        getLists() {
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            this.keyDisable = this.keys.disable === "yes";
            let keys = $A.cloneJSON(this.keys)
            if (this.departmentSelect > -1) {
                keys = Object.assign(keys, {
                    department: this.departmentSelect
                })
            }
            this.$store.dispatch("call", {
                url: 'users/lists',
                data: {
                    keys,
                    get_checkin_mac: this.checkinMac ? 1 : 0,
                    page: Math.max(this.page, 1),
                    pagesize: Math.max($A.runNum(this.pageSize), 10),
                },
            }).then(({data}) => {
                this.page = data.current_page;
                this.total = data.total;
                this.list = data.data;
                this.noText = '没有相关的成员';
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

        dropUser(name, row) {
            switch (name) {
                case 'settemp':
                    $A.modalConfirm({
                        content: `你确定将【ID:${row.userid}，${row.nickname}】设为临时帐号吗？（注：临时帐号限制请查看系统设置）`,
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'cleartemp':
                    $A.modalConfirm({
                        content: `你确定取消【ID:${row.userid}，${row.nickname}】临时身份吗？`,
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'email':
                    $A.modalInput({
                        title: "修改邮箱",
                        placeholder: `请输入新的邮箱（${row.email}）`,
                        onOk: (value) => {
                            if (!value) {
                                return '请输入新的邮箱地址'
                            }
                            return this.operationUser({
                                userid: row.userid,
                                email: value
                            });
                        }
                    });
                    break;

                case 'password':
                    $A.modalInput({
                        title: "修改密码",
                        placeholder: "请输入新的密码",
                        onOk: (value) => {
                            if (!value) {
                                return '请输入新的密码'
                            }
                            return this.operationUser({
                                userid: row.userid,
                                password: value
                            });
                        }
                    });
                    break;

                case 'checkin_mac':
                    this.checkinMacEditData = {
                        type: 'checkin_macs',
                        userid: row.userid,
                        nickname: row.nickname,
                        checkin_macs: row.checkin_macs,
                    };
                    if (this.checkinMacEditData.checkin_macs.length === 0) {
                        this.addCheckinDatum();
                    }
                    this.checkinMacEditShow = true;
                    break;

                case 'department':
                    let departments = []
                    row.department.some(did => {
                        const data = this.departmentList.find(d => d.id == did)
                        if (data) {
                            departments.push(data.owner_userid === row.userid ? `${data.name} (${this.$L('负责人')})` : data.name)
                        }
                    })
                    this.departmentEditData = {
                        type: 'department',
                        userid: row.userid,
                        nickname: row.nickname,
                        department: row.department.map(id => parseInt(id)),
                    };
                    this.departmentEditShow = true;
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
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'delete':
                    $A.modalInput({
                        title: `删除帐号【ID:${row.userid}，${row.nickname}】`,
                        placeholder: "请输入删除原因",
                        okText: "确定删除",
                        onOk: (value) => {
                            if (!value) {
                                return '删除原因不能为空'
                            }
                            return this.operationUser({
                                userid: row.userid,
                                type: name,
                                delete_reason: value
                            });
                        }
                    })
                    break;

                default:
                    this.operationUser({
                        userid: row.userid,
                        type: name
                    }, true);
                    break;
            }
        },

        operationUser(data, tipErr) {
            return new Promise((resolve, reject) => {
                if (data.type == 'checkin_macs') {
                    this.checkinMacEditLoading++;
                } else if (data.type == 'department') {
                    this.departmentEditLoading++;
                } else if (data.type == 'setdisable') {
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
                    if (data.type == 'checkin_macs') {
                        this.checkinMacEditShow = false;
                    } else if (data.type == 'department') {
                        this.departmentEditShow = false;
                    } else if (data.type == 'setdisable') {
                        this.disableShow = false;
                    }
                }).catch(({msg}) => {
                    if (tipErr === true) {
                        $A.modalError(msg);
                    }
                    this.getLists();
                    reject(msg)
                }).finally(_ => {
                    if (data.type == 'checkin_macs') {
                        this.checkinMacEditLoading--;
                    } else if (data.type == 'department') {
                        this.departmentEditLoading--;
                    } else if (data.type == 'setdisable') {
                        this.disableLoading--;
                    } else {
                        this.loadIng--;
                    }
                })
            })
        },

        getDepartmentLists() {
            this.departmentLoading++;
            this.$store.dispatch("call", {
                url: 'users/department/list',
            }).then(({data}) => {
                this.departmentList = []
                this.generateDepartmentList(data, 0, 1)
            }).finally(_ => {
                this.departmentLoading--;
            })
        },

        generateDepartmentList(data, parent_id, level) {
            data.some(item => {
                if (item.parent_id == parent_id) {
                    this.departmentList.push(Object.assign(item, {
                        level: level + 1
                    }))
                    this.generateDepartmentList(data, item.id, level + 1)
                }
            })
        },

        onShowDepartment(data) {
            this.departmentData = Object.assign({
                id: 0,
                name: '',
                parent_id: 0,
                owner_userid: [],
                dialog_group: 'new'
            }, data || {})
            this.departmentShow = true
        },

        onSaveDepartment() {
            this.departmentLoading++;
            this.$store.dispatch("call", {
                url: 'users/department/add',
                data: Object.assign(this.departmentData, {
                    owner_userid: this.departmentData.owner_userid[0],
                }),
            }).then(({msg}) => {
                $A.messageSuccess(msg)
                this.getDepartmentLists()
                this.getLists()
                this.departmentShow = false
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.departmentLoading--;
            })
        },

        onSelectDepartment(id) {
            if (this.windowPortrait) {
                this.minBox = true
            }
            //
            if (this.departmentSelect === id) {
                this.departmentSelect = -1
                return
            }
            this.departmentSelect = id
        },

        onOpDepartment(val) {
            if ($A.leftExists(val, 'add_')) {
                this.onShowDepartment({
                    parent_id: parseInt(val.substr(4))
                })
            } else if ($A.leftExists(val, 'edit_')) {
                const editItem = this.departmentList.find(({id}) => id === parseInt(val.substr(5)))
                if (editItem) {
                    this.onShowDepartment(editItem)
                }
            } else if ($A.leftExists(val, 'del_')) {
                const delItem = this.departmentList.find(({id}) => id === parseInt(val.substr(4)))
                if (delItem) {
                    $A.modalConfirm({
                        title: this.$L('删除部门'),
                        content: `<div>${this.$L(`你确定要删除【${delItem.name}】部门吗？`)}</div><div style="color:#f00;font-weight:600">${this.$L(`注意：此操作不可恢复，部门下的成员将移至默认部门。`)}</div>`,
                        language: false,
                        loading: true,
                        onOk: () => {
                            return new Promise((resolve, reject) => {
                                this.$store.dispatch("call", {
                                    url: 'users/department/del',
                                    data: {
                                        id: delItem.id
                                    },
                                }).then(({msg}) => {
                                    if (delItem.id === this.departmentSelect) {
                                        this.departmentSelect = -1
                                    }
                                    resolve(msg);
                                    this.getDepartmentLists();
                                }).catch(({msg}) => {
                                    reject(msg);
                                })
                            })
                        }
                    });
                }
            }
        },

        dialogRemote(key) {
            if (key !== '') {
                this.dialogLoad = true;
                this.$store.dispatch("call", {
                    url: 'dialog/group/searchuser',
                    data: {
                        key,
                    },
                }).then(({data}) => {
                    this.dialogList = data.list;
                }).finally(_ => {
                    this.dialogLoad = false;
                })
            } else {
                this.dialogList = [];
            }
        },

        addCheckinDatum() {
            this.checkinMacEditData.checkin_macs.push($A.cloneJSON(this.nullCheckinDatum));
        },

        delCheckinDatum(key) {
            this.checkinMacEditData.checkin_macs.splice(key, 1);
            if (this.checkinMacEditData.checkin_macs.length === 0) {
                this.addCheckinDatum();
            }
        },
    }
}
</script>
