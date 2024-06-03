<template>
    <div class="page-manage" :class="{'show-tabbar': showMobileTabbar, 'not-logged': userId <= 0}">
        <div class="manage-box-menu" :class="{'show-mobile-menu': showMobileMenu}">
            <Dropdown
                class="page-manage-menu-dropdown main-menu"
                trigger="click"
                @on-click="settingRoute"
                @on-visible-change="menuVisibleChange">
                <div :class="['manage-box-title', visibleMenu ? 'menu-visible' : '']">
                    <div class="manage-box-avatar">
                        <UserAvatar :userid="userId" :size="36"/>
                    </div>
                    <span>{{userInfo.nickname}}</span>
                    <Badge v-if="!!clientNewVersion" class="manage-box-top-report" dot/>
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
                            placement="right-start">
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
                            <i class="taskfont">&#xe6fb;</i>
                            <div class="menu-title">{{$L('仪表盘')}}</div>
                            <Badge v-if="dashboardTask.overdue_count > 0" class="menu-badge" type="error" :overflow-count="999" :count="dashboardTask.overdue_count"/>
                            <Badge v-else-if="dashboardTask.today_count > 0" class="menu-badge" type="info" :overflow-count="999" :count="dashboardTask.today_count"/>
                            <Badge v-else-if="dashboardTask.all_count > 0" class="menu-badge" type="primary" :overflow-count="999" :count="dashboardTask.all_count"/>
                        </li>
                        <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                            <i class="taskfont">&#xe6f5;</i>
                            <div class="menu-title">{{$L('日历')}}</div>
                        </li>
                        <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                            <i class="taskfont">&#xe6eb;</i>
                            <div class="menu-title">{{$L('消息')}}</div>
                            <Badge class="menu-badge" :overflow-count="999" :text="msgUnreadMention"/>
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
                <div ref="menuProject" class="menu-project">
                    <ul>
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
                </div>
            </Scrollbar>
            <div class="operate-position" :style="operateStyles" v-show="operateVisible">
                <Dropdown
                    trigger="custom"
                    :placement="windowLandscape ? 'bottom' : 'top'"
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
                v-if="projectKeyValue || ((projectSearchShow || projectTotal > 20) && windowHeight > 600)"
                class="manage-project-search">
                <div class="search-pre">
                    <Loading v-if="projectKeyLoading > 0"/>
                    <Icon v-else type="ios-search" />
                </div>
                <Input v-model="projectKeyValue" :placeholder="$L(`共${projectTotal || cacheProjects.length}个项目，搜索...`)" clearable/>
            </div>
            <ButtonGroup class="manage-box-new-group">
                <Button class="manage-box-new" type="primary" icon="md-add" @click="onAddShow">{{$L('新建项目')}}</Button>
                <Dropdown @on-click="onAddMenu" trigger="click">
                    <Button type="primary">
                        <Icon type="ios-arrow-down"></Icon>
                    </Button>
                    <DropdownMenu slot="list">
                        <DropdownItem name="project">{{$L('新建项目')}} ({{mateName}}+B)</DropdownItem>
                        <DropdownItem name="task">{{$L('新建任务')}} ({{mateName}}+K)</DropdownItem>
                        <DropdownItem name="group">{{$L('创建群组')}} ({{mateName}}+U)</DropdownItem>
                        <DropdownItem name="createMeeting">{{$L('新会议')}} ({{mateName}}+J)</DropdownItem>
                        <DropdownItem name="joinMeeting">{{$L('加入会议')}}</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </ButtonGroup>
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

        <!--创建群组-->
        <Modal
            v-model="createGroupShow"
            :title="$L('创建群组')"
            :mask-closable="false">
            <Form :model="createGroupData" label-width="auto" @submit.native.prevent>
                <FormItem prop="avatar" :label="$L('群头像')">
                    <ImgUpload v-model="createGroupData.avatar" :num="1" :width="512" :height="512" :whcut="1"/>
                </FormItem>
                <FormItem prop="userids" :label="$L('群成员')">
                    <UserSelect v-model="createGroupData.userids" :uncancelable="createGroupData.uncancelable" :multiple-max="100" show-bot :title="$L('选择项目成员')"/>
                </FormItem>
                <FormItem prop="chat_name" :label="$L('群名称')">
                    <Input v-model="createGroupData.chat_name" :placeholder="$L('输入群名称（选填）')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="createGroupShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="createGroupLoad > 0" @click="submitCreateGroup">{{$L('创建')}}</Button>
            </div>
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

        <!--举报投诉管理-->
        <DrawerOverlay
            v-model="complaintShow"
            placement="right"
            :size="1200">
            <ComplaintManagement v-if="complaintShow"/>
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
import ComplaintManagement from "./manage/components/ComplaintManagement";
import MicroApps from "../components/MicroApps.vue";
import notificationKoro from "notification-koro1";
import {Store} from "le5le-store";
import {MarkdownPreview} from "../store/markdown";
import UserSelect from "../components/UserSelect.vue";
import ImgUpload from "../components/ImgUpload.vue";

