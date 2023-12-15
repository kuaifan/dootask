<template>
    <div class="mobile-tabbar">
        <NetworkException v-if="windowPortrait" type="alert"/>
        <ul class="tabbar-box">
            <li v-for="item in navList" @click="toggleRoute(item.name)" :class="{active: activeName === item.name}">
                <i class="taskfont" v-html="item.icon"></i>
                <div class="tabbar-title">{{ $L(item.label) }}</div>
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
            </li>
        </ul>
    </div>
</template>

<script>
import {Store} from "le5le-store";
import {mapGetters, mapState} from "vuex";
import NetworkException from "../NetworkException";

export default {
    name: "MobileTabbar",
    components: {NetworkException},
    data() {
        return {
            navList: [
                {icon: '&#xe6fb;', name: 'dashboard', label: '仪表盘'},
                {icon: '&#xe6fa;', name: 'project', label: '项目'},
                {icon: '&#xe6eb;', name: 'dialog', label: '消息'},
                {icon: '&#xe6b2;', name: 'contacts', label: '通讯录'},
                {icon: '&#xe60c;', name: 'application', label: '应用'},
            ],
        };
    },

    computed: {
        ...mapState(['cacheDialogs', 'reportUnreadNumber', 'approveUnreadNumber']),
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
            if (['manage-calendar', 'manage-file', 'manage-setting', 'manage-application', 'manage-approve', 'manage-apps'].includes(this.routeName)) {
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
            if (active) {
                return
            }
            $A.eeuiAppSendMessage({
                action: 'setBdageNotify',
                bdage: this.unreadAndOverdue,
            });
        },
    },

    methods: {
        toggleRoute(path) {
            this.$emit("on-click", path)
            //
            let location;
            switch (path) {
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

                default:
                    location = {name: 'manage-' + path};
                    break;
            }
            this.goForward(location);
        },
    },
};
</script>
