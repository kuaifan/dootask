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
                <div v-if="windowPortrait" class="setting-profile" @click="toggleRoute('personal')">
                    <UserAvatar :userid="userId" :size="52" :show-state-dot="false"/>
                    <div class="setting-profile-info">
                        <div class="setting-profile-name">{{userInfo.nickname}}</div>
                        <div class="setting-profile-email">{{userInfo.email}}</div>
                    </div>
                    <IonIcon class="setting-menu-arrow" :icon="chevronForward"/>
                </div>
                <ul>
                    <li
                        v-for="(item, key) in (windowPortrait ? mobileMenu : menu)"
                        :key="key"
                        :class="classNameRoute(item.path, item.divided, item.danger)"
                        @click="toggleRoute(item.path)">
                        <IonIcon v-if="item.icon" class="setting-menu-icon" :icon="item.icon"/>
                        <template v-if="item.path === 'device'">
                            <AutoTip>{{$L(item.name)}}</AutoTip>
                            <span v-if="deviceCount > 0" class="op-8">{{deviceCount}}</span>
                        </template>
                        <template v-else-if="item.path === 'version'">
                            <AutoTip disabled>{{$L(item.name)}}</AutoTip>
                            <Badge v-if="!!clientNewVersion" :text="clientNewVersion"/>
                        </template>
                        <template v-else-if="item.path === 'system-update'">
                            <AutoTip disabled>{{$L(item.name)}}</AutoTip>
                            <Badge v-if="!!systemNewVersion" :text="systemNewVersion"/>
                        </template>
                        <template v-else-if="item.path === 'version-show'">
                            <AutoTip v-if="windowPortrait">{{$L(item.name)}}</AutoTip>
                            <AutoTip v-else>{{$L(item.name)}}: {{clientVersion}}</AutoTip>
                            <span v-if="windowPortrait" class="setting-menu-value">{{clientVersion}}</span>
                            <span v-if="canCheckUpdate" class="check-update" @click.stop="onCheckUpdate">{{$L('检查更新')}}</span>
                        </template>
                        <template v-else-if="item.value">
                            <AutoTip>{{$L(item.name)}}</AutoTip>
                            <span class="setting-menu-value">{{item.value}}</span>
                        </template>
                        <span v-else>{{$L(item.name)}}</span>
                        <IonIcon v-if="item.arrow" class="setting-menu-arrow" :icon="chevronForward"/>
                    </li>
                </ul>
                <div v-if="windowPortrait" class="setting-logout" @click="toggleRoute('logout')">{{$L('退出登录')}}</div>
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
import {languageList, languageName} from "../../../i18n";
import {
    chevronForward,
    contrastOutline,
    documentTextOutline,
    informationCircleOutline,
    keyOutline,
    keypadOutline,
    languageOutline,
    lockClosedOutline,
    mailOutline,
    personRemoveOutline,
    phonePortraitOutline,
    settingsOutline,
    shieldCheckmarkOutline,
    syncOutline,
    trashOutline,
} from "ionicons/icons";

export default {
    components: {MobileNavTitle},
    data() {
        return {
            deviceCount: 0,
            serverVersion: null,
            clientVersion: window.systemInfo.version,
            canCheckUpdate: $A.eeuiAppIsExpo(),
            chevronForward,
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
        ...mapState(['userId', 'userInfo', 'userIsAdmin', 'clientNewVersion', 'systemNewVersion', 'systemConfig', 'themeConf', 'themeList']),

        showContent() {
            return this.$route.path.match(/^\/manage\/setting\/[\w-]+$/)
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
            if (this.userIsAdmin) {
                menu.push({path: 'system-update', name: '系统更新', divided: true})
            }
            menu.push(...[
                {path: 'version', name: this.clientNewVersion ? '更新客户端' : '更新日志', title: '更新日志', divided: !this.userIsAdmin},
                {path: 'version-show', name: '版本'},
                {path: 'device', name: '登录设备', divided: true},
                {path: 'clearCache', name: '清除缓存'},
                {path: 'logout', name: '退出登录'},
            ])
            return menu;
        },

        /**
         * 移动端菜单：个人设置移至顶部资料卡、退出登录改为底部按钮，其余按语义重新分组
         */
        mobileMenu() {
            const theme = this.themeList.find(item => item.value === this.themeConf)
            const groups = [
                [
                    {path: 'password', name: '密码设置', icon: lockClosedOutline},
                    {path: 'email', name: '修改邮箱', icon: mailOutline},
                    {path: 'device', name: '登录设备', icon: phonePortraitOutline},
                ],
                [
                    {path: 'language', name: '语言设置', icon: languageOutline, value: languageList[languageName]},
                    {path: 'theme', name: '主题设置', icon: contrastOutline, value: theme ? this.$L(theme.name) : ''},
                    (this.$Electron || this.$isEEUIApp) && {path: 'keyboard', name: '键盘设置', icon: keypadOutline},
                ],
                this.userIsAdmin && [
                    {path: 'system', name: '系统设置', icon: settingsOutline},
                    {path: 'license', name: 'License Key', icon: keyOutline},
                    {path: 'system-update', name: '系统更新', icon: syncOutline},
                ],
                [
                    {path: 'version-show', name: '版本', icon: informationCircleOutline},
                    {path: 'version', name: this.clientNewVersion ? '更新客户端' : '更新日志', title: '更新日志', icon: documentTextOutline},
                    $A.isDooServer() && this.$isEEUIApp && {path: 'privacy', name: '隐私政策', icon: shieldCheckmarkOutline},
                ],
                [
                    {path: 'clearCache', name: '清除缓存', icon: trashOutline},
                    $A.isDooServer() && this.$isEEUIApp && {path: 'delete', name: '删除帐号', icon: personRemoveOutline, danger: true},
                ],
            ]
            const menu = []
            groups.filter(Boolean).forEach(group => {
                group.filter(Boolean).forEach((item, index) => {
                    menu.push(Object.assign(item, {
                        divided: index === 0,
                        arrow: item.path !== 'version-show',
                    }))
                })
            })
            return menu
        },

        titleNameRoute() {
            const {routeName, menu} = this;
            let name = '';
            menu.some((item) => {
                if (routeName === `manage-setting-${item.path}`) {
                    name = `${this.$L(item.title || item.name)}${item.desc||''}`;
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
        },
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

        classNameRoute(path, divided, danger) {
            return {
                "flex": true,
                "active": this.windowLandscape && this.routeName === `manage-setting-${path}`,
                "divided": !!divided,
                "danger": !!danger
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

        onCheckUpdate() {
            emitter.emit('checkAppUpdate', true);
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
