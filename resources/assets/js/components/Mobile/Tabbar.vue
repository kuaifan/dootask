<template>
    <div class="mobile-tabbar">
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
                    <Badge v-if="dashboardTask.overdue_count > 0" class="tabbar-badge" type="error" :count="dashboardTask.overdue_count"/>
                    <Badge v-else-if="dashboardTask.today_count > 0" class="tabbar-badge" type="info" :count="dashboardTask.today_count"/>
                    <Badge v-else-if="dashboardTask.all_count > 0" class="tabbar-badge" type="primary" :count="dashboardTask.all_count"/>
                </template>
                <template v-else-if="item.name === 'dialog'">
                    <Badge class="tabbar-badge" :text="msgUnreadMention"/>
                </template>
            </li>
        </ul>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import {Store} from "le5le-store";

export default {
    name: "MobileTabbar",

    data() {
        return {
            isMore: false,

            navList: [
                {icon: '&#xe6fb;', name: 'dashboard', label: '仪表盘'},
                {icon: '&#xe6fa;', name: 'project', label: '项目'},
                {icon: '&#xe6eb;', name: 'dialog', label: '消息'},
                {icon: '&#xe6b2;', name: 'contacts', label: '通讯录'},
                {icon: '&#xe6e9;', name: 'more', label: '更多'},
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
                ]
            ],
        };
    },

    created() {

    },

    mounted() {

    },

    beforeDestroy() {

    },

    computed: {
        ...mapState(['cacheDialogs']),
        ...mapGetters(['dashboardTask']),

        routeName() {
            return this.$route.name
        },

        msgUnreadMention() {
            let num = 0;
            let mention = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog);
                mention += $A.getDialogMention(dialog);
            })
            if (num > 99) {
                num = "99+"
            }
            if (mention > 99) {
                mention = "99+"
            }
            const todoNum = this.msgTodoTotal
            if (todoNum) {
                return mention ? `${todoNum}·@${mention}` : todoNum;
            }
            if (!num) {
                return "";
            }
            return mention ? `${num}·@${mention}` : String(num);
        },

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

        activeName() {
            if (this.isMore || ['manage-calendar', 'manage-file', 'manage-setting'].includes(this.routeName)) {
                return 'more';
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

                case 'addTask':
                case 'addProject':
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
