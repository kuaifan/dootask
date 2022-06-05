<template>
    <div id="app">
        <keep-alive>
            <router-view class="child-view"></router-view>
        </keep-alive>
        <Spinner/>
        <RightBottom/>
        <NetworkException/>
        <PreviewImageState/>
        <AudioManager/>
        <iframe v-if="manifestUrl" v-show="false" :src="manifestUrl"></iframe>
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
import Spinner from "./components/Spinner";
import RightBottom from "./components/RightBottom";
import PreviewImageState from "./components/PreviewImage/state";
import NetworkException from "./components/NetworkException";
import AudioManager from "./components/AudioManager";
import {mapState} from "vuex";

export default {
    components: {AudioManager, NetworkException, PreviewImageState, RightBottom, Spinner},

    data() {
        return {
            routePath: null,
            manifestUrl: null,
            inter: null,
        }
    },

    created() {
        this.electronEvents();
        this.eeuiEvents();
    },

    mounted() {
        this.inter = setInterval(this.searchEnter, 1000);
        window.addEventListener('resize', this.windowSizeListener);
        window.addEventListener('scroll', this.windowScrollListener);
    },

    beforeDestroy() {
        this.inter && clearInterval(this.inter);
        window.removeEventListener('resize', this.windowSizeListener);
        window.removeEventListener('scroll', this.windowScrollListener);
    },

    computed: {
        ...mapState(['ws']),
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
                    if (this.$openLog) {
                        $A.loadScript('js/vconsole.min.js', (e) => {
                            if (e !== null || typeof window.VConsole !== 'function') {
                                $A.modalError("vConsole 组件加载失败！");
                                return;
                            }
                            window.vConsole = new window.VConsole({
                                onReady: () => {
                                    console.log('vConsole: onReady');
                                },
                                onClearLog: () => {
                                    console.log('vConsole: onClearLog');
                                }
                            });
                        });
                    }
                }
            },
            immediate: true
        },
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

        windowSizeListener() {
            this.$store.state.windowWidth = window.innerWidth
            this.$store.state.windowHeight = window.innerHeight
            this.$store.state.windowLarge = window.innerWidth > 768
            this.$store.state.windowSmall = window.innerWidth <= 768
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
            this.manifestUrl = $A.apiUrl("../manifest")
        },

        eeuiEvents() {
            if (!this.$isEEUiApp) {
                return;
            }
            // 页面失活
            window.__onPagePause = () => {
                if (this.$openLog) {
                    console.log('onPagePause');
                }
                this.$store.dispatch("getBasicData", -1)
            }
            // 页面激活
            window.__onPageResume = (num) => {
                if (this.$openLog) {
                    console.log('onPageResume', num);
                    console.log('ws', this.ws, this.ws ? this.ws.readyState : null);
                }
                if (num > 0) {
                    this.$store.dispatch("getBasicData", 600)
                    if (this.ws === null) {
                        this.$store.dispatch("websocketConnection");
                    } else {
                        this.$store.dispatch("call", {
                            url: 'users/ws/exist',
                        }).catch(_ => {
                            this.$store.dispatch("websocketConnection");
                        });
                    }
                }
            }
        }
    }
}
</script>
