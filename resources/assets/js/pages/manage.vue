<template>
    <div v-show="userId > 0" class="page-manage">
        <div class="manage-box-menu" :class="{'show768-menu': show768Menu}">
            <Dropdown
                class="manage-box-dropdown"
                trigger="click"
                @on-click="settingRoute"
                @on-visible-change="menuVisibleChange">
                <div :class="['manage-box-title', visibleMenu ? 'menu-visible' : '']">
                    <div class="manage-box-avatar">
                        <UserAvatar :userid="userId" :size="36" tooltipDisabled/>
                    </div>
                    <span>{{userInfo.nickname}}</span>
                    <div class="manage-box-arrow">
                        <Icon type="ios-arrow-up" />
                        <Icon type="ios-arrow-down" />
                    </div>
                </div>
                <DropdownMenu slot="list">
                    <DropdownItem
                        v-for="(item, key) in menu"
                        :key="key"
                        :divided="!!item.divided"
                        :name="item.path">{{$L(item.name)}}</DropdownItem>
                    <Dropdown placement="right-start" @on-click="setLanguage">
                        <DropdownItem divided>
                            <div class="manage-menu-language">
                                {{currentLanguage}}
                                <Icon type="ios-arrow-forward"></Icon>
                            </div>
                        </DropdownItem>
                        <DropdownMenu slot="list">
                            <Dropdown-item v-for="(item, key) in languageList" :key="key" :name="key" :selected="getLanguage() === key">{{item}}</Dropdown-item>
                        </DropdownMenu>
                    </Dropdown>
                    <DropdownItem divided name="signout" style="color:#f40">{{$L('退出登录')}}</DropdownItem>
                </DropdownMenu>
            </Dropdown>
            <ul class="overlay-y">
                <li @click="toggleRoute('dashboard')" :class="classNameRoute('dashboard')">
                    <i class="taskfont">&#xe6fb;</i>
                    <div class="menu-title">{{$L('仪表盘')}}</div>
                    <Badge class="menu-badge" :type="dashboardTask.overdue.length > 0 ? 'error' : 'primary'" :count="dashboardTotal"></Badge>
                </li>
                <li @click="toggleRoute('calendar')" :class="classNameRoute('calendar')">
                    <i class="taskfont">&#xe6f5;</i>
                    <div class="menu-title">{{$L('日历')}}</div>
                </li>
                <li @click="toggleRoute('messenger')" :class="classNameRoute('messenger')">
                    <i class="taskfont">&#xe6eb;</i>
                    <div class="menu-title">{{$L('消息')}}</div>
                    <Badge class="menu-badge" :count="msgAllUnread"></Badge>
                </li>
                <li @click="toggleRoute('file')" :class="classNameRoute('file')">
                    <i class="taskfont">&#xe6f3;</i>
                    <div class="menu-title">{{$L('文件')}}</div>
                </li>
                <li class="menu-project">
                    <ul>
                        <li
                            v-for="(item, key) in projectLists"
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
            <div
                v-if="projectTotal > 50"
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
                        <Option v-for="(item, index) in columns" :value="index" :key="index">{{ item.label }}</Option>
                    </Select>
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

        <!--任务详情-->
        <Modal
            :value="taskId > 0"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: taskData.dialog_id ? '1200px' : '700px'
            }"
            @on-visible-change="taskVisibleChange"
            footer-hide>
            <div class="page-manage-task-modal" :style="taskStyle">
                <TaskDetail :task-id="taskId" :open-task="taskData"/>
            </div>
        </Modal>

        <!--查看所有团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="900">
            <TeamManagement v-if="allUserShow"/>
        </DrawerOverlay>

        <!--查看所有项目-->
        <DrawerOverlay
            v-model="allProjectShow"
            placement="right"
            :size="900">
            <ProjectManagement v-if="allProjectShow"/>
        </DrawerOverlay>

        <!--查看归档项目-->
        <DrawerOverlay
            v-model="archivedProjectShow"
            placement="right"
            :size="900">
            <ProjectArchived v-if="archivedProjectShow"/>
        </DrawerOverlay>

        <!--菜单按钮-->
        <DragBallComponent
            :distanceLeft="0"
            :distanceTop="60"
            @on-click="show768Menu=!show768Menu">
            <div class="manage-mini-menu">
                <Icon :type="show768Menu ? 'md-close' : 'md-menu'" />
            </div>
        </DragBallComponent>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import TaskDetail from "./manage/components/TaskDetail";
import ProjectArchived from "./manage/components/ProjectArchived";
import notificationKoro from "notification-koro1";
import TeamManagement from "./manage/components/TeamManagement";
import ProjectManagement from "./manage/components/ProjectManagement";
import DrawerOverlay from "../components/DrawerOverlay";
import DragBallComponent from "../components/DragBallComponent";
import TaskAdd from "./manage/components/TaskAdd";
import {Store} from "le5le-store";

