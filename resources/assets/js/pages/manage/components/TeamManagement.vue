<template>
    <div class="team-management">
        <div class="management-title">
            {{$L('团队管理')}}
            <div class="title-zoom" @click="minBox=!minBox">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                        <path d="M9 3v18"></path>
                    </svg>
                </span>
            </div>
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
            </div>
        </div>
        <div class="management-box" :class="{'min-box':minBox}">
            <div class="management-department" :style="{width: departmentWidth + 'px'}">
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
                        :class="[`level-${item.level}`, departmentSelect === item.id || departmentOperation === item.id ? 'active' : '']"
                        @click="onSelectDepartment(item.id)">
                        <UserAvatarTip :userid="item.owner_userid" :size="20" class="department-icon">
                            <p><strong>{{$L('部门负责人')}}</strong></p>
                        </UserAvatarTip>
                        <div class="department-title">{{item.name}}</div>
                        <EDropdown
                            size="medium"
                            trigger="click"
                            @visible-change="onVcDepartment($event, item.id)"
                            @command="onOpDepartment">
                            <i @click.stop="" class="taskfont department-menu">&#xe6e9;</i>
                            <EDropdownMenu slot="dropdown">
                                <EDropdownItem v-if="item.level <= 3" :command="`add_${item.id}`">
                                    <div>{{$L('添加子部门')}}</div>
                                </EDropdownItem>
                                <EDropdownItem v-if="item.dialog_id" :command="`dialog_${item.dialog_id}`">
                                    <div>{{$L('部门交流群')}}</div>
                                </EDropdownItem>
                                <EDropdownItem :command="`sync_${item.id}`">
                                    <div>{{$L('同步部门成员')}}</div>
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
            <ResizeLine
                class="management-resize"
                placement="right"
                v-model="departmentWidth"
                :min="100"
                :max="900"/>
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
                        <template v-if="checkinMode">
                            <li>
                                <div class="search-label">
                                    {{$L("人脸图片")}}
                                </div>
                                <div class="search-content">
                                    <Select v-model="keys.checkin_face" :placeholder="$L('全部')">
                                        <Option value="">{{$L('全部')}}</Option>
                                        <Option value="yes">{{$L('已上传')}}</Option>
                                        <Option value="no">{{$L('未上传')}}</Option>
                                    </Select>
                                </div>
                            </li>
                            <li>
                                <div class="search-label">
                                    {{$L("MAC地址")}}
                                </div>
                                <div class="search-content">
                                    <Input v-model="keys.checkin_mac" :placeholder="$L('MAC地址')" clearable/>
                                </div>
                            </li>
                        </template>
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
                            <SearchButton
                                :loading="loadIng > 0"
                                :filtering="keyIs"
                                placement="bottom"
                                @search="onSearch"
                                @refresh="getLists"
                                @cancelFilter="keyIs=false"/>
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
            <Form ref="addProject" :model="departmentData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="name" :label="$L('部门名称')">
                    <Input type="text" v-model="departmentData.name" :placeholder="$L('请输入部门名称')"></Input>
                </FormItem>
                <FormItem prop="parent_id" :label="$L('上级部门')">
                    <Select v-model="departmentData.parent_id" :placeholder="$L('请选择上级部门')">
                        <Option :value="0">
                            <div class="department-level-name level-1">{{ $L('默认部门') }}</div>
                        </Option>
                        <Option
                            v-for="(item, index) in departmentList"
                            :disabled="item.level > 3 || item.id == departmentData.id || (item.parent_id == departmentData.id && departmentData.id > 0)"
                            :value="item.id"
                            :key="index"
                            :label="item.chains.join(' - ')">
                            <div :class="`department-level-name level-${item.level}`">{{ item.name }}</div>
                        </Option>
                    </Select>
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
                                    <div class="dialog-id">ID: {{option.id}}</div>
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
        <!--编辑用户信息-->
        <UserEditModal
            v-model="userEditShow"
            :user-data="userEditData"
            :checkin-mode="checkinMode"
            :department-list="departmentList"
            @updated="getLists"/>

        <!--操作离职-->
        <Modal
            v-model="disableShow"
            :title="$L('操作离职')">
            <Form :model="disableData" v-bind="formOptions" @submit.native.prevent>
                <Alert type="error" style="margin-bottom:18px">{{$L(`正在进行帐号【ID:${disableData.userid}, ${disableData.nickname}】离职操作。`)}}</Alert>
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
                    <div class="form-tip">{{ $L('可选，留空则不执行迁移') }}</div>
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
import ResizeLine from "../../../components/ResizeLine.vue";
import SearchButton from "../../../components/SearchButton.vue";
import UserEditModal from "./UserEditModal.vue";
import {mapState} from "vuex";

