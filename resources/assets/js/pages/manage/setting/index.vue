<template>
    <div class="page-setting">
        <PageTitle :title="titleNameRoute"/>
        <div class="setting-head">
            <div class="setting-titbox">
                <div class="setting-title">
                    <div v-if="showMobileBox" class="common-nav-back portrait" @click="goForward({name: 'manage-application'},true)"><i class="taskfont">&#xe676;</i></div>
                    <h1>{{settingTitleName}}</h1>
                    <div v-if="!showMobileBox" class="setting-more" @click="goBack()">
                        <Icon type="md-close" />
                    </div>
                </div>
            </div>
        </div>
        <div class="setting-box" :class="{'show-mobile-box':showMobileBox}">
            <div class="setting-menu">
                <ul>
                    <li
                        v-for="(item, key) in menu"
                        :key="key"
                        :class="classNameRoute(item.path, item.divided)"
                        @click="toggleRoute(item.path)">{{$L(item.name)}}</li>
                    <li
                        v-if="!!clientNewVersion"
                        class="flex"
                        :class="classNameRoute('version', true)"
                        @click="toggleRoute('version')">
                        <AutoTip disabled>{{$L('版本')}}: {{version}}</AutoTip>
                        <Badge :text="clientNewVersion"/>
                    </li>
                    <li v-else class="version divided" @click="onVersion">
                        <AutoTip>{{$L('版本')}}: {{version}}</AutoTip>
                    </li>
                </ul>
            </div>
            <div class="setting-content">
                <div class="setting-content-title">{{titleNameRoute}}</div>
                <div class="setting-content-view">
                    <router-view class="setting-router-view"></router-view>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {Store} from "le5le-store";
import axios from "axios";

export default {
    data() {
        return {
            version: window.systemInfo.version
        }
    },

    mounted() {

    },

    computed: {
        ...mapState(['userInfo', 'userIsAdmin', 'clientNewVersion']),

        routeName() {
            return this.$route.name
        },

        showMobileBox() {
            return this.routeName === 'manage-setting'
        },

        menu() {
            const menu = [
                {path: 'personal', name: '个人设置'},
                {path: 'password', name: '密码设置'},
                {path: 'email', name: '修改邮箱'},
                {path: 'language', name: '语言设置', divided: true},
                {path: 'theme', name: '主题设置'},
            ]

            if (this.$Electron || this.$isEEUiApp) {
                menu.push({path: 'keyboard', name: '键盘设置', desc: ' (Beta)'})
            }

            if ($A.isDooServer() && this.$isEEUiApp) {
                menu.push(...[
                    {path: 'privacy', name: '隐私政策', divided: true},
                    {path: 'delete', name: '删除帐号'},
                ])
            }

            if (this.userIsAdmin) {
                menu.push(...[
                    {path: 'system', name: '系统设置', divided: true},
                    {path: 'license', name: 'License Key'},
                ])
            }
            menu.push(...[
                {path: 'clearCache', name: '清除缓存', divided: true},
                {path: 'logout', name: '退出登录'},
            ])
            return menu;
        },

        titleNameRoute() {
            const {routeName, menu} = this;
            let name = '';
            menu.some((item) => {
                if (routeName === `manage-setting-${item.path}`) {
                    name = `${this.$L(item.name)}${item.desc||''}`;
                    return true;
                }
            })
            return name || this.$L('设置');
        },

        settingTitleName() {
            if (this.windowPortrait) {
                return this.titleNameRoute
            }
            return this.$L('设置')
        },
    },

    watch: {
        routeName: {
            handler(name) {
                if (name === 'manage-setting' && this.windowLandscape) {
                    this.goForward({name: 'manage-setting-personal'}, true);
                }
            },
            immediate: true
        }
    },

    methods: {
        toggleRoute(path) {
            switch (path) {
                case 'clearCache':
                    $A.modalConfirm({
                        title: '清除缓存',
                        content: '你确定要清除缓存吗？',
                        onOk: () => {
                            $A.IDBSet("clearCache", "handle").then(_ => {
                                $A.reloadUrl()
                            });
                        }
                    });
                    break;

                case 'logout':
                    $A.modalConfirm({
                        title: '退出登录',
                        content: '你确定要登出系统吗？',
                        onOk: () => {
                            this.$store.dispatch("logout", false)
                        }
                    });
                    break;

                case 'version':
                    Store.set('updateNotification', null);
                    break;

                case 'privacy':
                    this.openPrivacy();
                    break;

                case 'index':
                    this.goForward({name: 'manage-setting'});
                    break;

                default:
                    this.goForward({name: 'manage-setting-' + path});
                    break;
            }
        },

        openPrivacy() {
            const url = $A.apiUrl('privacy')
            if (this.$isEEUiApp) {
                $A.eeuiAppOpenPage({
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url,
                        browser: true,
                        showProgress: true,
                    },
                });
            } else {
                window.open(url)
            }
        },

        classNameRoute(path, divided) {
            return {
                "active": this.windowLandscape && this.routeName === `manage-setting-${path}`,
                "divided": !!divided
            };
        },

        onVersion() {
            axios.get($A.apiUrl('system/version')).then(({status, data}) => {
                if (status === 200) {
                    let content = `${this.$L('服务器')}: ${$A.getDomain($A.apiUrl('../'))}`
                    content += `<br/>${this.$L('服务器版本')}: v${data.version}`
                    content += `<br/>${this.$L('客户端版本')}: v${this.version}`
                    $A.modalInfo({
                        language: false,
                        title: '版本信息',
                        content
                    })
                }
            }).catch(_ => { })
        },
    }
}
</script>
