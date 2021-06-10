<template>
    <div class="page-manage">
        <div class="manage-box-menu">
            <Dropdown class="manage-box-dropdown" trigger="click" @on-click="settingRoute">
                <div class="manage-box-title">
                    <div class="manage-box-avatar">
                        <UserAvatar :userid="userId" :size="36" tooltip-disabled/>
                    </div>
                    <span>{{userInfo.nickname}}</span>
                    <div class="manage-box-arrow">
                        <Icon type="ios-arrow-up" />
                        <Icon type="ios-arrow-down" />
                    </div>
                </div>
                <DropdownMenu slot="list">
                    <DropdownItem v-for="(item, key) in menu" :key="key" :name="item.path">{{$L(item.name)}}</DropdownItem>
                    <DropdownItem divided name="signout">{{$L('退出登录')}}</DropdownItem>
                </DropdownMenu>
            </Dropdown>
            <ul>
                <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                    <Icon type="ios-speedometer-outline" />
                    <div class="menu-title">{{$L('仪表板')}}</div>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <Icon type="ios-calendar-outline" />
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <Icon type="ios-chatbubbles-outline" />
                    <div class="menu-title">{{$L('消息')}}</div>
                    <Badge class="menu-badge" :count="dialogMsgUnread"></Badge>
                </li>
                <li class="menu-project">
                    <ul>
                        <li
                            v-for="(item, key) in projectList"
                            :key="key"
                            :class="classNameRoute('project/' + item.id, openMenu[item.id])"
                            @click="toggleRoute('project/' + item.id)">
                            <div class="project-h1">
                                <em @click.stop="toggleOpenMenu(item.id)"></em>
                                <div class="title">{{item.name}}</div>
                                <div v-if="item.task_my_num > 0" class="num">{{item.task_my_num}}</div>
                            </div>
                            <div class="project-h2">
                                <p>
                                    <em>{{$L('我的')}}:</em>
                                    <span>{{item.task_my_complete}}/{{item.task_my_num}}</span>
                                    <Progress :percent="item.task_my_percent" :stroke-width="6" />
                                </p>
                                <p>
                                    <em>{{$L('全部')}}:</em>
                                    <span>{{item.task_complete}}/{{item.task_num}}</span>
                                    <Progress :percent="item.task_percent" :stroke-width="6" />
                                </p>
                            </div>
                        </li>
                    </ul>
                    <Loading v-if="loadIng > 0"/>
                </li>
            </ul>
            <Button class="manage-box-new" type="primary" icon="md-add" @click="addShow=true">{{$L('新建项目')}}</Button>
        </div>

        <div class="manage-box-main">
            <keep-alive>
                <router-view class="manage-box-view overlay"></router-view>
            </keep-alive>
        </div>

        <!--新建项目-->
        <Modal
            v-model="addShow"
            :title="$L('新建项目')"
            :mask-closable="false">
            <Form ref="addProject" :model="addData" :rules="addRule" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input type="text" v-model="addData.name"></Input>
                </FormItem>
                <FormItem prop="columns" :label="$L('项目模板')">
                    <Select v-model="addData.template" @on-change="(res) => {$set(addData, 'columns', columns[res].value)}" :placeholder="$L('请选择模板')">
                        <Option v-for="(item, index) in columns" :value="index" :key="index">{{ item.label }}</Option>
                    </Select>
                </FormItem>
                <FormItem v-if="addData.columns.length > 0" :label="$L('任务列表')">
                    <div style="line-height:38px">
                        <span v-for="(item, index) in addData.columns">
                            <Tag @on-close="() => { addData.columns.splice(index, 1)}" closable size="large" color="primary">{{item}}</Tag>
                        </span>
                    </div>
                    <div style="margin-top:4px;"></div>
                    <div style="margin-bottom:-16px">
                        <Button icon="ios-add" type="dashed" @click="addColumns">{{$L('添加列表')}}</Button>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAddProject">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--任务详情-->
        <Modal
            v-model="projectTask._show"
            :mask-closable="false"
            footer-hide>
            <TaskDetail/>
        </Modal>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import TaskDetail from "./manage/components/TaskDetail";

