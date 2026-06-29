<template>
    <div class="page-manage" :class="pageClass">
        <div
            ref="boxMenu"
            class="manage-box-menu"
            :class="{'menu-resizing': menuResizing}"
            :style="menuStyle">
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
                    <template v-for="(item, index) in menu">
                        <!--最近打开的任务-->
                        <Dropdown
                            v-if="item.path === 'taskBrowse'"
                            :key="`taskBrowse-${index}`"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown max-h-400"
                            placement="right-start">
                            <DropdownItem :divided="!!item.divided">
                                <div class="manage-menu-flex">
                                    <div class="manage-menu-title">
                                        {{$L(item.name)}}
                                    </div>
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list" v-if="taskBrowseLists.length > 0">
                                <template v-for="(item, key) in taskBrowseLists">
                                    <DropdownItem
                                        v-if="item.id > 0 && key < 10"
                                        :key="`task-${key}`"
                                        :style="$A.generateColorVarStyle(item.flow_item_color, [10], 'flow-item-custom-color')"
                                        class="task-title"
                                        @click.native="openTask(item)"
                                        :name="item.name">
                                        <span v-if="item.flow_item_name" :class="item.flow_item_status">{{item.flow_item_name}}</span>
                                        <div class="task-title-text">{{ item.name }}</div>
                                    </DropdownItem>
                                </template>
                                <DropdownItem
                                    :key="'task-browse-view-more'"
                                    class="task-title task-view-more"
                                    @click.native="openRecent"
                                    name="taskBrowseViewMore">
                                    <div class="task-title-text">{{ $L('查看更多...') }}</div>
                                </DropdownItem>
                            </DropdownMenu>
                            <DropdownMenu v-else slot="list">
                                <DropdownItem style="color:darkgrey">{{ $L('暂无打开记录') }}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 团队管理 -->
                        <Dropdown
                            v-else-if="item.path === 'team'"
                            :key="`team-${index}`"
                            transfer
                            transfer-class-name="page-manage-menu-dropdown"
                            placement="right-start">
                            <DropdownItem :divided="!!item.divided">
                                <div class="manage-menu-flex">
                                    <div class="manage-menu-title">
                                        {{$L(item.name)}}
                                    </div>
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem name="allUser">{{$L('团队管理')}}</DropdownItem>
                                <DropdownItem name="exportTask">{{$L('导出任务统计')}}</DropdownItem>
                                <DropdownItem name="exportOverdueTask">{{$L('导出超期任务')}}</DropdownItem>
                                <DropdownItem name="exportCheckin">{{$L('导出签到数据')}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <!-- 部门负责人视角 -->
                        <DropdownItem
                            v-else-if="item.path === 'departmentOwnerView'"
                            :key="`menu-${index}`"
                            :divided="!!item.divided"
                            :name="item.path"
                            :style="item.style || {}">
                            <div class="manage-menu-flex">
                                <div class="manage-menu-title">
                                    {{$L(item.name)}}
                                </div>
                                <Badge
                                    v-if="item.selectedCount > 0"
                                    class="manage-menu-report-badge"
                                    :overflow-count="999"
                                    :count="item.selectedCount"/>
                            </div>
                        </DropdownItem>
                        <!-- 其他菜单 -->
                        <template v-else-if="item.visible !== false">
                            <DropdownItem
                                :key="`menu-${index}`"
                                :divided="!!item.divided"
                                :name="item.path"
                                :style="item.style || {}">
                                <div class="manage-menu-flex">
                                    <div class="manage-menu-title">
                                        {{$L(item.name)}}
                                    </div>
                                    <Icon
                                        v-if="item.selected === true"
                                        type="md-checkmark" />
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
                            <Badge v-else-if="dashboardTask.todo_count > 0" class="menu-badge" type="primary" :overflow-count="999" :count="dashboardTask.todo_count"/>
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
                            <Badge v-if="applicationBadgeCount > 0" class="menu-badge" :overflow-count="999" :count="applicationBadgeCount"/>
                            <Badge v-else-if="applicationBadgeDot" class="menu-badge" dot/>
                        </li>
                        <li v-for="(item, key) in filterMicroAppsMenusMain" :key="key" @click="onTabbarClick('microApp', item)">
                            <div class="apply-icon no-dark-content" :style="{backgroundImage: `url(${item.icon})`}"></div>
                            <div class="menu-title">{{item.label}}</div>
                            <Badge v-if="microBadge(item).count > 0" class="menu-badge" :overflow-count="999" :count="microBadge(item).count"/>
                            <Badge v-else-if="microBadge(item).dot" class="menu-badge" dot/>
                        </li>
                    </ul>
                    <div v-if="ownerProjectTabsVisible" class="owner-project-tabs">
                        <div
                            v-for="item in ownerProjectTabs"
                            :key="item.type"
                            :class="['owner-project-tab', ownerProjectTab === item.type ? 'active' : '']"
                            :title="$L(item.name)"
                            @click="ownerProjectTab = item.type">
                            <span>{{$L(item.name)}}</span>
                            <Badge :overflow-count="999" :count="item.count"/>
                        </div>
                    </div>
                </div>
                <div ref="menuProject" class="menu-project">
                    <Draggable
                        :list="projectDraggableList"
                        :animation="150"
                        :disabled="$isEEUIApp || windowTouch || !!projectKeyValue || ownerProjectTabsVisible"
                        tag="ul"
                        item-key="id"
                        draggable="li:not(.pinned)"
                        handle=".project-h1"
                        v-longpress="handleLongpress"
                        @start="projectDragging = true"
                        @end="onProjectSortEnd">
                        <li
                            v-for="item in projectDraggableList"
                            :ref="`project_${item.id}`"
                            :key="item.id"
                            :class="[classNameProject(item), item.top_at ? 'pinned' : '']"
                            :data-id="item.id"
                            @pointerdown="handleOperation"
                            @click="toggleRoute('project', {projectId: item.id})">
                            <div class="project-h1">
                                <em @click.stop="toggleOpenMenu(item.id)"></em>
                                <div class="title" v-html="transformEmojiToHtml(item.name)"></div>
                                <ETooltip v-if="item.department_readonly && item.personal" :content="$L('个人项目，只读查看')" placement="right">
                                    <UserAvatar class="readonly-owner-avatar" :userid="item.userid" :size="18"/>
                                </ETooltip>
                                <ETooltip v-else-if="item.department_readonly" :content="$L('负责人视角，只读查看')" placement="right">
                                    <i class="taskfont readonly-project-avatar">&#xe75c;</i>
                                </ETooltip>
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
                        <li v-if="projectKeyLoading > 0 || departmentOwnerProjectsRefreshing" class="loading"><Loading/></li>
                        <li v-else-if="projectLists.length === 0" class="nothing">
                            {{$L(projectKeyValue ? `没有任何与"${projectKeyValue}"相关的结果` : `没有任何项目`)}}
                        </li>
                    </Draggable>
                </div>
            </Scrollbar>
            <div
                v-transfer-dom
                :data-transfer="true"
                class="operate-position"
                :style="operateStyles"
                v-show="operateVisible">
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
                        <DropdownItem @click.native="handleChatClick">
                            {{ $L('项目讨论') }}
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
                <Form class="search-form" action="javascript:void(0)" @submit.native.prevent="$A.eeuiAppKeyboardHide">
                    <Input type="search" v-model="projectKeyValue" :placeholder="$L(`共${projectTotal || cacheProjects.length}个项目，搜索...`)" clearable/>
                </Form>
            </div>
            <ButtonGroup class="manage-box-new-group">
                <Button class="manage-box-new" type="primary" icon="md-add" @click="onAddMenu('task')">{{$L('新建任务')}}</Button>
                <Dropdown @on-click="onAddMenu" trigger="click">
                    <Button type="primary">
                        <Icon type="ios-arrow-down"></Icon>
                    </Button>
                    <DropdownMenu slot="list">
                        <DropdownItem v-if="aiInstalled" name="aiAssistant">{{$L('AI 助手')}} ({{mateName}}+I)</DropdownItem>
                        <DropdownItem name="task">{{$L('新建任务')}} ({{mateName}}+K)</DropdownItem>
                        <DropdownItem name="project">{{$L('新建项目')}} ({{mateName}}+B)</DropdownItem>
                        <DropdownItem name="group">{{$L('创建群组')}} ({{mateName}}+U)</DropdownItem>
                        <DropdownItem name="createMeeting">{{$L('新会议')}} ({{mateName}}+J)</DropdownItem>
                        <DropdownItem name="joinMeeting">{{$L('加入会议')}}</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </ButtonGroup>
            <ResizeLine
                class="manage-menu-resize"
                placement="right"
                v-model="menuWidth"
                :min="200"
                :max="420"
                @on-change="onMenuResizeChange"/>
        </div>

        <div class="manage-box-main" :role="routeName">
            <div class="manage-status-bar"><span></span></div>
            <keep-alive>
                <router-view class="manage-box-view" @on-click="onTabbarClick"></router-view>
            </keep-alive>
            <div class="manage-navigation-bar"><span></span></div>
        </div>

        <!--新建项目-->
        <Modal
            v-model="addShow"
            :title="$L('新建项目')"
            :mask-closable="false">
            <Form
                ref="addProject"
                :model="addData"
                :rules="addRule"
                v-bind="formOptions"
                @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <div class="page-manage-project-ai-wrapper">
                        <Input ref="projectName" type="text" v-model="addData.name"></Input>
                        <div
                            class="project-ai-button"
                            type="text"
                            @click="onProjectAI">
                            <i class="taskfont">&#xe8a1;</i>
                        </div>
                    </div>
                </FormItem>
                <FormItem v-if="addData.columns" :label="$L('任务列表')">
                    <TagInput v-model="addData.columns" :cut="[',', '，', ' ']"/>
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
            <Form :model="createGroupData" v-bind="formOptions"  @submit.native.prevent>
                <FormItem prop="avatar" :label="$L('群头像')">
                    <ImgUpload v-model="createGroupData.avatar" :num="1" :width="512" :height="512" whcut="cover"/>
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

        <!--弹出 MCP 服务器信息-->
        <MCPHelper v-model="mcpHelperShow"/>

        <!--负责人视角-->
        <DepartmentOwnerView v-model="departmentOwnerViewShow"/>

        <!--导出任务统计-->
        <TaskExport v-model="exportTaskShow"/>

        <!--导出签到数据-->
        <CheckinExport v-model="exportCheckinShow"/>

        <!--任务详情-->
        <TaskModal ref="taskModal"/>

        <!--聊天窗口-->
        <DialogModal ref="dialogModal"/>

        <!--搜索框-->
        <SearchBox ref="searchBox"/>

        <!--工作报告-->
        <DrawerOverlay
            v-model="workReportShow"
            placement="right"
            :size="1200">
            <Report v-if="workReportShow" v-model="workReportTab" @on-read="$store.dispatch('getReportUnread', 1000)" />
        </DrawerOverlay>

        <!--我的收藏-->
        <DrawerOverlay
            v-model="favoriteShow"
            placement="right"
            :size="1200">
            <FavoriteManagement v-if="favoriteShow" @on-close="favoriteShow = false"/>
        </DrawerOverlay>

        <!--最近打开-->
        <DrawerOverlay
            v-model="recentShow"
            placement="right"
            :size="1200">
            <RecentManagement v-if="recentShow" @on-close="recentShow = false"/>
        </DrawerOverlay>

        <!--团队成员管理-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1380">
            <TeamManagement v-if="allUserShow" @on-close="allUserShow=false"/>
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

        <!--移动端选项卡-->
        <transition name="mobile-slide">
            <MobileTabbar v-if="mobileTabbar" @on-click="onTabbarClick"/>
        </transition>

        <!--应用详情-->
        <MicroApps/>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import ProjectArchived from "./manage/components/ProjectArchived";
import TeamManagement from "./manage/components/TeamManagement";
import MCPHelper from "./manage/components/MCPHelper";
import FavoriteManagement from "./manage/components/FavoriteManagement";
import RecentManagement from "./manage/components/RecentManagement";
import ProjectManagement from "./manage/components/ProjectManagement";
import DrawerOverlay from "../components/DrawerOverlay";
import MobileTabbar from "../components/Mobile/Tabbar";
import TaskAdd from "./manage/components/TaskAdd";
import Report from "./manage/components/Report";
import longpress from "../directives/longpress";
import TransferDom from "../directives/transfer-dom";
import DialogModal from "./manage/components/DialogModal";
import TaskModal from "./manage/components/TaskModal";
import CheckinExport from "./manage/components/CheckinExport";
import TaskExport from "./manage/components/TaskExport";
import ComplaintManagement from "./manage/components/ComplaintManagement";
import MicroApps from "../components/MicroApps";
import ResizeLine from "../components/ResizeLine.vue";
import UserSelect from "../components/UserSelect.vue";
import ImgUpload from "../components/ImgUpload.vue";
import notificationKoro from "notification-koro1";
import emitter from "../store/events";
import SearchBox from "../components/SearchBox.vue";
import transformEmojiToHtml from "../utils/emoji";
import {languageName} from "../language";
import {AINormalizeJsonContent, PROJECT_AI_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../utils/ai";
import Draggable from 'vuedraggable'
import DepartmentOwnerView from "./manage/components/DepartmentOwnerView.vue";

export default {
    components: {
        SearchBox,
        ImgUpload,
        UserSelect,
        TaskExport,
        CheckinExport,
        TaskModal,
        DialogModal,
        MobileTabbar,
        TaskAdd,
        Report,
        DrawerOverlay,
        ProjectManagement,
        TeamManagement,
        MCPHelper,
        FavoriteManagement,
        RecentManagement,
        ProjectArchived,
        MicroApps,
        ResizeLine,
        ComplaintManagement,
        Draggable,
        DepartmentOwnerView
    },
    directives: {longpress, TransferDom},
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

            createGroupShow: false,
            createGroupData: {},
            createGroupLoad: 0,

            exportTaskShow: false,
            exportCheckinShow: false,

            projectKeyValue: '',
            projectKeyLoading: 0,
            projectSearchShow: false,

            projectDraggableList: [],
            projectDragging: false,
            ownerProjectTab: 'mine',

            openMenu: {},
            visibleMenu: false,

            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            favoriteShow: false,
            recentShow: false,

            natificationReady: false,
            notificationManage: null,

            workReportShow: false,
            workReportTab: "my",

            operateStyles: {},
            operateVisible: false,
            operateItem: {},

            complaintShow: false,

            taskBrowseLoading: false,
            taskBrowseHistory: [],

            mcpHelperShow: false,
            departmentOwnerViewShow: false,

            menuWidth: Math.min(420, Math.max(200, $A.getStorageInt("manage.menuWidth", 255))),
            menuResizing: false,
        }
    },

    mounted() {
        this.notificationInit();
        //
        emitter.on('addTask', this.onAddTask);
        emitter.on('createGroup', this.onCreateGroup);
        emitter.on('dialogMsgPush', this.addDialogMsg);
        emitter.on('openReport', this.openReport);
        emitter.on('openFavorite', this.openFavorite);
        emitter.on('openRecent', this.openRecent);
        emitter.on('openManageExport', this.openManageExport);
        //
        document.addEventListener('keydown', this.shortcutEvent);
    },

    activated() {
        this.$store.dispatch("getUserInfo").catch(_ => {})
        this.$store.dispatch("getTaskPriority", 1000)
        this.$store.dispatch("getReportUnread", 1000)
    },

    beforeDestroy() {
        emitter.off('addTask', this.onAddTask);
        emitter.off('createGroup', this.onCreateGroup);
        emitter.off('dialogMsgPush', this.addDialogMsg);
        emitter.off('openReport', this.openReport);
        emitter.off('openFavorite', this.openFavorite);
        emitter.off('openRecent', this.openRecent);
        emitter.off('openManageExport', this.openManageExport);
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
            'themeName',
            'wsOpenNum',
            'columnTemplate',

            'clientNewVersion',
            'clientDownloadUrl',

            'reportUnreadNumber',

            'dialogIns',
            'formOptions',
            'systemConfig',
            'mobileTabbar',
            'longpressData',
            'departmentOwnerProjectsRefreshing',

            'mcpServerStatus',
            'microAppsIds'
        ]),

        ...mapGetters(['dashboardTask', "filterMicroAppsMenusMain"]),

        // 父『应用』入口聚合角标（规则收敛在 appBadges 模块 getter）
        ...mapGetters('appBadges', {
            applicationBadgeCount: 'applicationCount',
            applicationBadgeDot: 'applicationDot',
        }),

        aiInstalled() {
            return this.microAppsIds?.includes('ai');
        },

        departmentOwnerViewAvailable() {
            return this.systemConfig.department_owner_project_view === 'open' && (this.userInfo.managed_departments || []).length > 0;
        },

        cacheDepartmentOwnerIds() {
            return this.$store.state.cacheDepartmentOwnerIds || [];
        },

        /**
         * page className
         * @param mobileTabbar
         * @param userId
         * @returns {{"show-tabbar", "not-logged": boolean}}
         */
        pageClass({mobileTabbar, userId}) {
            return {
                'show-tabbar': mobileTabbar,
                'not-logged': userId <= 0
            }
        },

        menuStyle() {
            return {
                width: `${this.menuWidth}px`
            }
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

        /**
         * 是否显示客户端下载
         * @returns {boolean}
         */
        showDownloadClient() {
            return !this.$Electron && !this.$isEEUIApp && !!this.clientDownloadUrl
        },

        /**
         * 右上角菜单
         * @returns {Array}
         */
        menu() {
            const {userIsAdmin} = this;
            const array = [
                {path: 'taskBrowse', name: '最近打开的任务'},
                {path: 'favorite', name: '我的收藏'},
                {path: 'download', name: '下载内容', visible: !!this.$Electron},
                {path: 'mcpServer', name: '启用桌面 MCP 服务器', visible: !!this.$Electron, selected: this.mcpServerStatus.running === 'running'},
            ];
            if (userIsAdmin) {
                array.push(...[
                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'system', name: '系统设置'},
                    {path: 'license', name: 'License Key'},

                    {path: 'downloadClient', name: '客户端下载', divided: true, visible: this.showDownloadClient},
                    {path: 'version', name: '更新版本', divided: true, visible: !!this.clientNewVersion},

                    {path: 'allProject', name: '所有项目', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},

                    {path: 'team', name: '团队管理', divided: true},
                ])
            } else {
                array.push(...[
                    {path: 'personal', name: '个人设置', divided: true},
                    {path: 'downloadClient', name: '客户端下载', divided: true, visible: this.showDownloadClient},
                    {path: 'version', name: '更新版本', divided: true, visible: !!this.clientNewVersion},

                    {path: 'workReport', name: '工作报告', divided: true},
                    {path: 'archivedProject', name: '已归档的项目'},
                ])
            }
            if (this.departmentOwnerViewAvailable) {
                array.push({
                    path: 'departmentOwnerView',
                    name: '负责人视角',
                    divided: !userIsAdmin,
                    visible: true,
                    selected: this.cacheDepartmentOwnerIds.length > 0,
                    selectedCount: this.cacheDepartmentOwnerIds.length,
                });
            }
            array.push(...[
                {path: 'clearCache', name: '清除缓存', divided: true},
                {path: 'logout', name: '退出登录', style: {color: '#f40'}}
            ])
            return array
        },

        /**
         * 项目模板列表
         * @returns {Array}
         */
        columns() {
            const array = $A.cloneJSON(this.columnTemplate);
            array.unshift({
                name: this.$L('空白模板'),
                columns: [],
            })
            return array
        },

        /**
         * 项目列表
         * @returns {Array}
         */
        projectBaseLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                // 置顶优先
                if (a.top_at !== b.top_at && (a.top_at || b.top_at)) {
                    return $A.sortDay(b.top_at, a.top_at);
                }
                // 自定义排序
                const as = typeof a.sort === 'number' ? a.sort : Number.MAX_SAFE_INTEGER;
                const bs = typeof b.sort === 'number' ? b.sort : Number.MAX_SAFE_INTEGER;
                if (as !== bs) return as - bs;
                // 兜底：按ID倒序
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(item => $A.strExists(`${item.name} ${item.desc}`, projectKeyValue));
            }
            return data;
        },

        ownerProjectTabsVisible() {
            return this.departmentOwnerViewAvailable && this.cacheDepartmentOwnerIds.length > 0;
        },

        ownerProjectTabs() {
            return [
                {type: 'mine', name: '我的项目', count: this.projectBaseLists.filter(item => !item.department_readonly).length},
                {type: 'readonly', name: '负责人视角', count: this.projectBaseLists.filter(item => item.department_readonly).length},
            ];
        },

        routeProjectId() {
            const {projectId} = this.$route.params;
            return parseInt(/^\d+$/.test(projectId) ? projectId : 0);
        },

        routeProject() {
            if (this.routeProjectId <= 0) {
                return null;
            }
            return this.cacheProjects.find(({id}) => id == this.routeProjectId) || null;
        },

        projectLists() {
            if (!this.ownerProjectTabsVisible) {
                return this.projectBaseLists;
            }
            return this.projectBaseLists.filter(item => this.ownerProjectTab === 'readonly' ? item.department_readonly : !item.department_readonly);
        },

        /**
         * 最近打开的任务列表
         * @returns {Array}
         */
        taskBrowseLists() {
            // 直接使用组件内的响应式数据
            return this.taskBrowseHistory.slice(0, 10); // 只显示前10个
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
            if (!show) return
            this.$store.dispatch("getReportUnread", 0)
        },

        windowActive(active) {
            if (!active) return
            this.$store.dispatch("getProjectByQueue", 600);
        },

        themeName: {
            handler(theme) {
                if (this.$Electron) {
                    $A.Electron.request({
                        action: 'updateDownloadWindow',
                        language: languageName,
                        theme,
                    });
                }
            },
            immediate: true
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

        ownerProjectTabs: {
            handler(tabs) {
                if (!this.ownerProjectTabsVisible) {
                    this.ownerProjectTab = 'mine';
                    return;
                }
                const active = tabs.find(item => item.type === this.ownerProjectTab);
                if (!active || active.count === 0) {
                    const first = tabs.find(item => item.count > 0);
                    if (first) {
                        this.ownerProjectTab = first.type;
                    }
                }
                this.syncOwnerProjectTabByRoute();
            },
            immediate: true
        },

        routeProject: {
            handler() {
                this.syncOwnerProjectTabByRoute();
            },
            immediate: true
        },

        projectLists: {
            handler(val) {
                if (!this.projectDragging) {
                    this.projectDraggableList = $A.cloneJSON(val)
                }
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

        mcpServerStatus: {
            handler(data) {
                if (!this.$Electron) {
                    return;
                }
                this.$Electron.sendMessage('mcpServerToggle', data);
            },
            immediate: true
        }
    },

    methods: {
        transformEmojiToHtml,
        // 插件/微应用菜单角标 {count, dot}
        microBadge(menu) {
            return this.$store.getters['appBadges/badge'](menu && menu.id, menu && menu.key);
        },
        onMenuResizeChange({event}) {
            this.menuResizing = event !== 'up';
            if (event === 'up') {
                $A.setStorage("manage.menuWidth", this.menuWidth);
            }
        },
        syncOwnerProjectTabByRoute() {
            if (!this.ownerProjectTabsVisible || !this.routeProject) {
                return;
            }
            this.ownerProjectTab = this.routeProject.department_readonly ? 'readonly' : 'mine';
        },
        chackPass() {
            if (this.userInfo.changepass === 1) {
                this.goForward({name: 'manage-setting-password'});
            }
        },

        async toggleRoute(path, params) {
            const location = {name: 'manage-' + path, params: params || {}};
            const fileFolderId = await $A.IDBInt("fileFolderId");
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
                case 'departmentOwnerView':
                    this.departmentOwnerViewShow = true;
                    return;
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
                case 'workReport':
                    this.openReport(this.reportUnreadNumber > 0 ? 'receive' : 'my');
                    return;
                case 'favorite':
                    this.openFavorite();
                    return;
                case 'version':
                    emitter.emit('updateNotification', null);
                    return;
                case 'downloadClient':
                    emitter.emit('openDownloadClient');
                    return;
                case 'clearCache':
                    $A.IDBSet("clearCache", "handle").then(_ => {
                        $A.reloadUrl()
                    });
                    return;
                case 'complaint':
                    this.complaintShow = true;
                    return;
                case 'download':
                    $A.Electron.request({
                        action: 'openDownloadWindow',
                        language: languageName,
                        theme: this.themeName,
                    });
                    return;
                case 'mcpServer':
                    this.mcpHelperShow = true;
                    if (this.mcpServerStatus.running !== 'running') {
                        this.$store.dispatch('toggleMcpServer');
                    }
                    return;
                case 'logout':
                    $A.modalConfirm({
                        title: '退出登录',
                        content: '你确定要登出系统吗？',
                        loading: true,
                        onOk: () => {
                            return new Promise(async resolve => {
                                await this.$store.dispatch("logout", false)
                                resolve()
                            })
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
                        }).then(() => {
                            resolve();
                            $A.modalSuccess('正在打包，请留意系统消息。');
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },

        menuVisibleChange(visible) {
            this.visibleMenu = visible
            // 当菜单展开时，获取最新的浏览历史
            if (visible && !this.taskBrowseLoading) {
                this.loadTaskBrowseHistory()
            }
        },

        classNameRoute(path) {
            const name = this.routeName
            return {
                "active": name === `manage-${path}`,
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
                    emitter.emit('addMeeting', {
                        type: 'create',
                        userids: [this.userId],
                    });
                    break;

                case 'joinMeeting':
                    emitter.emit('addMeeting', {
                        type: 'join',
                    });
                    break;

                case 'aiAssistant':
                    this.onOpenAIAssistant();
                    break;
            }
        },

        onOpenAIAssistant() {
            emitter.emit('openAIAssistantGlobal');
        },

        onAddShow() {
            this.$store.dispatch("getColumnTemplate").catch(() => {})
            this.addShow = true;
            this.$nextTick(() => {
                this.$refs.projectName.focus();
            })
        },

        onProjectAI() {
            emitter.emit('openAIAssistant', {
                sessionKey: 'project-create',
                title: this.$L('AI 项目助手'),
                placeholder: this.$L('请简要描述项目目标、范围或关键里程碑，AI 将生成名称和任务列表'),
                onBeforeSend: this.handleProjectAIBeforeSend,
                onRender: this.handleProjectAIRender,
                onApply: this.handleProjectAIApply,
            });
        },

        buildProjectAIContextData() {
            const prompts = [];
            const currentName = (this.addData.name || '').trim();
            const currentColumns = this.normalizeAIColumns(this.addData.columns);

            if (currentName || currentColumns.length > 0) {
                prompts.push('## 当前项目草稿');
                if (currentName) {
                    prompts.push(`已有名称：${currentName}`);
                }
                if (currentColumns.length > 0) {
                    prompts.push(`现有任务列表：${currentColumns.join('、')}`);
                }
                prompts.push('请在此基础上进行优化和补充。');
            }

            const rawTemplates = Array.isArray(this.columns) ? this.columns : [];
            const templateExamples = rawTemplates
                .filter((item, index) => index > 0 && item)
                .map(item => {
                    const columns = this.normalizeAIColumns(item.columns);
                    if (columns.length === 0) {
                        return null;
                    }
                    return {
                        name: (item.name || '').trim(),
                        columns,
                    };
                })
                .filter(Boolean)
                .slice(0, 6);

            if (templateExamples.length > 0) {
                prompts.push('## 常用模板示例');
                templateExamples.forEach(example => {
                    const namePrefix = example.name ? `${example.name}：` : '';
                    prompts.push(`- ${namePrefix}${example.columns.join('、')}`);
                });
                prompts.push('可以借鉴以上结构，但要结合用户需求生成更贴合的方案。');
            }

            return prompts.join('\n').trim();
        },

        handleProjectAIBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(PROJECT_AI_SYSTEM_PROMPT)]
            ];
            const contextPrompt = this.buildProjectAIContextData();
            if (contextPrompt) {
                let assistantContext = [
                    '以下是可用的上下文，请据此生成项目：',
                    contextPrompt,
                ].join('\n');
                if ($A.getObject(context, [0,0]) === 'human') {
                    assistantContext += "\n----\n请根据以上信息，结合以下用户输入的内容生成项目名称和任务列表：++++";
                }
                prepared.push(['human', assistantContext]);
            }
            if (context.length > 0) {
                prepared.push(...context);
            }
            return prepared;
        },

        handleProjectAIApply({rawOutput}) {
            if (!rawOutput) {
                $A.messageWarning('AI 未生成内容');
                return;
            }
            const parsed = this.parseProjectAIContent(rawOutput);
            if (!parsed) {
                $A.modalError('AI 内容解析失败，请重试');
                return;
            }
            if (parsed.name) {
                this.$set(this.addData, 'name', parsed.name);
            }
            if (parsed.columns.length > 0) {
                this.$set(this.addData, 'columns', parsed.columns.join(','));
            }
            this.$nextTick(() => {
                if (this.$refs.projectName) {
                    this.$refs.projectName.focus();
                }
            });
        },

        normalizeAIColumns(value) {
            if (!value) {
                return [];
            }
            const normalize = (item) => {
                if (!item) {
                    return '';
                }
                if (typeof item === 'string') {
                    return item.trim();
                }
                if (typeof item === 'object') {
                    const text = item.name || item.title || item.label || item.value || '';
                    return typeof text === 'string' ? text.trim() : '';
                }
                return String(item).trim();
            };
            if (Array.isArray(value)) {
                return value.map(normalize).filter(Boolean);
            }
            if (typeof value === 'string') {
                return value.split(/[\n\r,，;；|]/).map(item => item.trim()).filter(Boolean);
            }
            if (typeof value === 'object') {
                if (Array.isArray(value.columns)) {
                    return this.normalizeAIColumns(value.columns);
                }
                if (typeof value.columns === 'string') {
                    return this.normalizeAIColumns(value.columns);
                }
            }
            return [];
        },

        parseProjectAIContent(content) {
            const payload = AINormalizeJsonContent(content);
            if (!payload || typeof payload !== 'object') {
                return null;
            }
            const nameSource = [payload.name, payload.title, payload.project_name].find(item => typeof item === 'string' && item.trim());
            const columnsSource = payload.columns || payload.lists || payload.stages || payload.columns_list;
            const columns = this.normalizeAIColumns(columnsSource);
            if (!nameSource && columns.length === 0) {
                return null;
            }
            return {
                name: nameSource ? nameSource.trim() : '',
                columns,
            };
        },

        handleProjectAIRender({rawOutput}) {
            if (!rawOutput) {
                return '';
            }
            const parsed = this.parseProjectAIContent(rawOutput);
            if (!parsed) {
                return rawOutput;
            }
            const blocks = [];
            if (parsed.name) {
                blocks.push(`## ${parsed.name}`);
            }
            if (parsed.columns.length > 0) {
                const lines = parsed.columns.map((column, index) => `${index + 1}. ${column}`);
                blocks.push(lines.join('\n'));
            }
            return blocks.join('\n\n').trim() || rawOutput;
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
            if (!(e.metaKey || e.ctrlKey) || e.shiftKey) return;

            // Ctrl/Cmd + Alt 组合
            if (e.altKey) {
                switch (e.keyCode) {
                    case 76: // L - 下载内容
                        e.preventDefault();
                        this.settingRoute('download')
                        break;
                }
                return;
            }

            // Ctrl/Cmd 组合（无 Alt/Shift）
            switch (e.keyCode) {
                case 66: // B - 新建项目
                    e.preventDefault();
                    this.onAddShow()
                    break;

                case 70:
                case 191: // F、/ - 搜索
                    e.preventDefault();
                    this.$refs.searchBox.onShow();
                    break;

                case 75:
                case 78: // K、N - 新建任务
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

                case 73: // I - AI助手
                    if (this.aiInstalled) {
                        e.preventDefault();
                        this.onOpenAIAssistant();
                    }
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
        },

        onProjectSortEnd() {
            const nonPinnedItems = this.projectDraggableList.filter(item => !item.top_at)
            this.$store.dispatch("call", {
                url: 'project/user/sort',
                data: {
                    list: nonPinnedItems.map(item => item.id)
                },
                method: 'post',
                spinner: 2000
            }).then(({msg}) => {
                nonPinnedItems.forEach((item, index) => {
                    this.$store.dispatch("saveProject", {id: item.id, sort: index})
                })
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                this.projectDraggableList = $A.cloneJSON(this.projectLists)
                $A.modalError(msg)
            }).finally(() => {
                this.projectDragging = false
            })
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
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.createGroupLoad--;
            });
        },

        addDialogMsg({silence, data}) {
            if (silence) {
                return; // 静默消息不通知
            }
            if (!this.natificationReady && !this.$isEEUIApp) {
                return; // 通知未准备好不通知
            }
            if (this.windowActive && data.dialog_id === $A.last(this.dialogIns)?.dialog_id) {
                return; // 窗口激活且最后打开的会话是通知的会话时不通知
            }
            //
            const {id, dialog_id, dialog_type, userid} = data;
            if (userid == this.userId) {
                return; // 自己的消息不通知
            }
            this.__notificationId = id;
            //
            const notificationFuncA = async (title, body) => {
                if (userid === -1) {
                    // AI 助手虚拟用户没有会员记录，取自定义昵称或默认名称
                    if (dialog_type === 'group') {
                        body = ((data.msg && data.msg.nickname) || this.$L('AI 助手')) + ': ' + body;
                    }
                    notificationFuncB(title, body, $A.mainUrl('images/avatar/default_assistant.png'))
                    return;
                }
                const tempUser = await this.$store.dispatch("getUserData", userid).catch(_ => {});
                if (dialog_type === 'group' && tempUser) {
                    body = tempUser.nickname + ': ' + body;
                }
                notificationFuncB(title, body, tempUser?.userimg)
            }
            const notificationFuncB = (title, body, userimg) => {
                if (this.__notificationId === id) {
                    this.__notificationId = null
                    if (this.$isEEUIApp) {
                        emitter.emit('openMobileNotification', {
                            userid: userid,
                            title,
                            desc: body,
                            callback: () => {
                                this.$store.dispatch('openDialog', dialog_id)
                            }
                        });
                    } else if (this.$Electron) {
                        this.$Electron.sendMessage('openNotification', {
                            icon: userimg || $A.originUrl('images/logo.png'),
                            title,
                            body,
                            data,
                            tag: "dialog",
                            hasReply: true,
                            replyPlaceholder: this.$L('回复消息')
                        })
                    } else {
                        this.notificationManage.replaceOptions({
                            icon: userimg || $A.originUrl('images/logo.png'),
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
            const summary = $A.getMsgSimpleDesc(data);
            if (dialog) {
                notificationFuncA(dialog.name, summary)
            } else {
                this.$store.dispatch("getDialogOne", dialog_id).then(({data}) => notificationFuncA(data.name, summary)).catch(() => {})
            }
        },

        openReport(tab) {
            this.workReportTab = tab;
            this.workReportShow = true;
        },

        openFavorite() {
            this.favoriteShow = true;
        },

        openRecent() {
            this.recentShow = true;
        },

        openManageExport(type) {
            switch (type) {
                case 'task':
                    this.exportTaskShow = true;
                    break;
                case 'overdue':
                    this.exportOverdueTask();
                    break;
                case 'checkin':
                    this.exportCheckinShow = true;
                    break;
            }
        },

        handleLongpress(event) {
            const {type, data, element} = this.longpressData;
            this.$store.commit("longpress/clear")
            //
            if (type !== 'manage') {
                return
            }
            const projectItem = this.projectLists.find(item => item.id == data.projectId)
            if (!projectItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(projectItem) ? projectItem : {};
            requestAnimationFrame(() => {
                const rect = element.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX}px`,
                    top: `${rect.top}px`,
                    height: `${rect.height}px`,
                }
                this.operateVisible = true;
            })
        },

        handleOperation({currentTarget}) {
            this.$store.commit("longpress/set", {
                type: 'manage',
                data: {
                    projectId: $A.getAttr(currentTarget, 'data-id')
                },
                element: currentTarget
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

        handleChatClick() {
            this.$store.dispatch("openDialog", this.operateItem.dialog_id).catch(({msg}) => {
                $A.modalError(msg || this.$L('打开会话失败'))
            })
        },

        onTabbarClick(act, params = '') {
            switch (act) {
                case 'createGroup':
                    this.onAddMenu('group')
                    break;
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
                case 'microApp':
                    this.$store.dispatch("openMicroApp", params);
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
                this.$Electron.listener('clickNotification', target => {
                    console.log("[Notification] B Click", target);
                    this.$Electron.sendMessage('mainWindowActive')
                    this.notificationClick(target)
                })
                this.$Electron.listener('replyNotification', target => {
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
                    this.$store.dispatch("increaseTaskMsgNum", {id: data.dialog_id});
                    this.$store.dispatch("increaseMsgReplyNum", {id: data.reply_id});
                    this.$store.dispatch("updateDialogLastMsg", data);
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            }
        },

        /**
         * 加载任务浏览历史
         */
        loadTaskBrowseHistory() {
            if (this.taskBrowseLoading) return

            this.taskBrowseLoading = true
            this.$store.dispatch("getTaskBrowseHistory", 20).then(({data}) => {
                // 更新组件内的浏览历史数据
                this.taskBrowseHistory = data || []
            }).catch(error => {
                console.warn('获取任务浏览历史失败:', error)
                // 失败时保持当前数据不变
            }).finally(() => {
                this.taskBrowseLoading = false
            })
        },
    }
}
</script>
