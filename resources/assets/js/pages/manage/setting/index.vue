<template>
    <div class="page-setting">
        <PageTitle :title="$L(titleNameRoute)"/>
        <div class="setting-head">
            <div class="setting-titbox">
                <div class="setting-title">
                    <h1>{{$L('设置')}}</h1>
                    <div class="setting-more" @click="show768Menu=!show768Menu">
                        <Icon :type="show768Menu ? 'md-close' : 'md-more'" />
                    </div>
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="setting-menu" :class="{'show768-menu':show768Menu}">
                <ul>
                    <li
                        v-for="(item, key) in menu"
                        :key="key"
                        :class="classNameRoute(item.path, item.divided)"
                        @click="toggleRoute(item.path)">{{$L(item.name)}}</li>
                    <li
                        v-if="!!clientNewVersion"
                        :class="classNameRoute('version', true)"
                        @click="toggleRoute('version')">
                        <AutoTip disabled>{{$L('版本')}}: {{version}}</AutoTip>
                        <Badge :text="clientNewVersion"/>
                    </li>
                    <li v-else class="version divided">
                        <AutoTip>{{$L('版本')}}: {{version}}</AutoTip>
                    </li>
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
import {Store} from "le5le-store";

export default {
    data() {
        return {
            curPath: this.$route.path,
            show768Menu: true,

            version: window.systemInfo.version
        }
    },
    mounted() {

    },
    computed: {
        ...mapState(['userInfo', 'userIsAdmin', 'clientNewVersion']),

        menu() {
            let menu = [
                {path: 'personal', name: '个人设置'},
                {path: 'password', name: '密码设置'},
            ]
            if (this.userIsAdmin) {
                menu.push(...[
                    {path: 'system', name: '系统设置', divided: true},
                ])
            }
            return menu;
        },

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
            if (path == 'version') {
                Store.set('updateNotification', null);
                return;
            }
            this.show768Menu = false;
            this.goForward({path: '/manage/setting/' + path});
        },

        classNameRoute(path, divided) {
            return {
                "active": $A.leftExists(this.curPath, '/manage/setting/' + path),
                "divided": !!divided
            };
        },
    }
}
</script>