export default {
    components: {
        ImgUpload, UserSelect,
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
        MicroApps,
        ComplaintManagement
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

            createGroupShow: false,
            createGroupData: {},
            createGroupLoad: 0,
            createGroupSubscribe: null,

            exportTaskShow: false,
            exportCheckinShow: false,
            exportApproveShow: false,


            dialogMsgSubscribe: null,

            projectKeyValue: '',
            projectKeyLoading: 0,
            projectSearchShow: false,

            openMenu: {},
            visibleMenu: false,
            showMobileMenu: false,

            workReportShow: false,
            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            natificationReady: false,
            notificationManage: null,

            reportTabs: "my",

            operateStyles: {},
            operateVisible: false,
            operateItem: {},

            needStartHome: false,

            complaintShow: false,
        }
    },

    mounted() {
        this.notificationInit();
        //
        this.addTaskSubscribe = Store.subscribe('addTask', this.onAddTask);
        this.createGroupSubscribe = Store.subscribe('createGroup', this.onCreateGroup);
        this.dialogMsgSubscribe = Store.subscribe('dialogMsgPush', this.addDialogMsg);
        //
        document.addEventListener('keydown', this.shortcutEvent);
    },

    activated() {
        this.$store.dispatch("getUserInfo").catch(_ => {})
        this.$store.dispatch("getTaskPriority").catch(_ => {})
        this.$store.dispatch("getReportUnread", 1000)
        this.$store.dispatch("getApproveUnread", 1000)
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
        if (this.createGroupSubscribe) {
            this.createGroupSubscribe.unsubscribe();
            this.createGroupSubscribe = null;
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
            'cacheProjects',
            'projectTotal',
            'wsOpenNum',
            'columnTemplate',

            'clientNewVersion',
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
            return import.meta.env.VITE_OKR_WEB_URL || $A.mainUrl("apps/okr")
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
                    {path: 'complaint', name: '举报管理'},
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

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(item => $A.strExists(`${item.name} ${item.desc}`, projectKeyValue));
            }
            return data;
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
            this.$store.dispatch("getBasicData", 600)
        },

        workReportShow(show) {
            if (show) {
                this.$store.dispatch("getReportUnread", 0)
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

        unreadAndOverdue: {
            handler(val) {
                if (this.$Electron) {
                    this.$Electron.sendMessage('setDockBadge', val);
                }
            },
            immediate: true
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
                case 'complaint':
                    this.complaintShow = true;
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
            if(routeName == 'manage-approve' || routeName == 'manage-apps'){
                routeName = `manage-application`
            }
            return {
                "active": routeName === `manage-${path}`,
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
                case 'project':
                    this.onAddShow()
                    break;

                case 'task':
                    this.onAddTask(0)
                    break;

                case 'group':
                    this.onCreateGroup([this.userId])
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
                switch (e.keyCode) {
                    case 66: // B - 新建项目
                        e.preventDefault();
                        this.onAddShow()
                        break;

                    case 75:
                    case 78: // K/N - 新建任务
                        e.preventDefault();
                        this.onAddMenu('task')
                        break;

                    case 85: // U - 创建群组
                        this.onCreateGroup([this.userId])
                        break;

                    case 74: // J - 新会议
                        e.preventDefault();
                        this.onAddMenu('createMeeting')
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

        onCreateGroup(userids) {
            if (!$A.isArray(userids)) {
                userids = []
            }
            this.createGroupData = {userids, uncancelable: [this.userId]}
            this.createGroupShow = true
        },

        submitCreateGroup() {
            this.createGroupLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/group/add',
                data: this.createGroupData
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.createGroupShow = false;
                this.createGroupData = {};
                this.$store.dispatch("saveDialog", data);
                this.$store.dispatch('openDialog', data.id)
                this.toggleRoute('messenger', {dialogAction: 'dialog'})
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.createGroupLoad--;
            });
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
                    body = $A.getMsgTextPreview(msg.type === 'md' ? MarkdownPreview(msg.text) : msg.text)
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
                    this.__notificationId = null
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

        handleLongpress(event, el) {
            const projectId = $A.getAttr(el, 'data-id')
            const projectItem = this.projectLists.find(item => item.id == projectId)
            if (!projectItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(projectItem) ? projectItem : {};
            this.$nextTick(() => {
                const rect = el.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX}px`,
                    top: `${rect.top + this.windowScrollY}px`,
                    height: rect.height + 'px',
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

        onTabbarClick(act) {
            switch (act) {
                case 'addTask':
                    this.onAddTask(0)
                    break;
                case 'addProject':
                    this.onAddShow()
                    break;
                case 'allUser':
                case 'complaint':
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
