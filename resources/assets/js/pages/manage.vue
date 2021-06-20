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
                    <i class="iconfont">&#xe6fb;</i>
                    <div class="menu-title">{{$L('仪表板')}}</div>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <i class="iconfont">&#xe6f5;</i>
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <i class="iconfont">&#xe6eb;</i>
                    <div class="menu-title">{{$L('消息')}}</div>
                    <Badge class="menu-badge" :count="dialogMsgUnread"></Badge>
                </li>
                <li class="menu-project">
                    <ul>
                        <li
                            v-for="(item, key) in projects"
                            :key="key"
                            :class="classNameRoute('project/' + item.id, openMenu[item.id])"
                            @click="toggleRoute('project/' + item.id)">
                            <div class="project-h1">
                                <em @click.stop="toggleOpenMenu(item.id)"></em>
                                <div class="title">{{item.name}}</div>
                                <div v-if="item.task_my_num - item.task_my_complete > 0" class="num">{{item.task_my_num - item.task_my_complete}}</div>
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
            <Button class="manage-box-new" type="primary" icon="md-add" @click="onAddShow">{{$L('新建项目')}}</Button>
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
                    <Input ref="projectName" type="text" v-model="addData.name"></Input>
                </FormItem>
                <FormItem v-if="addData.columns" :label="$L('任务列表')">
                    <TagInput v-model="addData.columns"/>
                </FormItem>
                <FormItem v-else :label="$L('项目模板')">
                    <Select :value="0" @on-change="(i) => {$set(addData, 'columns', columns[i].value.join(','))}" :placeholder="$L('请选择模板')">
                        <Option v-for="(item, index) in columns" :value="index" :key="index">{{ item.label }}</Option>
                    </Select>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAddProject">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--任务详情-->
        <Modal
            :value="taskId > 0"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: taskData.dialog_id ? '1200px' : '640px'
            }"
            @on-visible-change="taskVisibleChange"
            footer-hide>
            <TaskDetail :open-task="taskData"/>
        </Modal>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
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
                columns: '',
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
        this.$store.dispatch("getUserInfo");
        this.$store.dispatch("getTaskPriority");
    },

    deactivated() {
        this.addShow = false;
    },

    computed: {
        ...mapState([
            'userId',
            'userInfo',
            'dialogMsgUnread',
            'projects',
            'projectChatShow',
            'taskId',
        ]),
        ...mapGetters(['taskData'])
    },

    watch: {
        '$route' (route) {
            this.curPath = route.path;
        },
        taskId (id) {
            if (id > 0) {
                if (this.projectChatShow) {
                    this._projectChatShow = true;
                    this.$store.dispatch("toggleBoolean", "projectChatShow");
                }
            } else {
                if (this._projectChatShow) {
                    this._projectChatShow = false;
                    this.$store.dispatch("toggleBoolean", "projectChatShow");
                }
            }
        }
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
                        this.$store.dispatch("logout")
                    }
                });
                return;
            }
            this.toggleRoute('setting/' + path);
        },

        classNameRoute(path, openMenu) {
            return {
                "active": this.curPath == '/manage/' + path,
                "open-menu": openMenu === true,
            };
        },

        onAddShow() {
            this.addShow = true;
            this.$nextTick(() => {
                this.$refs.projectName.focus();
            })
        },

        onAddProject() {
            this.$refs.addProject.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'project/add',
                        data: this.addData,
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.loadIng--;
                        this.addShow = false;
                        this.$refs.addProject.resetFields();
                        this.$store.dispatch("saveProject", data);
                        this.toggleRoute('project/' + data.id)
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                        this.loadIng--;
                    });
                }
            });
        },

        taskVisibleChange(visible) {
            if (!visible) {
                this.$store.dispatch('openTask', 0)
            }
        }
    }
}
</script>