export default {
    components: {
        TaskAdd,
        DragBallComponent, DrawerOverlay, ProjectManagement, TeamManagement, ProjectArchived, TaskDetail},
    data() {
        return {
            loadIng: 0,

            curPath: this.$route.path,
            mateName: /macintosh|mac os x/i.test(navigator.userAgent) ? '⌘' : 'Ctrl',

            addShow: false,
            addData: {
                name: '',
                columns: '',
            },
            addRule: {},

            addTaskShow: false,
            addTaskSubscribe: null,

            dialogMsgSubscribe: null,

            websocketOpenSubscribe: null,

            columns: [],

            projectKeyValue: '',
            projectKeyAlready: {},
            projectKeyLoading: 0,

            openMenu: {},
            visibleMenu: false,
            show768Menu: false,
            innerHeight: window.innerHeight,

            allUserShow: false,
            allProjectShow: false,
            archivedProjectShow: false,

            natificationHidden: false,
            natificationReady: false,
            notificationClass: null,
        }
    },

    mounted() {
        if ($A.getStorageString("clearCache")) {
            $A.setStorage("clearCache", "")
            $A.messageSuccess("清除成功");
        }
        //
        this.$store.dispatch("getUserInfo");
        this.$store.dispatch("getTaskPriority");
        //
        this.notificationInit();
        this.onVisibilityChange();
        //
        this.addTaskSubscribe = Store.subscribe('addTask', this.onAddTask);
        this.dialogMsgSubscribe = Store.subscribe('dialogMsgPush', this.addDialogMsg);
        this.websocketOpenSubscribe = Store.subscribe('websocketOpen', this.refreshBasic);
        //
        document.addEventListener('keydown', this.shortcutEvent);
        window.addEventListener('resize', this.innerHeightListener);
        //
        if (this.$Electron) {
            this.$Electron.ipcRenderer.send('setDockBadge', 0);
        }
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
        if (this.websocketOpenSubscribe) {
            this.websocketOpenSubscribe.unsubscribe();
            this.websocketOpenSubscribe = null;
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
            'cacheDialogs',
            'cacheProjects',
            'projectTotal',
            'taskId',
        ]),

        ...mapGetters(['taskData', 'dashboardTask']),

        msgAllUnread() {
            let num = 0;
            this.cacheDialogs.map(({unread}) => {
                if (unread) {
                    num += unread;
                }
            })
            return num;
        },

        dashboardTotal() {
            return this.dashboardTask.today.length + this.dashboardTask.overdue.length
        },

        currentLanguage() {
            return this.languageList[this.languageType] || 'Language'
        },

        menu() {
            const {userIsAdmin} = this;
            if (userIsAdmin) {
                return [
                    {path: 'personal', name: '个人设置'},
                    {path: 'password', name: '密码设置'},
                    {path: 'clearCache', name: '清除缓存'},
                    {path: 'system', name: '系统设置', divided: true},
                    {path: 'priority', name: '任务等级'},
                    {path: 'allUser', name: '团队管理', divided: true},
                    {path: 'allProject', name: '所有项目'},
                    {path: 'archivedProject', name: '已归档的项目'}
                ]
            } else {
                return [
                    {path: 'personal', name: '个人设置'},
                    {path: 'password', name: '密码设置'},
                    {path: 'clearCache', name: '清除缓存'},
                    {path: 'archivedProject', name: '已归档的项目', divided: true}
                ]
            }
        },

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = cacheProjects.sort((a, b) => {
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
        }
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

        msgAllUnread() {
            if (this.$Electron) {
                this.$Electron.ipcRenderer.send('setDockBadge', this.msgAllUnread + this.dashboardTotal);
            }
        },

        dashboardTotal() {
            if (this.$Electron) {
                this.$Electron.ipcRenderer.send('setDockBadge', this.msgAllUnread + this.dashboardTotal);
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
            if (!val) {
                this.notificationTimeout = setTimeout(() => {
                    this.notificationClass.close();
                }, 6000);
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

        innerHeightListener() {
            this.innerHeight = window.innerHeight;
        },

        chackPass() {
            if (this.userInfo.changepass === 1) {
                this.goForward({path: '/manage/setting/password'});
            }
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
                case 'clearCache':
                    this.$store.dispatch("handleClearCache", null).then(() => {
                        $A.setStorage("clearCache", $A.randomString(6))
                        window.location.reload()
                    }).catch(() => {
                        window.location.reload()
                    });
                    return;
                case 'signout':
                    $A.modalConfirm({
                        title: '退出登录',
                        content: '你确定要登出系统？',
                        onOk: () => {
                            this.$store.dispatch("logout")
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
                this.$set(this.addData, 'columns', this.columns[index].value.join(','));
            })
        },

        shortcutEvent(e) {
            if (e.keyCode === 75 || e.keyCode === 78) {
                if (e.metaKey || e.ctrlKey) {
                    e.preventDefault();
                    this.onAddTask(0)
                }
            }
        },

        onAddTask(data) {
            this.$refs.addTask.defaultPriority();
            this.$refs.addTask.setData($A.isJson(data) ? data : {
                'owner': this.userId,
                'column_id': data,
            });
            this.addTaskShow = true;
        },

        addDialogMsg(data) {
            if (this.natificationHidden && this.natificationReady) {
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
                    })
                }
            }
        },

        taskVisibleChange(visible) {
            if (!visible) {
                this.$store.dispatch('openTask', 0)
            }
        },

        refreshBasic(num) {
            if (num > 1) {
                this.$store.dispatch("refreshBasicData")
            }
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
                            this.goForward({path: '/manage/messenger'});
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
        }
    }
}
</script>
