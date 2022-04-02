<template>
    <div v-show="userId > 0" class="page-manage">
        <div class="manage-box-menu" :class="{'show768-menu': show768Menu}">
            <Dropdown
                class="page-manage-menu-dropdown"
                trigger="click"
                @on-click="settingRoute"
                @on-visible-change="menuVisibleChange">
                <div :class="['manage-box-title', visibleMenu ? 'menu-visible' : '']">
                    <div class="manage-box-avatar">
                        <UserAvatar :userid="userId" :size="36" tooltipDisabled/>
                    </div>
                    <span>{{userInfo.nickname}}</span>
                    <Badge v-if="reportUnreadNumber > 0" class="manage-box-top-report" :count="reportUnreadNumber"/>
                    <Badge v-else-if="!!clientNewVersion" class="manage-box-top-report" dot/>
                    <div class="manage-box-arrow">
                        <Icon type="ios-arrow-up" />
                        <Icon type="ios-arrow-down" />
                    </div>
                </div>
                <DropdownMenu slot="list">
                    <template v-for="item in menu">
                        <!--最近打开的任务-->
                        <Dropdown
                            v-if="item.path === 'taskBrowse'"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            placement="right-start">
                            <DropdownItem>
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
                                    :name="item.name">{{ item.name }}</DropdownItem>
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
                            placement="right-start">
                            <DropdownItem divided>
                                <div class="manage-menu-flex">
                                    {{$L(item.name)}}
                                    <Badge v-if="reportUnreadNumber > 0" class="manage-menu-report-badge" :count="reportUnreadNumber"/>
                                    <Icon v-else type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem name="allUser">{{$L('团队管理')}}</DropdownItem>
                                <DropdownItem name="workReport">
                                    <div class="manage-menu-flex">
                                        {{$L('工作报告')}}
                                        <Badge v-if="reportUnreadNumber > 0" class="manage-menu-report-badge" :count="reportUnreadNumber"/>
                                    </div>
                                </DropdownItem>
                                <DropdownItem name="exportTask">{{$L('导出任务统计')}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 主题皮肤 -->
                        <Dropdown
                            v-else-if="item.path === 'theme'"
                            placement="right-start"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            @on-click="setTheme">
                            <DropdownItem divided>
                                <div class="manage-menu-flex">
                                    {{$L(item.name)}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem
                                    v-for="(item, key) in themeList"
                                    :key="key"
                                    :name="item.value"
                                    :selected="themeMode === item.value">{{$L(item.name)}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 语言设置 -->
                        <Dropdown
                            v-else-if="item.path === 'language'"
                            placement="right-start"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            @on-click="setLanguage">
                            <DropdownItem divided>
                                <div class="manage-menu-flex">
                                    {{currentLanguage}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem
                                    v-for="(item, key) in languageList"
                                    :key="key"
                                    :name="key"
                                    :selected="getLanguage() === key">{{item}}</DropdownItem>
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
                            </div>
                        </DropdownItem>
                    </template>
                </DropdownMenu>
            </Dropdown>
            <ul :class="overlayClass" @scroll="handleClickTopOperateOutside">
                <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                    <i class="taskfont">&#xe6fb;</i>
                    <div class="menu-title">{{$L('仪表盘')}}</div>
                    <Badge v-if="dashboardTask.overdue.length > 0" class="menu-badge" type="error" :count="dashboardTask.overdue.length"/>
                    <Badge v-else-if="dashboardTask.today.length > 0" class="menu-badge" type="info" :count="dashboardTask.today.length"/>
                    <Badge v-else-if="dashboardTask.all.length > 0" class="menu-badge" type="primary" :count="dashboardTask.all.length"/>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <i class="taskfont">&#xe6f5;</i>
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <i class="taskfont">&#xe6eb;</i>
                    <div class="menu-title">{{$L('消息')}}</div>
                    <Badge class="menu-badge" :count="msgAllUnread"/>
                </li>
                <li @click="toggleRoute('file')" :class="classNameRoute('file')">
                    <i class="taskfont">&#xe6f3;</i>
                    <div class="menu-title">{{$L('文件')}}</div>
                </li>
                <li ref="projectWrapper" class="menu-project">
                    <ul :class="overlayClass" @scroll="handleClickTopOperateOutside">
                        <li
                            v-for="(item, key) in projectLists"
                            :key="key"
                            :class="classNameProject(item)"
                            @click="toggleRoute('project/' + item.id)"
                            @contextmenu.prevent.stop="handleRightClick($event, item)">
                            <div class="project-h1">
                                <em @click.stop="toggleOpenMenu(item.id)"></em>
                                <div class="title">{{item.name}}</div>
                                <div v-if="item.top_at" class="icon-top"></div>
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
                    <div class="top-operate" :style="topOperateStyles">
                        <Dropdown
                            trigger="custom"
                            :visible="topOperateVisible"
                            transfer-class-name="page-file-dropdown-menu"
                            @on-clickoutside="handleClickTopOperateOutside"
                            transfer>
                            <DropdownMenu slot="list">
                                <DropdownItem @click.native="handleTopClick">
                                    {{ $L(topOperateItem.top_at ? '取消置顶' : '置顶该项目') }}
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </li>
            </ul>
            <div
                v-if="projectTotal > 20"
                class="manage-project-search"
                :class="{loading:projectKeyLoading > 0}">
                <Input prefix="ios-search" v-model="projectKeyValue" :placeholder="$L('共' + projectTotal + '个项目，搜索...')" clearable />
            </div>
            <ButtonGroup class="manage-box-new-group">
                <Button class="manage-box-new" type="primary" icon="md-add" @click="onAddShow">{{$L('新建项目')}}</Button>
                <Dropdown @on-click="onAddTask(0)">
                    <Button type="primary">
                        <Icon type="ios-arrow-down"></Icon>
                    </Button>
                    <DropdownMenu slot="list">
                        <DropdownItem>{{$L('新建任务')}} ({{mateName}}+K)</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </ButtonGroup>
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
        <Modal
            v-model="exportTaskShow"
            :title="$L('导出任务统计')"
            :mask-closable="false">
            <Form ref="exportTask" :model="exportData" label-width="auto" @submit.native.prevent>
                <FormItem :label="$L('导出会员')">
                    <UserInput v-model="exportData.userid" :multiple-max="20" :placeholder="$L('请选择会员')"/>
                </FormItem>
                <FormItem :label="$L('时间范围')">
                    <DatePicker
                        v-model="exportData.time"
                        type="daterange"
                        format="yyyy/MM/dd"
                        style="width:100%"
                        :placeholder="$L('请选择时间')"/>
                </FormItem>
                <FormItem prop="type" :label="$L('导出时间类型')">
                    <RadioGroup v-model="exportData.type">
                        <Radio label="taskTime">{{$L('任务时间')}}</Radio>
                        <Radio label="createdTime">{{$L('创建时间')}}</Radio>
                    </RadioGroup>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="exportTaskShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="exportLoadIng > 0" @click="onExportTask">{{$L('导出')}}</Button>
            </div>
        </Modal>

        <!--任务详情-->
        <Modal
            :value="taskId > 0"
            :styles="{
                width: '90%',
                maxWidth: taskData.dialog_id ? '1200px' : '700px'
            }"
            :mask-closable="false"
            :footer-hide="true"
            @on-visible-change="taskVisibleChange">
            <div class="page-manage-task-modal" :style="taskStyle">
                <TaskDetail ref="taskDetail" :task-id="taskId" :open-task="taskData"/>
            </div>
        </Modal>

        <!--工作报告-->
        <DrawerOverlay
            v-model="workReportShow"
            placement="right"
            :size="1100">
            <Report v-if="workReportShow" :reportType="reportTabs" :reportUnreadNumber="reportUnreadNumber" @on-read="getReportUnread" />
        </DrawerOverlay>

        <!--查看所有团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1100">
            <TeamManagement v-if="allUserShow"/>
        </DrawerOverlay>

        <!--查看所有项目-->
        <DrawerOverlay
            v-model="allProjectShow"
            placement="right"
            :size="1100">
            <ProjectManagement v-if="allProjectShow"/>
        </DrawerOverlay>

        <!--查看归档项目-->
        <DrawerOverlay
            v-model="archivedProjectShow"
            placement="right"
            :size="1100">
            <ProjectArchived v-if="archivedProjectShow"/>
        </DrawerOverlay>

        <!--菜单按钮-->
        <DragBallComponent
            :distanceLeft="0"
            :distanceTop="60"
            @on-click="show768Menu=!show768Menu">
            <div class="manage-mini-menu">
                <Icon :type="show768Menu ? 'md-close' : 'md-menu'" />
                <Badge :count="unreadTotal"/>
            </div>
        </DragBallComponent>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import TaskDetail from "./manage/components/TaskDetail";
import ProjectArchived from "./manage/components/ProjectArchived";
import TeamManagement from "./manage/components/TeamManagement";
import ProjectManagement from "./manage/components/ProjectManagement";
import DrawerOverlay from "../components/DrawerOverlay";
import DragBallComponent from "../components/DragBallComponent";
import TaskAdd from "./manage/components/TaskAdd";
import Report from "./manage/components/Report";
import notificationKoro from "notification-koro1";
import {Store} from "le5le-store";
import UserInput from "../components/UserInput";

export default {
    components: {
        UserInput,
        TaskAdd,
        TaskDetail,
        Report,
        DragBallComponent,
        DrawerOverlay,
        ProjectManagement,
        TeamManagement,
        ProjectArchived},
    data() {
        return {
            loadIng: 0,

            curPath: this.$route.path,
            mateName: /macintosh|mac os x/i.test(navigator.userAgent) ? '⌘' : 'Ctrl',

            addShow: false,
            addData: {
                name: '',
                columns: '',
                flow: 'open',
            },
            addRule: {},

            addTaskShow: false,
            addTaskSubscribe: null,

            exportTaskShow: false,
            exportLoadIng: 0,
            exportData: {
                userid: [],
                time: [],
                type:'taskTime',
            },

            dialogMsgSubscribe: null,

            projectKeyValue: '',
            projectKeyAlready: {},
            projectKeyLoading: 0,

            openMenu: {},
            visibleMenu: false,
            show768Menu: false,
            innerHeight: window.innerHeight,

            workReportShow: false,
            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            natificationHidden: false,
            natificationReady: false,
            notificationClass: null,

            reportTabs: "my",
            reportUnreadNumber: 0,

            topOperateStyles: {},
            topOperateVisible: false,
            topOperateItem: {},
        }
    },

    mounted() {
        if ($A.getStorageString("clearCache")) {
            $A.setStorage("clearCache", "")
            $A.messageSuccess("清除成功");
        }
        //
        this.$store.dispatch("getUserInfo").catch(() => {})
        this.$store.dispatch("getTaskPriority").catch(() => {})
        //
        this.getReportUnread(0);
        this.notificationInit();
        this.onVisibilityChange();
        //
        this.addTaskSubscribe = Store.subscribe('addTask', this.onAddTask);
        this.dialogMsgSubscribe = Store.subscribe('dialogMsgPush', this.addDialogMsg);
        //
        document.addEventListener('keydown', this.shortcutEvent);
        window.addEventListener('resize', this.innerHeightListener);
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
        window.removeEventListener('resize', this.innerHeightListener);
    },

    deactivated() {
        this.addShow = false;
    },

    computed: {
        ...mapState([
            'userId',
            'userInfo',
            'userIsAdmin',
            'cacheTasks',
            'cacheDialogs',
            'cacheProjects',
            'projectTotal',
            'taskId',
            'wsOpenNum',
            'columnTemplate',
            'dialogOpenId',

            'themeMode',
            'themeList',

            'wsMsg',

            'clientNewVersion',
            'cacheTaskBrowse',
        ]),

        ...mapGetters(['taskData', 'dashboardTask']),

        msgAllUnread() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                let unread = $A.getDialogUnread(dialog);
                if (unread) {
                    num += unread;
                }
            })
            return num;
        },

        unreadTotal() {
            if (this.userId > 0) {
                return this.msgAllUnread + this.dashboardTask.overdue.length + this.reportUnreadNumber
            } else {
                return 0
            }
        },

        currentLanguage() {
            return this.languageList[this.languageType] || 'Language'
        },

        menu() {
            const {userIsAdmin} = this;
            if (userIsAdmin) {
                return [
                    {path: 'taskBrowse', name: '最近打开的任务'},

                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'password', name: '密码设置'},
                    {path: 'clearCache', name: '清除缓存'},

                    {path: 'system', name: '系统设置', divided: true},
                    {path: 'version', name: '更新版本', visible: !!this.clientNewVersion},

                    {path: 'allProject', name: '所有项目', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},

                    {path: 'team', name: '团队管理', divided: true},

                    {path: 'theme', name: '主题皮肤', divided: true},

                    {path: 'language', name: this.currentLanguage, divided: true},

                    {path: 'logout', name: '退出登录', style: {color: '#f40'}, divided: true},
                ]
            } else {
                return [
                    {path: 'taskBrowse', name: '最近打开的任务'},

                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'password', name: '密码设置'},
                    {path: 'clearCache', name: '清除缓存'},

                    {path: 'version', name: '更新版本', divided: true, visible: !!this.clientNewVersion},

                    {path: 'workReport', name: '工作报告', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},

                    {path: 'theme', name: '主题皮肤', divided: true},

                    {path: 'language', name: this.currentLanguage, divided: true},

                    {path: 'logout', name: '退出登录', style: {color: '#f40'}, divided: true},
                ]
            }
        },

        columns() {
            const array = $A.cloneJSON(this.columnTemplate);
            array.unshift({
                name: this.$L('空白模板'),
                columns: [],
            })
            return array
        },

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(({name}) => name.toLowerCase().indexOf(projectKeyValue.toLowerCase()) > -1);
            }
            return data;
        },

        taskStyle() {
            const {innerHeight} = this;
            return {
                maxHeight: (innerHeight - (innerHeight > 900 ? 200 : 70) - 20) + 'px'
            }
        },

        overlayClass() {
            return {
                'overlay-y': true,
                'overlay-none': this.topOperateVisible === true,
            }
        },

        taskBrowseLists() {
            const {cacheTasks, cacheTaskBrowse, userId} = this;
            return cacheTaskBrowse.filter(({userid}) => userid === userId).map(({id}) => {
                return cacheTasks.find(task => task.id === id) || {}
            });
        },
    },

    watch: {
        '$route' (route) {
            this.curPath = route.path;
            this.chackPass();
        },

        userInfo() {
            this.chackPass();
        },

        taskId(id) {
            if (id > 0) {
                this.$Modal.resetIndex();
            }
        },

        projectKeyValue(val) {
            if (val == '') {
                return;
            }
            setTimeout(() => {
                if (this.projectKeyValue == val) {
                    this.searchProject();
                }
            }, 600);
        },

        natificationHidden(val) {
            clearTimeout(this.notificationTimeout);
            if (!val && this.notificationClass) {
                this.notificationTimeout = setTimeout(() => {
                    this.notificationClass.close();
                }, 6000);
            }
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                this.$store.dispatch("getBasicData")
                this.getReportUnread()
            }, 5000)
        },

        workReportShow(show) {
            if (show) {
                this.getReportUnread(0);
            }
        },

        unreadTotal: {
            handler(num) {
                if (this.$Electron) {
                    this.$Electron.sendMessage('setDockBadge', num);
                }
            },
            immediate: true
        },

        userId: {
            handler() {
                this.$store.dispatch("websocketConnection")
            },
            immediate: true
        },

        wsMsg: {
            handler(info) {
                const {type, action} = info;
                switch (type) {
                    case 'report':
                        if (action == 'unreadUpdate') {
                            this.getReportUnread()
                        }
                        break;
                }
            },
            deep: true,
        },
    },

    methods: {
        initLanguage() {
            this.addRule = {
                name: [
                    { required: true, message: this.$L('请填写项目名称！'), trigger: 'change' },
                    { type: 'string', min: 2, message: this.$L('项目名称至少2个字！'), trigger: 'change' }
                ]
            };
        },

        innerHeightListener() {
            this.innerHeight = window.innerHeight;
        },

        chackPass() {
            if (this.userInfo.changepass === 1) {
                this.goForward({name: 'manage-setting-password'});
            }
        },

        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
        },

        toggleRoute(path) {
            this.show768Menu = false;
            this.goForward({path: '/manage/' + path});
        },

        toggleOpenMenu(id) {
            this.$set(this.openMenu, id, !this.openMenu[id])
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
                    this.$store.dispatch("handleClearCache", null).then(() => {
                        $A.setStorage("clearCache", $A.randomString(6))
                        window.location.reload()
                    }).catch(() => {
                        window.location.reload()
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
                this.toggleRoute('setting/' + path);
            }
        },

        menuVisibleChange(visible) {
            this.visibleMenu = visible
        },

        classNameRoute(path) {
            return {
                "active": this.curPath == '/manage/' + path,
            };
        },

        classNameProject(item) {
            let path = 'project/' + item.id;
            let openMenu = this.openMenu[item.id];
            return {
                "active": this.curPath == '/manage/' + path,
                "open-menu": openMenu === true,
                "operate": item.id == this.topOperateItem.id && this.topOperateVisible
            };
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

        searchProject() {
            if (this.projectKeyAlready[this.projectKeyValue] === true) {
                return;
            }
            this.projectKeyAlready[this.projectKeyValue] = true;
            //
            setTimeout(() => {
                this.projectKeyLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                }
            }).then(() => {
                this.projectKeyLoading--;
            }).catch(() => {
                this.projectKeyLoading--;
            });
        },

        selectChange(index) {
            this.$nextTick(() => {
                this.$set(this.addData, 'columns', this.columns[index].columns.join(','));
            })
        },

        shortcutEvent(e) {
            if (e.metaKey || e.ctrlKey) {
                if (e.keyCode === 75 || e.keyCode === 78) {
                    e.preventDefault();
                    this.onAddTask(0)
                } else if (e.keyCode === 83 && this.taskId > 0) {
                    e.preventDefault();
                    this.$refs.taskDetail.checkUpdate(true)
                }
            }
        },

        onAddTask(data) {
            this.$refs.addTask.defaultPriority();
            this.$refs.addTask.setData($A.isJson(data) ? data : {
                'owner': [this.userId],
                'column_id': data,
            });
            this.addTaskShow = true;
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        addDialogMsg(data) {
            if (!this.natificationReady) {
                return;
            }
            if (!this.natificationHidden && this.curPath == "/manage/messenger" && this.dialogOpenId == data.dialog_id) {
                return;
            }
            //
            const {id, dialog_id, type, msg} = data;
            let body = '';
            switch (type) {
                case 'text':
                    body = msg.text;
                    break;
                case 'file':
                    body = '[' + this.$L(msg.type == 'img' ? '图片信息' : '文件信息') + ']'
                    break;
                default:
                    return;
            }
            this._notificationId = id;
            this.notificationClass.replaceOptions({
                icon: $A.originUrl('images/logo.png'),
                body: body,
                data: data,
                tag: "dialog",
                requireInteraction: true
            });
            let dialog = this.cacheDialogs.find((item) => item.id == dialog_id);
            if (dialog) {
                this.notificationClass.replaceTitle(dialog.name);
                this.notificationClass.userAgreed();
            } else {
                this.$store.dispatch("getDialogOne", dialog_id).then(({data}) => {
                    if (this._notificationId === id) {
                        this.notificationClass.replaceTitle(data.name);
                        this.notificationClass.userAgreed();
                    }
                }).catch(() => {})
            }
        },

        taskVisibleChange(visible) {
            if (!visible) {
                this.openTask(0)
            }
        },

        getReportUnread(timeout) {
            this.reportUnreadTimeout && clearTimeout(this.reportUnreadTimeout)
            this.reportUnreadTimeout = setTimeout(() => {
                this.$store.dispatch("call", {
                    url: 'report/unread',
                }).then(({data}) => {
                    this.reportUnreadNumber = data.total || 0;
                }).catch(() => {});
            }, typeof timeout === "number" ? timeout : 1000)
        },

        handleRightClick(event, item) {
            this.handleClickTopOperateOutside();
            this.topOperateItem = item;
            this.$nextTick(() => {
                const projectWrap = this.$refs.projectWrapper;
                const projectBounding = projectWrap.getBoundingClientRect();
                this.topOperateStyles = {
                    left: `${event.clientX - projectBounding.left}px`,
                    top: `${event.clientY - projectBounding.top}px`
                };
                this.topOperateVisible = true;
            })
        },

        handleClickTopOperateOutside() {
            this.topOperateVisible = false;
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'project/top',
                data: {
                    project_id: this.topOperateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveProject", data);
                this.$nextTick(() => {
                    let active = this.$refs.projectWrapper.querySelector(".active")
                    if (active) {
                        $A.scrollToView(active, {
                            behavior: 'instant',
                            scrollMode: 'if-needed',
                        });
                    }
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        onExportTask() {
            if (this.exportLoadIng > 0) {
                return;
            }
            this.exportLoadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/export',
                data: this.exportData,
            }).then(({data}) => {
                this.exportLoadIng--;
                this.exportTaskShow = false;
                this.$store.dispatch('downUrl', {
                    url: data.url
                });
            }).catch(({msg}) => {
                this.exportLoadIng--;
                $A.modalError(msg);
            });
        },

        notificationInit() {
            this.notificationClass = new notificationKoro(this.$L("打开通知成功"));
            if (this.notificationClass.support) {
                this.notificationClass.notificationEvent({
                    onclick: ({target}) => {
                        console.log("[Notification] Click", target);
                        this.notificationClass.close();
                        window.focus();
                        //
                        const {tag, data} = target;
                        if (tag == 'dialog') {
                            if (!$A.isJson(data)) {
                                return;
                            }
                            this.goForward({name: 'manage-messenger'});
                            if (data.dialog_id) {
                                $A.setStorage("messenger::dialogId", data.dialog_id)
                                this.$store.state.dialogOpenId = data.dialog_id;
                            }
                        }
                    },
                });
                this.notificationPermission();
            }
        },

        notificationPermission() {
            const userSelectFn = msg => {
                switch (msg) {
                    // 随时可以调用通知
                    case 'already granted':
                    case 'granted':
                        return this.natificationReady = true;

                    // 请求权限通知被关闭，再次调用
                    case 'close':
                        return this.notificationClass.initNotification(userSelectFn);

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
            this.notificationClass.initNotification(userSelectFn);
        },

        onVisibilityChange() {
            let hiddenProperty = 'hidden' in document ? 'hidden' : 'webkitHidden' in document ? 'webkitHidden' : 'mozHidden' in document ? 'mozHidden' : null;
            let visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');
            let visibilityChangeListener = () => {
                this.natificationHidden = !!document[hiddenProperty]
            }
            document.addEventListener(visibilityChangeEvent, visibilityChangeListener);
        },
    }
}
</script>