export default {
    name: "TeamManagement",
    components: {SearchButton, ResizeLine, UserAvatarTip, UserSelect, UserEditModal},
    props: {
        checkinMode: {
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
                        const arr = [h('AutoTip', {
                            style: {
                                minWidth: '50px'
                            }
                        }, row.email)];
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
                        return h('AutoTip', row.tel || '-');
                    }
                },
                {
                    title: this.$L('昵称'),
                    key: 'nickname',
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('AutoTip', row.nickname_original || '-');
                    }
                },
                {
                    title: this.$L('职位/职称'),
                    key: 'profession',
                    minWidth: 80,
                    render: (h, {row}) => {
                        return h('AutoTip', row.profession || '-');
                    },
                },
                {
                    title: this.$L('部门'),
                    key: 'department',
                    minWidth: 80,
                    render: (h, {row}) => {
                        const departments = []
                        row.department.some(did => {
                            const data = this.departmentList.find(d => d.id == did)
                            if (data) {
                                departments.push({
                                    id: data.id,
                                    name: data.name,
                                    chain: data.chains.join(' - ')
                                })
                            }
                        })
                        departments.sort((a, b) => {
                            return a.id - b.id
                        })
                        if (departments.length === 0) {
                            return h('AutoTip', this.$L('默认部门'));
                        } else {
                            const tmp = []
                            tmp.push(h('span', {
                                domProps: {
                                    title: departments[0].chain
                                }
                            }, departments[0].name))
                            if (departments.length > 1) {
                                tmp.push(h('ETooltip', [
                                    h('ol', {
                                        slot: 'content',
                                        style: {
                                            lineHeight: '1.5',
                                            paddingLeft: '18px'
                                        },
                                        domProps: {
                                            innerHTML: departments.map(({chain}) => `<li>${chain}</li>`).join('')
                                        }
                                    }),
                                    h('div', {
                                        class: 'department-tag-num'
                                    }, departments.length)
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
                        return h('AutoTip', arr)
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
                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'openDialog',
                            },
                        }, [h('div', this.$L('打开会话窗口'))]));
                        if (identity.includes('admin')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'clearadmin',
                                    divided: true
                                },
                            }, [h('div', this.$L('取消管理员'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'setadmin',
                                    divided: true
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
                        // 编辑用户信息
                        dropdownItems.push(h('EDropdownItem', {
                            props: {
                                command: 'edit_user_info',
                                divided: true
                            },
                        }, [h('div', this.$L('编辑用户信息'))]));
                        if (identity.includes('disable')) {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'cleardisable',
                                    divided: true
                                },
                                style: {
                                    color: 'red'
                                }
                            }, [h('div', this.$L('恢复帐号（已离职）'))]));
                        } else {
                            dropdownItems.push(h('EDropdownItem', {
                                props: {
                                    command: 'setdisable',
                                    divided: true
                                },
                                style: {
                                    color: 'red'
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

            userEditShow: false,
            userEditData: {},

            departmentWidth: $A.getStorageInt('management.departmentWidth', 239),

            disableShow: false,
            disableLoading: 0,
            disableData: {
                transfer_userid: [],
                disable_time: ''
            },
            disableOptions: {
                shortcuts: [
                    {
                        text: '12:00',
                        value () {
                            return $A.daytz().startOf('day').add(12, 'hour').toDate();
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '17:00',
                        value () {
                            return $A.daytz().startOf('day').add(17, 'hour').toDate();
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '18:00',
                        value () {
                            return $A.daytz().startOf('day').add(18, 'hour').toDate();
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: '19:00',
                        value () {
                            return $A.daytz().startOf('day').add(19, 'hour').toDate();
                        },
                        onClick: (picker) => {
                            picker.handlePickSuccess();
                        }
                    },
                    {
                        text: this.$L('现在'),
                        value () {
                            return $A.daytz().toDate();
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
            departmentOperation: 0,

            dialogLoad: false,
            dialogList: [],
        }
    },
    created() {
        if (this.checkinMode) {
            this.columns.splice(5, 0, ...[
                {
                    key: 'checkin_face',
                    minWidth: 80,
                    renderHeader: (h) => {
                        return h('AutoTip', {
                            style: {
                                color: '#f90'
                            }
                        }, this.$L('人脸图片'))
                    },
                    render: (h, {row}) => {
                        const checkin_face = $A.cloneJSON(row.checkin_face || '')
                        return h('AutoTip', checkin_face ? this.$L('已上传') : '-');
                    },
                }, {
                    key: 'checkin_mac',
                    minWidth: 80,
                    renderHeader: (h) => {
                        return h('AutoTip', {
                            style: {
                                color: '#f90'
                            }
                        }, this.$L('MAC地址'))
                    },
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
                }
            ])
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
        departmentWidth(w) {
            $A.setStorage('management.departmentWidth', w)
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
        ...mapState(['formOptions']),

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
                    get_checkin_data: this.checkinMode ? 1 : 0,
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
                case 'edit_user_info':
                    this.userEditData = $A.cloneJSON(row);
                    this.userEditShow = true;
                    break;

                case 'openDialog':
                    this.$store.dispatch("openDialogUserid", row.userid).catch(({msg}) => {
                        $A.modalError(msg)
                    });
                    break;

                case 'setadmin':
                    $A.modalConfirm({
                        content: `你确定将【ID:${row.userid}, ${row.nickname}】设为管理员吗？`,
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'clearadmin':
                    $A.modalConfirm({
                        content: `你确定取消【ID:${row.userid}, ${row.nickname}】管理员身份吗？`,
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'settemp':
                    $A.modalConfirm({
                        content: `你确定将【ID:${row.userid}, ${row.nickname}】设为临时帐号吗？（注：临时帐号限制请查看系统设置）`,
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
                        content: `你确定取消【ID:${row.userid}, ${row.nickname}】临时身份吗？`,
                        loading: true,
                        onOk: () => {
                            return this.operationUser({
                                userid: row.userid,
                                type: name
                            });
                        }
                    });
                    break;

                case 'setdisable':
                    this.disableData = {
                        type: 'setdisable',
                        userid: row.userid,
                        nickname: row.nickname,
                        transfer_userid: [],
                        disable_time: ''
                    };
                    this.disableShow = true;
                    break;

                case 'cleardisable':
                    $A.modalConfirm({
                        content: `你确定恢复已离职帐号【ID:${row.userid}, ${row.nickname}】吗？（注：此操作仅恢复帐号状态，无法恢复操作离职时移交的数据）`,
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
                        title: `删除帐号【ID:${row.userid}, ${row.nickname}】`,
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
                let submitData = data;
                if (data.type == 'setdisable') {
                    this.disableLoading++;
                    submitData = Object.assign({}, data);
                    if (Array.isArray(submitData.transfer_userid)) {
                        if (submitData.transfer_userid.length > 0) {
                            submitData.transfer_userid = submitData.transfer_userid[0];
                        } else {
                            delete submitData.transfer_userid;
                        }
                    } else if (!submitData.transfer_userid) {
                        delete submitData.transfer_userid;
                    }
                } else {
                    this.loadIng++;
                }
                this.$store.dispatch("call", {
                    url: 'users/operation',
                    data: submitData,
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.getLists();
                    resolve();
                    if (data.type == 'setdisable') {
                        this.disableShow = false;
                    }
                }).catch(({msg}) => {
                    if (tipErr === true) {
                        $A.modalError(msg);
                    }
                    this.getLists();
                    reject(msg);
                }).finally(_ => {
                    if (data.type == 'setdisable') {
                        this.disableLoading--;
                    } else {
                        this.loadIng--;
                    }
                });
            });
        },

        getDepartmentLists() {
            this.departmentLoading++;
            this.$store.dispatch("getDepartmentList").then(list => {
                this.departmentList = list;
            }).finally(_ => {
                this.departmentLoading--;
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

        onVcDepartment(visible, id) {
            this.departmentOperation = visible ? id : 0;
        },

        onOpDepartment(val) {
            if ($A.leftExists(val, 'add_')) {
                this.onShowDepartment({
                    parent_id: parseInt(val.substr(4))
                })
                return
            }

            if ($A.leftExists(val, 'edit_')) {
                const editItem = this.departmentList.find(({id}) => id === parseInt(val.substr(5)))
                if (editItem) {
                    this.onShowDepartment(editItem)
                }
                return;
            }

            if ($A.leftExists(val, 'dialog_')) {
                const dialogId = parseInt(val.substr(7))
                this.$store.dispatch("openDialog", dialogId).catch(({msg}) => {
                    $A.modalError(msg || this.$L('打开会话失败'))
                })
                return;
            }

            if ($A.leftExists(val, 'sync_')) {
                const departmentId = parseInt(val.substr(5));
                
                // 前端先检查是否有子部门
                const hasSubDepartments = this.departmentList.some(dept => dept.parent_id === departmentId);
                if (!hasSubDepartments) {
                    $A.modalWarning({
                        title: this.$L('同步部门成员'),
                        content: this.$L('当前部门没有子部门，无需同步'),
                    });
                    return;
                }
                
                $A.modalConfirm({
                    title: this.$L('同步部门成员'),
                    content: `<div>${this.$L(`你确定要同步部门成员吗？`)}</div><div style="color:#f00;font-weight:600">${this.$L(`注：此操作会同步子部门成员到当前部门`)}</div>`,
                    language: false,
                    loading: true,
                    onOk: () => {
                        return new Promise((resolve, reject) => {
                            this.$store.dispatch("call", {
                                url: 'users/department/sync',
                                data: {
                                    id: departmentId
                                },
                            }).then(({msg}) => {
                                this.getLists();
                                resolve(msg);
                            }).catch(({msg}) => {
                                reject(msg);
                            });
                        });
                    }
                });
                return;
            }

            if ($A.leftExists(val, 'del_')) {
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
    }
}
</script>
