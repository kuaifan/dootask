<template>
    <div class="page-setting">
        <PageTitle :title="$L(titleNameRoute)"/>
        <div class="setting-head">
            <div class="setting-titbox">
                <div class="setting-title">
                    <h1>{{$L('设置')}}</h1>
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="setting-menu">
                <ul>
                    <li
                        v-for="(item, key) in menu"
                        v-if="!item.admin||userIsAdmin"
                        :key="key"
                        :class="classNameRoute(item.path)"
                        @click="toggleRoute(item.path)">{{$L(item.name)}}</li>
                </ul>
            </div>
            <div class="setting-content">
                <div class="setting-content-title">{{$L(titleNameRoute)}}</div>
                <div class="setting-content-view"><router-view class="setting-router-view"></router-view></div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            curPath: this.$route.path,

            menu: [
                {path: 'personal', admin: false, name: '个人设置'},
                {path: 'password', admin: false, name: '密码设置'},
                {path: 'system', admin: true, name: '系统设置'},
                {path: 'priority', admin: true, name: '任务等级'},
            ],
        }
    },
    mounted() {

    },
    computed: {
        ...mapState(['userInfo', 'userIsAdmin']),

        titleNameRoute() {
            const {curPath, menu} = this;
            let name = '';
            menu.some((item) => {
                if ($A.leftExists(curPath, '/manage/setting/' + item.path)) {
                    name = item.name;
                    return true;
                }
            })
            return name || '设置';
        }
    },
    watch: {
        '$route' (route) {
            this.curPath = route.path;
        }
    },
    methods: {
        toggleRoute(path) {
            this.goForward({path: '/manage/setting/' + path});
        },

        classNameRoute(path) {
            return {
                "active": $A.leftExists(this.curPath, '/manage/setting/' + path)
            };
        },
    }
}
</script>
