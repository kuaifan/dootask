<template>
    <div v-show="userId > 0" class="page-manage" :class="{'show-tabbar': showMobileTabbar}">
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
            <ul :class="listClassName" @scroll="operateVisible = false">
                <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                    <i class="taskfont">&#xe6fb;</i>
                    <div class="menu-title">{{$L('仪表盘')}}</div>
                    <Badge v-if="dashboardTask.overdue_count > 0" class="menu-badge" type="error" :count="dashboardTask.overdue_count"/>
                    <Badge v-else-if="dashboardTask.today_count > 0" class="menu-badge" type="info" :count="dashboardTask.today_count"/>
                    <Badge v-else-if="dashboardTask.all_count > 0" class="menu-badge" type="primary" :count="dashboardTask.all_count"/>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <i class="taskfont">&#xe6f5;</i>
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <i class="taskfont">&#xe6eb;</i>
                    <div class="menu-title">{{$L('消息')}}</div>
                    <Badge class="menu-badge" :text="msgUnreadMention"/>
                </li>
                <li @click="toggleRoute('file')" :class="classNameRoute('file')">
                    <i class="taskfont">&#xe6f3;</i>
                    <div class="menu-title">{{$L('文件')}}</div>
                </li>
                <li ref="menuProject" class="menu-project">
                    <ul :class="listClassName" @scroll="operateVisible = false">
                        <li
                            v-for="(item, key) in projectLists"
                            :ref="`project_${item.id}`"
                            :key="key"
                            :class="classNameProject(item)"
                            :data-id="item.id"
                            @click="toggleRoute('project', {projectId: item.id})"
                            v-longpress="handleLongpress">
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
                        <li v-if="projectKeyLoading > 0" class="loading"><Loading/></li>
                    </ul>
                </li>
            </ul>
            <div class="operate-position" :style="operateStyles">
                <Dropdown
                    trigger="custom"
                    :placement="windowLarge ? 'bottom' : 'top'"
                    :visible="operateVisible"
                    @on-clickoutside="operateVisible = false"
                    transfer>
                    <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                    <DropdownMenu slot="list">
                        <DropdownItem @click.native="handleTopClick">
                            {{ $L(operateItem.top_at ? '取消置顶' : '置顶该项目') }}
                        </DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </div>
            <div
                v-if="(projectSearchShow || projectTotal > 20) && windowHeight > 600"
                class="manage-project-search">
                <Input v-model="projectKeyValue" :placeholder="$L(`共${projectTotal || cacheProjects.length}个项目，搜索...`)" clearable>
                    <div class="search-pre" slot="prefix">
                        <Loading v-if="projectKeyLoading > 0"/>
                        <Icon v-else type="ios-search" />
                    </div>
                </Input>
            </div>
            <ButtonGroup class="manage-box-new-group">
                <Button class="manage-box-new" type="primary" icon="md-add" @click="onAddShow">{{$L('新建项目')}}</Button>
                <Dropdown @on-click="onAddMenu" trigger="click">
                    <Button type="primary">
                        <Icon type="ios-arrow-down"></Icon>
                    </Button>
                    <DropdownMenu slot="list">
                        <DropdownItem name="task">{{$L('新建任务')}} ({{mateName}}+K)</DropdownItem>
                        <DropdownItem name="createMeeting">{{$L('新会议')}} ({{mateName}}+J)</DropdownItem>
                        <DropdownItem name="joinMeeting">{{$L('加入会议')}}</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </ButtonGroup>
        </div>

        <div class="manage-box-main">
            <keep-alive>
                <router-view class="manage-box-view"></router-view>
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
        <TaskModal ref="taskModal"/>

        <!--聊天窗口（移动端）-->
        <DialogModal ref="dialogModal"/>

        <!--工作报告-->
        <DrawerOverlay
            v-model="workReportShow"
            placement="right"
            :size="1200">
            <Report v-if="workReportShow" :reportType="reportTabs" :reportUnreadNumber="reportUnreadNumber" @on-read="getReportUnread" />
        </DrawerOverlay>

        <!--查看所有团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1200">
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
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ProjectArchived from "./manage/components/ProjectArchived";
import TeamManagement from "./manage/components/TeamManagement";
import ProjectManagement from "./manage/components/ProjectManagement";
import DrawerOverlay from "../components/DrawerOverlay";
import MobileTabbar from "../components/Mobile/Tabbar";
import UserInput from "../components/UserInput";
import TaskAdd from "./manage/components/TaskAdd";
import Report from "./manage/components/Report";
import MobileBack from "../components/Mobile/Back";
import MobileNotification from "../components/Mobile/Notification";
import MeetingManager from "./manage/components/MeetingManager";
import longpress from "../directives/longpress";
import DialogModal from "./manage/components/DialogModal";
import TaskModal from "./manage/components/TaskModal";
import notificationKoro from "notification-koro1";
import {Store} from "le5le-store";

