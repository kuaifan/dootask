<template>
    <div class="mobile-tabbar">
        <NetworkException v-if="windowPortrait" type="alert"/>
        <transition name="mobile-fade">
            <div v-if="isMore" class="more-mask" @click="toggleRoute('more')"></div>
        </transition>
        <transition name="mobile-slide">
            <div v-if="isMore" class="more-box">
                <div class="tabbar-more-title">{{$L('更多')}}</div>
                <ul v-for="list in navMore">
                    <li v-for="item in list" @click="toggleRoute(item.name)" :class="{active: activeName === item.name}">
                        <div class="more-item">
                            <i class="taskfont" v-html="item.icon"></i>
                            <div class="tabbar-title">{{$L(item.label)}}</div>
                            <Badge v-if="item.name === 'workReport'" class="tabbar-badge" :overflow-count="999" :count="reportUnreadNumber + approveUnreadNumber"/>
                        </div>
                    </li>
                </ul>
            </div>
        </transition>
        <ul class="tabbar-box">
            <li v-for="item in navList" @click="toggleRoute(item.name)" :class="{active: activeName === item.name}">
                <i class="taskfont" v-html="item.icon"></i>
                <div class="tabbar-title">{{$L(item.label)}}</div>
                <template v-if="item.name === 'dashboard'">
                    <Badge v-if="dashboardTask.overdue_count > 0" class="tabbar-badge" type="error" :overflow-count="999" :count="dashboardTask.overdue_count"/>
                    <Badge v-else-if="dashboardTask.today_count > 0" class="tabbar-badge" type="info" :overflow-count="999" :count="dashboardTask.today_count"/>
                    <Badge v-else-if="dashboardTask.all_count > 0" class="tabbar-badge" type="primary" :overflow-count="999" :count="dashboardTask.all_count"/>
                </template>
                <template v-else-if="item.name === 'dialog'">
                    <Badge class="tabbar-badge" :overflow-count="999" :text="msgUnreadMention"/>
                </template>
                <template v-else-if="item.name === 'application'">
                    <Badge class="tabbar-badge" :overflow-count="999" :count="reportUnreadNumber + approveUnreadNumber"/>
                </template>
                <template v-else-if="item.name === 'more'">
                    <Badge class="tabbar-badge" :overflow-count="999" :count="reportUnreadNumber + approveUnreadNumber"/>
                </template>
            </li>
        </ul>
        <Modal
            v-model="scanLoginShow"
            :title="$L('扫码登录')"
            :mask-closable="false">
            <div class="mobile-scan-login-box">
                <div class="mobile-scan-login-title">{{$L(`你好，扫码确认登录`)}}</div>
                <div class="mobile-scan-login-subtitle">「{{$L('为确保帐号安全，请确认是本人操作')}}」</div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="scanLoginShow=false">{{$L('取消登录')}}</Button>
                <Button type="primary" :loading="scanLoginLoad" @click="scanLoginSubmit">{{$L('确认登录')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import {Store} from "le5le-store";
import NetworkException from "../NetworkException";

export default {
    name: "MobileTabbar",
    components: {NetworkException},
    data() {
        return {
            isMore: false,

            navList: [
                {icon: '&#xe6fb;', name: 'dashboard', label: '仪表盘'},
                {icon: '&#xe6fa;', name: 'project', label: '项目'},
                {icon: '&#xe6eb;', name: 'dialog', label: '消息'},
                {icon: '&#xe6b2;', name: 'contacts', label: '通讯录'},
                {icon: '&#xe60c;', name: 'application', label: '应用'},
                // {icon: '&#xe6e9;', name: 'more', label: '更多'},
            ],
            navMore: [
                [
                    {icon: '&#xe6f5;', name: 'calendar', label: '日历'},
                    {icon: '&#xe6f3;', name: 'file', label: '文件'},
                    {icon: '&#xe67b;', name: 'setting', label: '设置'},
                ],
                [
                    {icon: '&#xe7b9;', name: 'addProject', label: '创建项目'},
                    {icon: '&#xe7b8;', name: 'addTask', label: '添加任务'},
                    {icon: '&#xe7c1;', name: 'createMeeting', label: '新会议'},
                    {icon: '&#xe794;', name: 'joinMeeting', label: '加入会议'},
                ],
                [
                    {icon: '&#xe7da;', name: 'workReport', label: '工作报告'},
                    {icon: '&#xe7b9;', name: 'approve', label: '审批中心'},
                    {icon: '&#xe7b9;', name: 'okrManage', label: 'OKR管理'},
                ]
            ],

            scanLoginShow: false,
            scanLoginLoad: false,
            scanLoginCode: '',
        };
    },

    created() {
        if ($A.isEEUiApp) {
            this.navMore[0].splice(2, 0, {icon: '&#xe602;', name: 'scan', label: '扫一扫'})
        }
        if (this.userIsAdmin) {
            this.navMore[2].splice(0, 0, {icon: '&#xe63f;', name: 'allUser', label: '团队管理'})
            this.navMore[2].push({icon: '&#xe7b9;', name: 'okrAnalyze', label: 'OKR结果'})
        }
    },

    mounted() {

    },

    beforeDestroy() {

    },

    computed: {
        ...mapState(['userIsAdmin', 'cacheDialogs', 'reportUnreadNumber', 'approveUnreadNumber']),
        ...mapGetters(['dashboardTask']),

        routeName() {
            return this.$route.name
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
                if (todoNum > 999) {
                    todoNum = "999+"
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

        activeName() {
            if (this.isMore || ['manage-calendar', 'manage-file', 'manage-setting', 'manage-application', 'manage-approve', 'manage-apps'].includes(this.routeName)) {
                // return 'more';
                return 'application';
            }

            if (this.routeName === 'manage-dashboard') {
                return 'dashboard';
            }

            if (this.routeName === 'manage-project' && !/^\d+$/.test(this.$route.params.projectId)) {
                return 'project';
            }
            if (this.routeName === 'manage-messenger') {
                if (this.$route.params.dialogAction === 'contacts') {
                    return 'contacts'
                } else {
                    return 'dialog'
                }
            }
            return ''
        },
    },

    watch: {
        windowActive(active) {
            if (!active) {
                $A.eeuiAppSendMessage({
                    action: 'setBdageNotify',
                    bdage: this.unreadAndOverdue,
                });
            }
        },
    },

    methods: {
        toggleRoute(path) {
            this.$emit("on-click", path)
            if (path != 'more') {
                this.isMore = false
            }
            //
            let location;
            switch (path) {
                case 'more':
                    this.isMore = !this.isMore;
                    return;

                case 'scan':
                    $A.eeuiAppScan(this.scanResult);
                    return;

                case 'addTask':
                case 'addProject':
                case 'allUser':
                case 'workReport':
                    return;

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

                case 'project':
                    location = {name: 'manage-project', params: {projectId: 'all'}};
                    break;

                case 'dialog':
                    location = {name: 'manage-messenger', params: {dialogAction: 'dialog'}};
                    if (this.routeName === 'manage-messenger') {
                        Store.set('clickAgainDialog', true);
                    }
                    break;

                case 'contacts':
                    location = {name: 'manage-messenger', params: {dialogAction: 'contacts'}};
                    break;

                case 'okrManage':
                case 'okrAnalyze':
                    this.goForward({
                        path:'/manage/apps/' + ( path == 'okrManage' ? '/#/list' : '/#/analysis') ,
                        query: {
                            baseUrl: this.okrUrl
                        }
                    });
                    return;

                default:
                    location = {name: 'manage-' + path};
                    break;
            }
            this.goForward(location);
        },

        scanResult(text) {
            const arr = (text + "").match(/^https*:\/\/(.*?)\/login\?qrcode=(.*?)$/)
            if (arr) {
                // 扫码登录
                this.scanLoginCode = arr[2];
                this.scanLoginShow = true;
                return
            }
            if (/^https*:\/\//i.test(text)) {
                // 打开链接
                $A.eeuiAppOpenPage({
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url: text,
                        browser: true,
                        showProgress: true,
                    },
                });
            }
        },

        scanLoginSubmit() {
            if (this.scanLoginLoad === true) {
                return
            }
            this.scanLoginLoad = true
            //
            this.$store.dispatch("call", {
                url: "users/login/qrcode",
                data: {
                    type: "login",
                    code: this.scanLoginCode,
                }
            }).then(({msg}) => {
                this.scanLoginShow = false
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                $A.messageError(msg)
            }).finally(_ => {
                this.scanLoginLoad = false
            });
        }
    },
};
</script>
