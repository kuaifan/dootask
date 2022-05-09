<template>
    <div class="mobile-tabbar">
        <ul>
            <li v-for="item in navList" @click="toggleRoute(item.name)" :class="{active: activeName === item.name}">
                <i class="taskfont" v-html="item.icon"></i>
                <div class="tabbar-title">{{$L(item.label)}}</div>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "MobileTabbar",
    props: {

    },

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
            if ($A.leftExists(this.routeName, 'manage-setting')) {
                return 'setting'
            }
            return ''
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
