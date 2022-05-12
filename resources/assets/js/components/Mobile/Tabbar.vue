<template>
    <div class="mobile-tabbar">
        <ul>
            <li v-for="item in navList" @click="toggleRoute(item.name)" :class="{active: activeName === item.name}">
                <i class="taskfont" v-html="item.icon"></i>
                <div class="tabbar-title">{{$L(item.label)}}</div>
                <template v-if="item.name === 'dashboard'">
                    <Badge v-if="dashboardTask.overdue.length > 0" class="tabbar-badge" type="error" :count="dashboardTask.overdue.length"/>
                    <Badge v-else-if="dashboardTask.today.length > 0" class="tabbar-badge" type="info" :count="dashboardTask.today.length"/>
                    <Badge v-else-if="dashboardTask.all.length > 0" class="tabbar-badge" type="primary" :count="dashboardTask.all.length"/>
                </template>
                <template v-else-if="item.name === 'dialog'">
                    <Badge class="tabbar-badge" :text="msgUnreadMention"/>
                </template>
            </li>
        </ul>
        <div class="mobile-back"></div>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";

export default {
    name: "MobileTabbar",

    data() {
        return {
            navList: [
                {icon: '&#xe736;', name: 'dashboard', label: '仪表盘'},
                {icon: '&#xe732;', name: 'project', label: '项目'},
                {icon: '&#xe71e;', name: 'dialog', label: '消息'},
                {icon: '&#xe6b2;', name: 'contacts', label: '通讯录'},
                {icon: '&#xe61a;', name: 'setting', label: '我的'},
            ]
        };
    },

    created() {

    },

    mounted() {

    },

    beforeDestroy() {

    },

    computed: {
        ...mapState([
            'cacheDialogs',
        ]),
        ...mapGetters(['dashboardTask']),

        routeName() {
            return this.$route.name
        },

        activeName() {
            if (this.routeName === 'manage-dashboard') {
                return 'dashboard';
            }

            if (this.routeName === 'manage-project' && !/^\d+$/.test(this.$route.params.projectId)) {
                return 'project';
            }
            if (this.routeName === 'manage-messenger') {
                if (this.$route.params.dialogId === 'contacts') {
                    return 'contacts'
                } else {
                    return 'dialog'
                }
            }
            if (this.routeName === 'manage-setting') {
                return 'setting';
            }
            return ''
        },

        msgUnreadMention() {
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
    },

    methods: {
        toggleRoute(path) {
            let location;
            switch (path) {
                case 'project':
                    location = {name: 'manage-project', params: {projectId: 'all'}};
                    break;

                case 'dialog':
                    location = {name: 'manage-messenger', params: {dialogId: 'dialog'}};
                    break;

                case 'contacts':
                    location = {name: 'manage-messenger', params: {dialogId: 'contacts'}};
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