export default {
    components: {
        TaskModal,
        DialogModal,
        MeetingManager,
        MobileNotification,
        MobileBack,
        MobileTabbar,
        UserInput,
        TaskAdd,
        Report,
        DrawerOverlay,
        ProjectManagement,
        TeamManagement,
        ProjectArchived},
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
            projectKeyLoading: 0,
            projectSearchShow: false,

            openMenu: {},
            visibleMenu: false,
            show768Menu: false,

            workReportShow: false,
            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            natificationReady: false,
            notificationManage: null,

            reportTabs: "my",
            reportUnreadNumber: 0,

            operateStyles: {},
            operateVisible: false,
            operateItem: {},
        }
    },

    mounted() {
        if ($A.getStorageString("clearCache")) {
            $A.setStorage("clearCache", "")
            $A.messageSuccess("清除成功");
        }
        //
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
        this.getReportUnread(0);
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
            'cacheTasks',
            'cacheDialogs',
            'cacheProjects',
            'projectTotal',
            'wsOpenNum',
            'columnTemplate',

            'themeMode',
            'themeList',

            'wsMsg',

            'clientNewVersion',
            'cacheTaskBrowse',

            'dialogIns',
        ]),

        ...mapGetters(['dashboardTask']),

        routeName() {
            return this.$route.name
        },

        msgUnreadMention() {
            if (this.cacheDialogs.find(item => item.has_todo)) {
                return this.$L("待办")
            }
            let num = 0;
            let mention = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog);
                mention += $A.getDialogMention(dialog);
            })
            if (num <= 0) {
                return '';
            }
            if (num > 99) {
                num = "99+"
            }
            if (mention > 0) {
                if (mention > 99) {
                    return "@99+"
                }
                return `${num}·@${mention}`
            }
            return String(num);
        },

        msgAllUnread() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog);
            })
            return num;
        },

        unreadTotal() {
            if (this.userId > 0) {
                return this.msgAllUnread + this.dashboardTask.overdue_count + this.reportUnreadNumber
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
                return data.filter(item => $A.strExists(`${item.name}||${item.desc}`, projectKeyValue));
            }
            return data;
        },

        listClassName() {
            return {
                'scrollbar-overlay': true,
                'scrollbar-hidden': this.operateVisible === true,
            }
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
            return ['manage-dashboard', 'manage-calendar', 'manage-messenger', 'manage-file', 'manage-setting'].includes(this.routeName)
        },
    },

    watch: {
        '$route' () {
            this.chackPass();
        },

        userInfo() {
            this.chackPass();
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

        wsOpenNum(num) {
            if (num <= 1) return
            this.$store.dispatch("getBasicData", 600).then(this.getReportUnread)
        },

        workReportShow(show) {
            if (show) {
                this.getReportUnread(0);
            }
        },

        'cacheProjects.length': {
            handler() {
                this.$nextTick(_ => {
                    const menuProject = this.$refs.menuProject
                    const lastEl = $A.last($A.getObject(menuProject, 'children.0.children'))
                    if (lastEl) {
                        const lastRect = lastEl.getBoundingClientRect()
                        const menuRect = menuProject.getBoundingClientRect()
                        if (lastRect.top > menuRect.top + menuRect.height) {
                            this.projectSearchShow = true
                            return
                        }
                    }
                    this.projectSearchShow = false
                })
            },
            immediate: true
        },

        unreadTotal: {
            handler(num) {
                if (this.$Electron) {
                    this.$Electron.sendMessage('setDockBadge', num);
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

        chackPass() {
            if (this.userInfo.changepass === 1) {
                this.goForward({name: 'manage-setting-password'});
            }
        },

        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
        },

        toggleRoute(path, params) {
            this.show768Menu = false;
            let location = {name: 'manage-' + path, params: params || {}};
            if (path === 'file' && $A.getStorageInt("file::folderId") > 0) {
                location.params.folderId = $A.getStorageInt("file::folderId")
            }
            this.goForward(location);
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
                        $A.reloadUrl()
                    }).catch(() => {
                        $A.reloadUrl()
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

        menuVisibleChange(visible) {
            this.visibleMenu = visible
        },

        classNameRoute(path) {
            return {
                "active": this.routeName === `manage-${path}`,
            };
        },

        classNameProject(item) {
            return {
                "active": this.routeName === 'manage-project' && this.$route.params.projectId == item.id,
                "open-menu": this.openMenu[item.id] === true,
                "operate": item.id == this.operateItem.id && this.operateVisible
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

        searchProject() {
            setTimeout(() => {
                this.projectKeyLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                }
            }).finally(_ => {
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
                if (e.keyCode === 74) {
                    e.preventDefault();
                    this.onAddMenu('createMeeting')
                } else if (e.keyCode === 75 || e.keyCode === 78) {
                    e.preventDefault();
                    this.onAddMenu('task')
                } else if (e.keyCode === 83 && this.$refs.taskModal.checkUpdate()) {
                    e.preventDefault();
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
            if (!this.natificationReady && !this.$isEEUiApp) {
                return; // 通知未准备好不通知
            }
            if (this.windowActive && data.dialog_id === $A.last(this.dialogIns)?.dialog_id) {
                return; // 窗口激活且最后打开的会话是通知的会话时不通知
            }
            //
            const {id, dialog_id, type, msg, userid} = data;
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
            const notificationFunc = (title) => {
                if (this.__notificationId === id) {
                    if (this.$isEEUiApp) {
                        this.$refs.mobileNotification.open({
                            userid: userid,
                            desc: body,
                            callback: () => {
                                this.goForward({name: 'manage-messenger'});
                                this.$store.dispatch('openDialog', dialog_id)
                            }
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
                notificationFunc(dialog.name)
            } else {
                this.$store.dispatch("getDialogOne", dialog_id).then(({data}) => {
                    notificationFunc(data.name)
                }).catch(() => {})
            }
        },

        getReportUnread(timeout) {
            this.reportUnreadTimeout && clearTimeout(this.reportUnreadTimeout)
            this.reportUnreadTimeout = setTimeout(() => {
                if (this.userId === 0) {
                    this.reportUnreadNumber = 0;
                } else {
                    this.$store.dispatch("call", {
                        url: 'report/unread',
                    }).then(({data}) => {
                        this.reportUnreadNumber = data.total || 0;
                    }).catch(() => {});
                }
            }, typeof timeout === "number" ? timeout : 1000)
        },

        handleLongpress(event, el) {
            const projectId = $A.getAttr(el, 'data-id')
            const projectItem = this.projectLists.find(item => item.id == projectId)
            if (!projectItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(projectItem) ? projectItem : {};
            this.$nextTick(() => {
                const projectRect = el.getBoundingClientRect();
                const wrapRect = this.$refs.menuProject.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX - wrapRect.left}px`,
                    top: `${projectRect.top + this.windowScrollY}px`,
                    height: projectRect.height + 'px',
                }
                this.operateVisible = true;
            })
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'project/top',
                data: {
                    project_id: this.operateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveProject", data);
                this.$nextTick(() => {
                    const active = this.$refs.menuProject.querySelector(".active")
                    if (active) {
                        $A.scrollIntoViewIfNeeded(active);
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
                this.exportTaskShow = false;
                this.$store.dispatch('downUrl', {
                    url: data.url
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.exportLoadIng--;
            });
        },

        onTabbarClick(act) {
            switch (act) {
                case 'addTask':
                    this.onAddTask(0)
                    break;
                case 'addProject':
                    this.onAddShow()
                    break;
            }
        },

        notificationInit() {
            this.notificationManage = new notificationKoro(this.$L("打开通知成功"));
            if (this.notificationManage.support) {
                this.notificationManage.notificationEvent({
                    onclick: ({target}) => {
                        console.log("[Notification] Click", target);
                        this.notificationManage.close();
                        try {window.focus()}catch (e) {}
                        //
                        const {tag, data} = target;
                        if (tag == 'dialog') {
                            if (!$A.isJson(data)) {
                                return;
                            }
                            this.goForward({name: 'manage-messenger'});
                            this.$store.dispatch('openDialog', data.dialog_id)
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
    }
}
</script>
