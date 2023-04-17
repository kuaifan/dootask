<template>
    <div id="app">
        <keep-alive>
            <router-view class="child-view"></router-view>
        </keep-alive>

        <!--任务操作-->
        <TaskOperation/>

        <!--全局浮窗加载器-->
        <FloatSpinner/>

        <!--右下角客户端-->
        <RightBottom/>

        <!--网络提示-->
        <NetworkException v-if="windowLarge"/>

        <!--图片预览-->
        <PreviewImageState/>

        <!--Hidden IFrame-->
        <iframe v-for="item in iframes" :key="item.key" v-if="item.url" v-show="false" :src="item.url"></iframe>
    </div>
</template>


<style lang="scss" scoped>
.child-view {
    position: absolute;
    width: 100%;
    min-height: 100%;
    transition: all .3s cubic-bezier(.55, 0, .1, 1);
}
</style>
<script>
import FloatSpinner from "./components/FloatSpinner";
import RightBottom from "./components/RightBottom";
import PreviewImageState from "./components/PreviewImage/state";
import NetworkException from "./components/NetworkException";
import TaskOperation from "./pages/manage/components/TaskOperation";
import {mapState} from "vuex";
import {languageType} from "./language";

export default {
    components: {TaskOperation, NetworkException, PreviewImageState, RightBottom, FloatSpinner},

    data() {
        return {
            routePath: null,
            searchInter: null,
            iframes: [],
        }
    },

    created() {
        this.electronEvents();
        this.eeuiEvents();
        this.otherEvents();
        this.synchThemeLanguage();
    },

    mounted() {
        window.addEventListener('resize', this.windowSizeListener);
        window.addEventListener('scroll', this.windowScrollListener);
        this.searchInter = setInterval(this.searchEnter, 1000);
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.windowSizeListener);
        window.removeEventListener('scroll', this.windowScrollListener);
        this.searchInter && clearInterval(this.searchInter);
    },

    computed: {
        ...mapState(['ws', 'themeMode', 'supportTouch']),

        isSoftware() {
            return this.$Electron || this.$isEEUiApp;
        },
    },

    watch: {
        '$route': {
            handler(to) {
                this.routePath = to.path
            },
            immediate: true,
        },

        routePath: {
            handler(path) {
                if (path && this.userId > 0) {
                    path = path.replace(/^\/manage\/file\/\d+\/(\d+)$/, "/single/file/$1")
                    this.$store.dispatch("websocketPath", path)
                }
            },
            immediate: true
        },

        userId: {
            handler() {
                this.$store.dispatch("websocketConnection");
                //
                if (this.userId > 0) {
                    if (this.$isEEUiApp) {
                        $A.eeuiAppSendMessage({
                            action: 'intiUmeng',
                        });
                        setTimeout(_ => {
                            $A.eeuiAppSendMessage({
                                action: 'setUmengAlias',
                                userid: this.userId,
                                token: this.userToken,
                                url: $A.apiUrl('users/umeng/alias')
                            });
                        }, 6000)
                    }
                    //
                    $A.IDBString("logOpen").then(r => {
                        $A.openLog = r === "open"
                        if ($A.openLog) {
                            $A.loadScript('js/vconsole.min.js').then(_ => {
                                window.vConsole = new window.VConsole({
                                    onReady: () => {
                                        console.log('vConsole: onReady');
                                    },
                                    onClearLog: () => {
                                        console.log('vConsole: onClearLog');
                                    }
                                });
                            }).catch(_ => {
                                $A.modalError("vConsole 组件加载失败！");
                            })
                        }
                    })
                }
            },
            immediate: true
        },

        supportTouch: {
            handler(support) {
                if (support) {
                    $A("body").addClass("support-touch")
                } else {
                    $A("body").removeClass("support-touch")
                }
            },
            immediate: true
        },

        windowActive(active) {
            if (active) {
                this.__windowTimer && clearTimeout(this.__windowTimer)
                this.__windowTimer = setTimeout(_ => {
                    this.$store.dispatch("call", {
                        url: "users/socket/status",
                    }).then(_ => {
                        this.$store.dispatch("websocketSend", {
                            type: 'handshake',
                        }).catch(_ => {
                            this.$store.dispatch("websocketConnection")
                        })
                    }).catch(_ => {
                        this.$store.dispatch("websocketConnection")
                    })
                    if (this.themeMode === "auto") {
                        $A.dark.autoDarkMode()
                    }
                }, 600)
            } else {
                this.$store.dispatch("audioStop", true)
            }
        },

        themeMode() {
            this.synchThemeLanguage();
        }
    },

    methods: {
        searchEnter() {
            let row = $A(".sreachBox");
            if (row.length === 0) {
                return;
            }
            if (row.attr("data-enter-init") === "init") {
                return;
            }
            row.attr("data-enter-init", "init");
            //
            let buttons = row.find("button[type='button']");
            let button = null;
            if (buttons.length === 0) {
                return;
            }
            buttons.each((index, item) => {
                if ($A(item).text().indexOf("搜索")) {
                    button = $A(item);
                }
            });
            if (button === null) {
                return;
            }
            row.find("input.ivu-input").keydown(function (e) {
                if (e.keyCode == 13) {
                    if (!button.hasClass("ivu-btn-loading")) {
                        button.click();
                    }
                }
            });
        },

        synchThemeLanguage() {
            if (this.isSoftware) {
                this.iframes = this.iframes.filter(({key}) => key != 'synchThemeLanguage')
                this.iframes.push({
                    key: 'synchThemeLanguage',
                    url: $A.apiUrl(`../setting/theme_language?theme=${this.themeMode}&language=${languageType}`)
                })
            }
        },

        windowSizeListener() {
            this.$store.state.windowWidth = $A(window).width()
            this.$store.state.windowHeight = $A(window).height()
            this.$store.state.windowLarge = this.$store.state.windowWidth > 768
            this.$store.state.windowSmall = this.$store.state.windowWidth <= 768
            this.$store.state.formLabelPosition = this.$store.state.windowWidth > 576 ? 'right' : 'top'
            this.$store.state.formLabelWidth = this.$store.state.windowWidth > 576 ? 'auto' : ''
        },

        windowScrollListener() {
            this.$store.state.windowScrollY = window.scrollY
        },

        electronEvents() {
            if (!this.$Electron) {
                return;
            }
            window.__onBeforeUnload = () => {
                if (this.$Modal.removeLast()) {
                    return true;
                }
            }
            this.$Electron.registerMsgListener('dispatch', args => {
                if (!$A.isJson(args)) {
                    return;
                }
                let {action, data} = args;
                this.$store.dispatch(action, data);
            })
            this.$Electron.registerMsgListener('browserWindowBlur', _ => {
                this.$store.state.windowActive = false;
            })
            this.$Electron.registerMsgListener('browserWindowFocus', _ => {
                this.$store.state.windowActive = true;
            })
            this.iframes.push({
                key: 'manifest',
                url: $A.apiUrl("../manifest")
            })
            $A.bindScreenshotKey($A.jsonParse(window.localStorage.getItem("__keyboard:data__")) || {});
        },

        eeuiEvents() {
            if (!this.$isEEUiApp) {
                return;
            }
            // 页面失活
            window.__onPagePause = () => {
                this.$store.state.windowActive = false;
                this.$store.dispatch("getBasicData", -1);
            }
            // 页面激活
            window.__onPageResume = (num) => {
                this.$store.state.windowActive = true;
                if (num > 0) {
                    this.$store.dispatch("getBasicData", 600)
                }
            }
            // 通知权限
            window.__onNotificationPermissionStatus = (ret) => {
                this.$store.state.appNotificationPermission = $A.runNum(ret) == 1;
            }
        },

        otherEvents() {
            if (!this.isSoftware) {
                // 非客户端监听窗口激活
                const hiddenProperty = 'hidden' in document ? 'hidden' : 'webkitHidden' in document ? 'webkitHidden' : 'mozHidden' in document ? 'mozHidden' : null;
                const visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');
                document.addEventListener(visibilityChangeEvent, () => {
                    this.$store.state.windowActive = !document[hiddenProperty]
                });
            }
        },
    }
}
</script>
