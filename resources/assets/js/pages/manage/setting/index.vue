<template>
    <div class="page-setting">
        <PageTitle :title="titleNameRoute"/>
        <div class="setting-head">
            <div class="setting-titbox">
                <div class="setting-title">
                    <h1>{{settingTitleName}}</h1>
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="setting-menu">
                <MobileNavTitle :title="$L('设置')"/>
                <ul>
                    <li
                        v-for="(item, key) in menu"
                        :key="key"
                        :class="classNameRoute(item.path, item.divided)"
                        @click="toggleRoute(item.path)">
                        <template v-if="item.path === 'device'">
                            <AutoTip>{{$L(item.name)}}</AutoTip>
                            <span v-if="deviceCount > 0" class="op-8">{{deviceCount}}</span>
                        </template>
                        <template v-else-if="item.path === 'version'">
                            <AutoTip disabled>{{$L(item.name)}}</AutoTip>
                            <Badge v-if="!!clientNewVersion" :text="clientNewVersion"/>
                        </template>
                        <template v-else-if="item.path === 'version-show'">
                            <AutoTip>{{$L(item.name)}}: {{clientVersion}}</AutoTip>
                        </template>
                        <span v-else>{{$L(item.name)}}</span>
                    </li>
                </ul>
            </div>
            <transition :name="$isEEUIApp ? 'mobile-dialog' : 'none'">
                <div v-if="showContent" class="setting-content">
                    <MobileNavTitle :title="settingTitleName"/>
                    <div class="setting-content-title">{{titleNameRoute}}</div>
                    <div class="setting-content-view">
                        <router-view class="setting-router-view"></router-view>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import axios from "axios";
import MobileNavTitle from "../../../components/Mobile/NavTitle.vue";
import emitter from "../../../store/events";

export default {
    components: {MobileNavTitle},
    data() {
        return {
            deviceCount: 0,
            serverVersion: null,
            clientVersion: window.systemInfo.version,
        }
    },

    mounted() {
        if (this.$isEEUIApp) {
            this.clientVersion = `${window.systemInfo.version} (${$A.eeuiAppLocalVersion()})`
        }
    },

    activated() {
        this.getVersion();
    },

    computed: {
        ...mapState(['userInfo', 'userIsAdmin', 'clientNewVersion', 'systemConfig']),

        showContent() {
            return this.$route.path.match(/^\/manage\/setting\/\w+$/)
        },

        menu() {
            const menu = [
                {path: 'personal', name: '个人设置'},
                {path: 'password', name: '密码设置'},
                {path: 'email', name: '修改邮箱'},
                {path: 'language', name: '语言设置', divided: true},
                {path: 'theme', name: '主题设置'},
            ]

            if (this.$Electron || this.$isEEUIApp) {
                menu.push({path: 'keyboard', name: '键盘设置'})
            }

            if ($A.isDooServer() && this.$isEEUIApp) {
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
                {path: 'version', name: '更新日志', divided: true},
                {path: 'version-show', name: '版本'},
                {path: 'device', name: '登录设备', divided: true},
                {path: 'clearCache', name: '清除缓存'},
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
                        loading: true,
                        onOk: () => {
                            return new Promise(async resolve => {
                                await this.$store.dispatch("logout", false)
                                resolve()
                            })
                        }
                    });
                    break;

                case 'version-show':
                    this.onVersion();
                    break;

                case 'privacy':
                    this.openPrivacy();
                    break;

                case 'index':
                    this.goForward({name: 'manage-setting'});
                    break;

                default:
                    if (path === 'version' && !!this.clientNewVersion) {
                        emitter.emit('updateNotification', null);
                        return
                    }
                    this.goForward({name: 'manage-setting-' + path});
                    break;
            }
        },

        openPrivacy() {
            const url = $A.apiUrl('privacy')
            if (this.$isEEUIApp) {
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {url},
                });
            } else {
                window.open(url)
            }
        },

        classNameRoute(path, divided) {
            return {
                "flex": true,
                "active": this.windowLandscape && this.routeName === `manage-setting-${path}`,
                "divided": !!divided
            };
        },

        async onVersion() {
            const array = [
                `${this.$L('服务器')}: ${$A.mainDomain()}`
            ]
            if (this.serverVersion) {
                array.push(`${this.$L('服务器版本')}: v${this.serverVersion}`)
            } else {
                array.push(`${this.$L('服务器版本')}: ` + this.$L('获取失败'))
            }
            array.push(`${this.$L('客户端版本')}: v${this.clientVersion}`)
            //
            $A.modalInfo({
                language: false,
                title: this.$L('版本信息'),
                content: array.join('<br/>')
            })
        },

        getVersion() {
            this.versionTimer && clearTimeout(this.versionTimer)
            this.versionTimer = setTimeout(() => {
                this.$store.dispatch("call", {
                    url: 'system/version',
                }).then(({data}) => {
                    this.serverVersion = data.version
                    this.deviceCount = data.device_count
                }).catch(() => {
                    // console.log('获取版本失败')
                })
            }, this.versionTimer ? 1000 : 0)
        },

        updateDeviceCount(num) {
            this.deviceCount = num
        },
    }
}
</script>