export default {
    components: {TaskDetail},
    data() {
        return {
            loadIng: 0,

            curPath: this.$route.path,

            addShow: false,
            addData: {
                name: '',
                columns: [],
                template: 0,
            },
            addRule: {},

            columns: [],

            menu: [
                {
                    path: 'personal',
                    name: '个人设置'
                },
                {
                    path: 'password',
                    name: '密码设置'
                },
                {
                    path: 'system',
                    name: '系统设置'
                },
                {
                    path: 'priority',
                    name: '任务等级'
                }
            ],

            openMenu: {}
        }
    },

    mounted() {
        this.$store.commit('getUserInfo');
    },

    deactivated() {
        this.addShow = false;
    },

    computed: {
        ...mapState(['userId', 'userInfo', 'dialogMsgUnread', 'projectList', 'projectTask']),
    },

    watch: {
        '$route' (route) {
            this.curPath = route.path;
        },
    },

    methods: {
        initLanguage() {
            this.columns = [{
                label: this.$L('空白模板'),
                value: [],
            }, {
                label: this.$L('软件开发'),
                value: [this.$L('产品规划'), this.$L('前端开发'), this.$L('后端开发'), this.$L('测试'), this.$L('发布'), this.$L('其它')],
            }, {
                label: this.$L('产品开发'),
                value: [this.$L('产品计划'), this.$L('正在设计'), this.$L('正在研发'), this.$L('测试'), this.$L('准备发布'), this.$L('发布成功')],
            }];
            this.addRule = {
                name: [
                    { required: true, message: this.$L('请填写项目名称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('项目名称至少2个字！'), trigger: 'change' }
                ]
            };
        },

        toggleRoute(path) {
            this.goForward({path: '/manage/' + path});
        },

        toggleOpenMenu(id) {
            this.$set(this.openMenu, id, !this.openMenu[id])
        },

        settingRoute(path) {
            if (path === 'signout') {
                $A.modalConfirm({
                    title: '退出登录',
                    content: '你确定要登出系统？',
                    onOk: () => {
                        $A.logout()
                    }
                });
                return;
            }
            this.toggleRoute('setting/' + path);
        },

        classNameRoute(path, openMenu) {
            return {
                "active": $A.leftExists(this.curPath, '/manage/' + path),
                "open-menu": openMenu === true,
            };
        },

        addColumns() {
            this.columnsValue = "";
            $A.modalConfirm({
                render: (h) => {
                    return h('div', [
                        h('div', {
                            style: {
                                fontSize: '16px',
                                fontWeight: '500',
                                marginBottom: '20px',
                            }
                        }, this.$L('添加流程')),
                        h('TagInput', {
                            props: {
                                value: this.columnsValue,
                                autofocus: true,
                                placeholder: this.$L('请输入流程名称，多个可用英文逗号分隔。')
                            },
                            on: {
                                input: (val) => {
                                    this.columnsValue = val;
                                }
                            }
                        })
                    ])
                },
                onOk: () => {
                    if (this.columnsValue) {
                        let array = $A.trim(this.columnsValue).split(",");
                        array.forEach((name) => {
                            if ($A.trim(name)) {
                                this.addData.columns.push($A.trim(name));
                            }
                        });
                    }
                },
            })
        },

        onAddProject() {
            this.$refs.addProject.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    $A.apiAjax({
                        url: 'project/add',
                        data: this.addData,
                        complete: () => {
                            this.loadIng--;
                        },
                        success: ({ret, data, msg}) => {
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.addShow = false;
                                this.$refs.addProject.resetFields();
                                this.$set(this.addData, 'template', 0);
                                this.$store.commit('saveProjectData', data);
                                this.toggleRoute('project/' + data.id)
                            } else {
                                $A.modalError(msg);
                            }
                        }
                    });
                }
            });
        },
    }
}
</script>
