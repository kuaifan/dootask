<template>
    <div class="page-manage" :class="{'show-tabbar': showMobileTabbar, 'not-logged': userId <= 0}">
        <div class="manage-box-menu" :class="{'show-mobile-menu': showMobileMenu}">
            <Dropdown
                class="page-manage-menu-dropdown main-menu"
                trigger="click"
                @on-click="settingRoute"
                @on-visible-change="menuVisibleChange"
                >
                <div :class="['manage-box-title', visibleMenu ? 'menu-visible' : '']">
                    <div class="manage-box-avatar">
                        <UserAvatar :userid="userId" :size="42" tooltipDisabled/>
                    </div>
                    <Badge v-if="!!clientNewVersion" class="manage-box-top-report" dot/>
                </div>
                <DropdownMenu slot="list">
                    <template v-for="item in menu">
                        <!--最近打开的任务-->
                        <Dropdown
                            v-if="item.path === 'taskBrowse'"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            placement="right-start">
                            <DropdownItem :divided="!!item.divided">
                                <div class="manage-menu-flex">
                                    {{$L(item.name)}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list" v-if="taskBrowseLists.length > 0">
                                <DropdownItem
                                    v-for="(item, key) in taskBrowseLists"
                                    v-if="item.id > 0 && key < 10"
                                    :key="key"
                                    class="task-title"
                                    @click.native="openTask(item)"
                                    :name="item.name">
                                    <span v-if="item.flow_item_name" :class="item.flow_item_status">{{item.flow_item_name}}</span>
                                    <div class="task-title-text">{{ item.name }}</div>
                                </DropdownItem>
                            </DropdownMenu>
                            <DropdownMenu v-else slot="list">
                                <DropdownItem style="color:darkgrey">{{ $L('暂无打开记录') }}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 团队管理 -->
                        <Dropdown
                            v-else-if="item.path === 'team'"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            placement="right-start"
                            class="display-block" >
                            <DropdownItem :divided="!!item.divided">
                                <div class="manage-menu-flex">
                                    {{$L(item.name)}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem name="allUser">{{$L('团队管理')}}</DropdownItem>
                                <DropdownItem name="exportTask">{{$L('导出任务统计')}}</DropdownItem>
                                <DropdownItem name="exportOverdueTask">{{$L('导出超期任务')}}</DropdownItem>
                                <DropdownItem name="exportApprove">{{$L('导出审批数据')}}</DropdownItem>
                                <DropdownItem name="exportCheckin">{{$L('导出签到数据')}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 其他菜单 -->
                        <DropdownItem
                            v-else-if="item.visible !== false"
                            :divided="!!item.divided"
                            :name="item.path"
                            :style="item.style || {}">
                            <div class="manage-menu-flex">
                                {{$L(item.name)}}
                                <Badge
                                    v-if="item.path === 'version'"
                                    class="manage-menu-report-badge"
                                    :text="clientNewVersion"/>
                                <Badge
                                    v-else-if="item.path === 'workReport' && reportUnreadNumber > 0"
                                    class="manage-menu-report-badge"
                                    :count="reportUnreadNumber"/>
                                <Badge
                                    v-else-if="item.path === 'approve' && approveUnreadNumber > 0"
                                    class="manage-menu-report-badge"
                                    :count="approveUnreadNumber"/>
                            </div>
                        </DropdownItem>
                    </template>
                </DropdownMenu>
            </Dropdown>
            <Scrollbar class-name="manage-item" @on-scroll="operateVisible = false">
                <div class="menu-base">
                    <ul>
                        <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                            <i class="taskfont" v-if="!classNameRoute('dashboard')?.active">&#xe7f8;</i>
                            <i class="taskfont" v-else>&#xe7f7;</i>
                            <div class="menu-title">{{$L('首页')}}</div>
                            <Badge v-if="dashboardTask.overdue_count > 0" class="menu-badge" type="error" :overflow-count="999" :count="dashboardTask.overdue_count"/>
                            <Badge v-else-if="dashboardTask.today_count > 0" class="menu-badge" type="info" :overflow-count="999" :count="dashboardTask.today_count"/>
                            <Badge v-else-if="dashboardTask.all_count > 0" class="menu-badge" type="primary" :overflow-count="999" :count="dashboardTask.all_count"/>
                        </li>
                        <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                            <i class="taskfont" v-if="!classNameRoute('messenger')?.active">&#xe6eb;</i>
                            <i class="taskfont" v-else>&#xe7f5;</i>
                            <div class="menu-title">{{$L('消息')}}</div>
                            <Badge class="menu-badge" :overflow-count="999" :text="msgUnreadMention"/>
                        </li>
                        <li @click="toggleRoute('project')" :class="classNameRoute('project')">
                            <i class="taskfont" v-if="!classNameRoute('project')?.active">&#xe6fa;</i>
                            <i class="taskfont" v-else>&#xe6f9;</i>
                            <div class="menu-title">{{$L('项目')}}</div>
                        </li>
                        <li @click="toggleRoute('file')" :class="classNameRoute('file')">
                            <i class="taskfont">&#xe6f3;</i>
                            <div class="menu-title">{{$L('文件')}}</div>
                        </li>
                        <li @click="toggleRoute('application')" :class="classNameRoute('application')">
                            <i class="taskfont">&#xe60c;</i>
                            <div class="menu-title">{{$L('应用')}}</div>
                            <Badge class="menu-badge" :overflow-count="999" :text="String((reportUnreadNumber + approveUnreadNumber) || '')"/>
                        </li>
                    </ul>
                </div>
            </Scrollbar>
            <div class="manage-box-new-group">
                <ul>
                    <li class="client-download-update" v-if="!!clientNewVersion || !$Electron">
                        <Tooltip v-if="clientDownloadUrl && !$Electron" :content="$L('客户端下载')" placement="right" transfer :delay="300">
                            <a class="client-download common-right-bottom-link" :href="clientDownloadUrl" target="_blank">
                                <i class="taskfont">&#xe7fa;</i>
                            </a>
                        </Tooltip>
                        <Tooltip v-else-if="!!clientNewVersion && $Electron" :content="$L('更新客户端')" placement="right" transfer :delay="300">
                            <i class="taskfont"  @click="settingRoute('version')">&#xe7fb;</i>
                        </Tooltip>
                    </li>
                    <li @click="onAddShow">
                        <Tooltip :content="$L('新建项目') + ' ('+mateName+'+B)'" placement="right" transfer :delay="300">
                            <i class="taskfont">&#xe7b9;</i>
                        </Tooltip>
                    </li>
                    <li @click="onAddMenu('task')">
                        <Tooltip :content="$L('新建任务') + ' ('+mateName+'+K)'" placement="right" transfer :delay="300">
                            <i class="taskfont">&#xe7b5;</i>
                        </Tooltip>
                    </li>
                    <li @click="onAddMenu('createMeeting')">
                        <Tooltip :content="$L('新会议') + ' ('+mateName+'+J)'" placement="right" transfer :delay="300">
                            <i class="taskfont">&#xe7c1;</i>
                        </Tooltip>
                    </li>
                    <li @click="onAddMenu('joinMeeting')">
                        <Tooltip :content="$L('加入会议')" placement="right" transfer :delay="300">
                            <i class="taskfont">&#xe794;</i>
                        </Tooltip>
                    </li>
                </ul>
            </div>
        </div>

        <div class="manage-box-main">
            <keep-alive>
                <router-view class="manage-box-view" @on-click="onTabbarClick"></router-view>
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
                    <Select :value="0" @on-change="selectChange" :placeholder="$L('请选择模板')">
                        <Option v-for="(item, index) in columns" :value="index" :key="index">{{ item.name }}</Option>
                    </Select>
                </FormItem>
                <FormItem prop="flow" :label="$L('开启工作流')">
                    <RadioGroup v-model="addData.flow">
                        <Radio label="open">{{$L('开启')}}</Radio>
                        <Radio label="close">{{$L('关闭')}}</Radio>
                    </RadioGroup>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onAddProject">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--添加任务-->
        <Modal
            v-model="addTaskShow"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: '640px'
            }"
            footer-hide>
            <TaskAdd ref="addTask" v-model="addTaskShow"/>
        </Modal>

        <!--导出任务统计-->
        <TaskExport v-model="exportTaskShow"/>

        <!--导出签到数据-->
        <CheckinExport v-model="exportCheckinShow"/>

        <!--导出审批数据-->
        <ApproveExport v-model="exportApproveShow"/>

        <!--任务详情-->
        <TaskModal ref="taskModal"/>

        <!--聊天窗口（移动端）-->
        <DialogModal ref="dialogModal"/>

        <!--工作报告-->
        <DrawerOverlay
            v-model="workReportShow"
            placement="right"
            :size="1200">
            <Report v-if="workReportShow" v-model="reportTabs" @on-read="$store.dispatch('getReportUnread', 1000)" />
        </DrawerOverlay>

        <!--查看所有团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1380">
            <TeamManagement v-if="allUserShow"/>
        </DrawerOverlay>

        <!--查看所有项目-->
        <DrawerOverlay
            v-model="allProjectShow"
            placement="right"
            :size="1200">
            <ProjectManagement v-if="allProjectShow"/>
        </DrawerOverlay>

        <!--查看归档项目-->
        <DrawerOverlay
            v-model="archivedProjectShow"
            placement="right"
            :size="1200">
            <ProjectArchived v-if="archivedProjectShow"/>
        </DrawerOverlay>

        <!--会议管理-->
        <MeetingManager/>

        <!--移动端选项卡-->
        <transition name="mobile-slide">
            <MobileTabbar v-if="showMobileTabbar" @on-click="onTabbarClick"/>
        </transition>
        <MobileBack :showTabbar="showMobileTabbar"/>
        <MobileNotification ref="mobileNotification"/>

        <!-- okr明细 -->
        <MicroApps v-show="false" v-if="$route.name != 'manage-apps'" name="okr-details" :url="okrUrl" :datas="okrWindow"/>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ProjectArchived from "./manage/components/ProjectArchived";
import TeamManagement from "./manage/components/TeamManagement";
import ProjectManagement from "./manage/components/ProjectManagement";
import DrawerOverlay from "../components/DrawerOverlay";
import MobileTabbar from "../components/Mobile/Tabbar";
import TaskAdd from "./manage/components/TaskAdd";
import Report from "./manage/components/Report";
import MobileBack from "../components/Mobile/Back";
import MobileNotification from "../components/Mobile/Notification";
import MeetingManager from "./manage/components/MeetingManager";
import longpress from "../directives/longpress";
import DialogModal from "./manage/components/DialogModal";
import TaskModal from "./manage/components/TaskModal";
import CheckinExport from "./manage/components/CheckinExport";
import TaskExport from "./manage/components/TaskExport";
import ApproveExport from "./manage/components/ApproveExport";
import notificationKoro from "notification-koro1";
import {Store} from "le5le-store";
import MicroApps from "../components/MicroApps";

export default {
    components: {
        TaskExport,
        CheckinExport,
        ApproveExport,
        TaskModal,
        DialogModal,
        MeetingManager,
        MobileNotification,
        MobileBack,
        MobileTabbar,
        TaskAdd,
        Report,
        DrawerOverlay,
        ProjectManagement,
        TeamManagement,
        ProjectArchived,
        MicroApps
    },
    directives: {longpress},
    data() {
        return {
            loadIng: 0,

            mateName: /macintosh|mac os x/i.test(navigator.userAgent) ? '⌘' : 'Ctrl',

            addShow: false,
            addData: {
                name: '',
                columns: '',
                flow: 'open',
            },
            addRule: {
                name: [
                    { required: true, message: this.$L('请填写项目名称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('项目名称至少2个字！'), trigger: 'change' }
                ]
            },

            addTaskShow: false,
            addTaskSubscribe: null,

            exportTaskShow: false,
            exportCheckinShow: false,
            exportApproveShow: false,

            dialogMsgSubscribe: null,

            visibleMenu: false,
            showMobileMenu: false,

            workReportShow: false,
            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            natificationReady: false,
            notificationManage: null,

            reportTabs: "my",

            needStartHome: false,
        }
    },

    mounted() {
        this.notificationInit();
        //
        this.addTaskSubscribe = Store.subscribe('addTask', this.onAddTask);
        this.dialogMsgSubscribe = Store.subscribe('dialogMsgPush', this.addDialogMsg);
        //
        document.addEventListener('keydown', this.shortcutEvent);
    },

    activated() {
        this.$store.dispatch("getUserInfo").catch(_ => {})
        this.$store.dispatch("getTaskPriority").catch(_ => {})
        this.$store.dispatch("getReportUnread", 0)
        this.$store.dispatch("getApproveUnread", 0)
        //
        this.$store.dispatch("needHome").then(_ => {
            this.needStartHome = true
        }).catch(_ => {
            this.needStartHome = false
        })
    },

    beforeDestroy() {
        if (this.addTaskSubscribe) {
            this.addTaskSubscribe.unsubscribe();
            this.addTaskSubscribe = null;
        }
        if (this.dialogMsgSubscribe) {
            this.dialogMsgSubscribe.unsubscribe();
            this.dialogMsgSubscribe = null;
        }
        //
        document.removeEventListener('keydown', this.shortcutEvent);
    },

    deactivated() {
        this.addShow = false;
    },

    computed: {
        ...mapState([
            'userInfo',
            'userIsAdmin',
            'cacheUserBasic',
            'cacheTasks',
            'cacheDialogs',
            'wsOpenNum',
            'columnTemplate',

            'wsMsg',

            'clientNewVersion',
            'clientDownloadUrl',
            'cacheTaskBrowse',

            'dialogIns',

            'reportUnreadNumber',
            'approveUnreadNumber',

            'okrWindow'
        ]),

        ...mapGetters(['dashboardTask']),

        routeName() {
            return this.$route.name
        },

        // okr路由
        okrUrl() {
            return import.meta.env.VITE_OKR_WEB_URL || $A.apiUrl("../apps/okr")
        },

        /**
         * 综合数（未读、提及、待办）
         * @returns {string|string}
         */
        msgUnreadMention() {
            let num = 0;        // 未读
            let mention = 0;    // 提及
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog, false);
                mention += $A.getDialogMention(dialog);
            })
            if (num > 999) {
                num = "999+"
            }
            if (mention > 999) {
                mention = "999+"
            }
            const todoNum = this.msgTodoTotal   // 待办
            if (todoNum) {
                if (mention) {
                    return `@${mention}·${todoNum}`
                }
                if (num) {
                    return `${num}·${todoNum}`
                }
                return todoNum;
            }
            if (num) {
                if (mention) {
                    return `${num}·@${mention}`
                }
                return String(num)
            }
            if (mention) {
                return `@${mention}`
            }
            return "";
        },

        /**
         * 未读消息数
         * @returns {number}
         */
        msgAllUnread() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogNum(dialog);
            })
            return num;
        },

        /**
         * 待办消息数
         * @returns {string|null}
         */
        msgTodoTotal() {
            let todoNum = this.cacheDialogs.reduce((total, current) => total + (current.todo_num || 0), 0)
            if (todoNum > 0) {
                if (todoNum > 99) {
                    todoNum = "99+"
                } else if (todoNum === 1) {
                    todoNum = ""
                }
                return `${this.$L("待办")}${todoNum}`
            }
            return null;
        },

        /**
         * 未读消息 + 逾期任务
         * @returns {number|*}
         */
        unreadAndOverdue() {
            if (this.userId > 0) {
                return this.msgAllUnread + this.dashboardTask.overdue_count
            } else {
                return 0
            }
        },

        menu() {
            const {userIsAdmin, needStartHome} = this;
            const array = [
                {path: 'taskBrowse', name: '最近打开的任务'}
            ];
            if (userIsAdmin) {
                array.push(...[
                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'system', name: '系统设置'},
                    {path: 'license', name: 'License Key'},

                    {path: 'version', name: '更新版本', divided: true, visible: !!this.clientNewVersion},

                    {path: 'allProject', name: '所有项目', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},

                    {path: 'team', name: '团队管理', divided: true},
                ])
            } else {
                array.push(...[
                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'version', name: '更新版本', divided: true, visible: !!this.clientNewVersion},

                    {path: 'workReport', name: '工作报告', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},
                ])
            }
            if (needStartHome) {
                array.push(...[
                    {path: 'goHome', name: '打开首页', divided: true},
                    {path: 'clearCache', name: '清除缓存'},
                    {path: 'logout', name: '退出登录', style: {color: '#f40'}}
                ])
            } else {
                array.push(...[
                    {path: 'clearCache', name: '清除缓存', divided: true},
                    {path: 'logout', name: '退出登录', style: {color: '#f40'}}
                ])
            }
            return array
        },

        columns() {
            const array = $A.cloneJSON(this.columnTemplate);
            array.unshift({
                name: this.$L('空白模板'),
                columns: [],
            })
            return array
        },


        taskBrowseLists() {
            const {cacheTasks, cacheTaskBrowse, userId} = this;
            return cacheTaskBrowse.filter(({userid}) => userid === userId).map(({id}) => {
                return cacheTasks.find(task => task.id === id) || {}
            });
        },

        showMobileTabbar() {
            if (this.routeName === 'manage-project' && !/^\d+$/.test(this.$route.params.projectId)) {
                return true;
            }
            return ['manage-dashboard','manage-messenger', 'manage-application'].includes(this.routeName)
        },
    },

    watch: {
        '$route' () {
            this.chackPass();
        },

        userInfo() {
            this.chackPass();
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.$store.dispatch("getBasicData", 600)
        },

        workReportShow(show) {
            if (show) {
                this.$store.dispatch("getReportUnread", 0)
            }
        },

        unreadAndOverdue: {
            handler(val) {
                if (this.$Electron) {
                    this.$Electron.sendMessage('setDockBadge', val);
                }
            },
            immediate: true
        },

        wsMsg: {
            handler(info) {
                const {type, action} = info;
                switch (type) {
                    case 'report':
                        if (action == 'unreadUpdate') {
                            this.$store.dispatch("getReportUnread", 1000)
                        }
                        break;
                    case 'approve':
                        if (action == 'unread') {
                            this.$store.dispatch("getApproveUnread", 1000)
                        }
                        break;
                }
            },
            deep: true,
        },
    },

    methods: {
        chackPass() {
            if (this.userInfo.changepass === 1) {
                this.goForward({name: 'manage-setting-password'});
            }
        },

        async toggleRoute(path, params) {
            this.showMobileMenu = false;
            let location = {name: 'manage-' + path, params: params || {}};
            let fileFolderId = await $A.IDBInt("fileFolderId");
            if (path === 'file' && fileFolderId > 0) {
                location.params.folderId = fileFolderId
            }
            this.goForward(location);
        },

        settingRoute(path) {
            switch (path) {
                case 'allUser':
                    this.allUserShow = true;
                    return;
                case 'allProject':
                    this.allProjectShow = true;
                    return;
                case 'archivedProject':
                    this.archivedProjectShow = true;
                    return;
                case 'exportTask':
                    this.exportTaskShow = true;
                    return;
                case 'exportOverdueTask':
                    this.exportOverdueTask();
                    return;
                case 'exportCheckin':
                    this.exportCheckinShow = true;
                    return;
                case 'exportApprove':
                    this.exportApproveShow = true;
                    return;
                case 'workReport':
                    if (this.reportUnreadNumber > 0) {
                        this.reportTabs = "receive";
                    }
                    this.workReportShow = true;
                    return;
                case 'version':
                    Store.set('updateNotification', null);
                    return;
                case 'clearCache':
                    $A.IDBSet("clearCache", "handle").then(_ => {
                        $A.reloadUrl()
                    });
                    return;
                case 'goHome':
                    if (this.needStartHome) {
                        this.goForward('index');
                    }
                    return;
                case 'approve':
                    if (this.menu.findIndex((m) => m.path == path) > -1) {
                        this.goForward({name: 'manage-approve'});
                    }
                    return;
                case 'okrManage':
                case 'okrAnalyze':
                    this.goForward({
                        path:'/manage/apps/' + ( path == 'okrManage' ? '/#/list' : '/#/analysis'),
                    });
                    return;
                case 'logout':
                    $A.modalConfirm({
                        title: '退出登录',
                        content: '你确定要登出系统？',
                        onOk: () => {
                            this.$store.dispatch("logout", false)
                        }
                    });
                    return;
            }
            if (this.menu.findIndex((m) => m.path == path) > -1) {
                this.toggleRoute('setting-' + path);
            }
        },

        exportOverdueTask() {
            $A.modalConfirm({
                title: '导出任务',
                content: '你确定要导出所有超期任务吗？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'project/task/exportoverdue',
                        }).then(({data}) => {
                            resolve();
                            this.$store.dispatch('downUrl', {
                                url: data.url
                            });
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },

        menuVisibleChange(visible) {
            this.visibleMenu = visible
        },

        classNameRoute(path) {
            let routeName = this.routeName
            if(routeName == 'manage-approve' || routeName == 'manage-apps' || routeName == 'manage-calendar'){
                routeName = `manage-application`
            }
            return {
                "active": routeName === `manage-${path}`,
            };
        },

        onAddMenu(name) {
            switch (name) {
                case 'task':
                    this.onAddTask(0)
                    break;

                case 'createMeeting':
                    Store.set('addMeeting', {
                        type: 'create',
                        userids: [this.userId],
                    });
                    break;

                case 'joinMeeting':
                    Store.set('addMeeting', {
                        type: 'join',
                    });
                    break;
            }
        },

        onAddShow() {
            this.$store.dispatch("getColumnTemplate").catch(() => {})
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
                        this.addShow = false;
                        this.$refs.addProject.resetFields();
                        this.$store.dispatch("saveProject", data);
                        this.toggleRoute('project', {projectId: data.id})
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.loadIng--;
                    });
                }
            });
        },


        selectChange(index) {
            this.$nextTick(() => {
                this.$set(this.addData, 'columns', this.columns[index].columns.join(','));
            })
        },

        shortcutEvent(e) {
            if (e.metaKey || e.ctrlKey) {
                switch (e.keyCode) {
                    case 66: // B - 新建项目
                        e.preventDefault();
                        this.onAddShow()
                        break;

                    case 74: // J - 新会议
                        e.preventDefault();
                        this.onAddMenu('createMeeting')
                        break;

                    case 75:
                    case 78: // K/N - 加入会议
                        e.preventDefault();
                        this.onAddMenu('task')
                        break;

                    case 83: // S - 保存任务
                        if (this.$refs.taskModal.checkUpdate()) {
                            e.preventDefault();
                        }
                        break;

                    case 188: // , - 进入设置
                        e.preventDefault();
                        this.toggleRoute('setting')
                        break;
                }
            }
        },

        onAddTask(params) {
            this.addTaskShow = true
            this.$nextTick(_ => {
                let data = {
                    owner: [this.userId],
                }
                if ($A.isJson(params)) {
                    data = params
                } else if (/^[1-9]\d*$/.test(params)) {
                    data.column_id = params
                }
                this.$refs.addTask.setData(data)
            })
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        addDialogMsg(data) {
            if (!this.natificationReady && !this.$isEEUiApp) {
                return; // 通知未准备好不通知
            }
            if (this.windowActive && data.dialog_id === $A.last(this.dialogIns)?.dialog_id) {
                return; // 窗口激活且最后打开的会话是通知的会话时不通知
            }
            //
            const {id, dialog_id, dialog_type, type, msg, userid} = data;
            if (userid == this.userId) {
                return; // 自己的消息不通知
            }
            let body;
            switch (type) {
                case 'text':
                    body = $A.getMsgTextPreview(msg.text)
                    break;
                case 'file':
                    body = '[' + this.$L(msg.type == 'img' ? '图片信息' : '文件信息') + ']'
                    break;
                default:
                    return;
            }
            this.__notificationId = id;
            const notificationFuncA = (title) => {
                if (dialog_type === 'group') {
                    let tempUser = this.cacheUserBasic.find(item => item.userid == userid);
                    if (tempUser) {
                        notificationFuncB(`${title} (${tempUser.nickname})`)
                    } else {
                        this.$store.dispatch("call", {
                            url: 'users/basic',
                            data: {
                                userid: [userid]
                            },
                            skipAuthError: true
                        }).then(({data}) => {
                            tempUser = data.find(item => item.userid == userid);
                            if (tempUser) {
                                notificationFuncB(`${title} (${tempUser.nickname})`)
                            }
                        }).catch(_ => {
                            notificationFuncB(title)
                        });
                    }
                } else {
                    notificationFuncB(title)
                }
            }
            const notificationFuncB = (title) => {
                if (this.__notificationId === id) {
                    if (this.$isEEUiApp) {
                        this.$refs.mobileNotification.open({
                            userid: userid,
                            title,
                            desc: body,
                            callback: () => {
                                this.goForward({name: 'manage-messenger'});
                                this.$store.dispatch('openDialog', dialog_id)
                            }
                        })
                    } else if (this.$Electron) {
                        this.$Electron.sendMessage('openNotification', {
                            icon: $A.originUrl('images/logo.png'),
                            title,
                            body,
                            data,
                            tag: "dialog",
                            hasReply: true,
                            replyPlaceholder: this.$L('回复消息')
                        })
                    } else {
                        this.notificationManage.replaceOptions({
                            icon: $A.originUrl('images/logo.png'),
                            body: body,
                            data: data,
                            tag: "dialog",
                            // requireInteraction: true // true为通知不自动关闭
                        });
                        this.notificationManage.replaceTitle(title);
                        this.notificationManage.userAgreed();
                    }
                }
            }
            const dialog = this.cacheDialogs.find((item) => item.id == dialog_id);
            if (dialog) {
                notificationFuncA(dialog.name)
            } else {
                this.$store.dispatch("getDialogOne", dialog_id).then(({data}) => notificationFuncA(data.name)).catch(() => {})
            }
        },

        onTabbarClick(act) {
            switch (act) {
                case 'addTask':
                    this.onAddTask(0)
                    break;
                case 'addProject':
                    this.onAddShow()
                    break;
                case 'allUser':
                case 'workReport':
                    this.settingRoute(act)
                    break;
            }
        },

        /**
         * 初始化通知
         */
        notificationInit() {
            this.notificationManage = new notificationKoro(this.$L("打开通知成功"));
            if (this.notificationManage.support) {
                this.notificationManage.notificationEvent({
                    onclick: ({target}) => {
                        console.log("[Notification] A Click", target);
                        this.notificationManage.close();
                        this.notificationClick(target)
                        window.focus()
                    },
                });
                this.notificationPermission();
            }
            //
            if (this.$Electron) {
                this.$Electron.registerMsgListener('clickNotification', target => {
                    console.log("[Notification] B Click", target);
                    this.$Electron.sendMessage('mainWindowActive')
                    this.notificationClick(target)
                })
                this.$Electron.registerMsgListener('replyNotification', target => {
                    console.log("[Notification] B Reply", target);
                    this.notificationReply(target)
                })
            }
        },

        /**
         * 通知权限
         */
        notificationPermission() {
            const userSelectFn = msg => {
                switch (msg) {
                    // 随时可以调用通知
                    case 'already granted':
                    case 'granted':
                        return this.natificationReady = true;

                    // 请求权限通知被关闭，再次调用
                    case 'close':
                        return this.notificationManage.initNotification(userSelectFn);

                    // 请求权限当前被拒绝 || 曾经被拒绝
                    case 'denied':
                    case 'already denied':
                        if (msg === "denied") {
                            console.log("您刚刚拒绝显示通知 请在设置中更改设置");
                        } else {
                            console.log("您曾级拒绝显示通知 请在设置中更改设置");
                        }
                        break;
                }
            };
            this.notificationManage.initNotification(userSelectFn);
        },

        /**
         * 点击通知（客户端）
         * @param target
         */
        notificationClick(target) {
            const {tag, data} = target;
            if (tag == 'dialog') {
                if (!$A.isJson(data)) {
                    return;
                }
                this.goForward({name: 'manage-messenger'});
                this.$nextTick(_ => {
                    this.$store.dispatch('openDialog', data.dialog_id)
                })
            }
        },

        /**
         * 回复通知（客户端）
         * @param target
         */
        notificationReply(target) {
            const {tag, data, reply} = target;
            if (tag == 'dialog' && reply) {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/sendtext',
                    data: {
                        dialog_id: data.dialog_id,
                        text: reply,
                    },
                    method: 'post',
                }).then(({data}) => {
                    this.$store.dispatch("saveDialogMsg", data);
                    this.$store.dispatch("increaseTaskMsgNum", data);
                    this.$store.dispatch("increaseMsgReplyNum", data);
                    this.$store.dispatch("updateDialogLastMsg", data);
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            }
        },
    }
}
</script>
