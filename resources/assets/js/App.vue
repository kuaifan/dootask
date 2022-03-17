<template>
    <div id="app">
        <transition :name="transitionName">
            <keep-alive>
                <router-view class="child-view" :class="{'view-768': $store.state.windowMax768}"></router-view>
            </keep-alive>
        </transition>
        <Spinner/>
        <RightBottom/>
        <PreviewImage/>
    </div>
</template>

<script>
import Spinner from "./components/Spinner";
import RightBottom from "./components/RightBottom";
import PreviewImage from "./components/PreviewImage";
import {mapState} from "vuex";

export default {
    components: {PreviewImage, RightBottom, Spinner},

    data() {
        return {
            transitionName: null,
        }
    },

    created() {
        this.electronEvents();
    },

    mounted() {
        let hash = window.location.hash;
        if (hash.indexOf("#") === 0) {
            hash = hash.substr(1);
            if (hash) {
                this.$nextTick(() => {
                    hash = $A.removeURLParameter(hash, 'token');
                    this.goForward({path: hash});
                });
            }
        }
        this.sessionStorage('/', 1);
        let pathname = window.location.pathname;
        if (pathname && this.sessionStorage(pathname) === 0) {
            this.sessionStorage(pathname, this.sessionStorage('::count') + 1);
        }
        //
        setInterval(this.searchEnter, 1000);
        //
        window.addEventListener('resize', this.windowSizeListener);
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.windowSizeListener);
    },

    computed: {
        ...mapState(['taskId', 'cacheDrawerOverlay']),
    },

    watch: {
        '$route'(To, From) {
            if (this.transitionName === null) {
                this.transitionName = 'app-slide-no';
                return;
            }
            if (typeof To.name === 'undefined' || typeof From.name === 'undefined') {
                return;
            }
            this.slideType(To, From);
        }
    },

    methods: {
        slideType(To, From) {
            let isBack = this.$router.isBack;
            this.$router.isBack = false;
            //
            let ToIndex = this.sessionStorage(To.path);
            let FromIndex = this.sessionStorage(From.path);
            if (ToIndex && ToIndex < FromIndex) {
                isBack = true;      //后退
                this.sessionStorage(true, ToIndex);
            } else {
                isBack = false;     //前进
                this.sessionStorage(To.path, this.sessionStorage('::count') + 1);
            }
            //
            if (To.meta.slide === false || From.meta.slide === false) {
                //取消动画
                this.transitionName = 'app-slide-no'
            } else if (To.meta.slide === 'up' || From.meta.slide === 'up' || To.meta.slide === 'down' || From.meta.slide === 'down') {
                //上下动画
                if (isBack) {
                    this.transitionName = 'app-slide-down'
                } else {
                    this.transitionName = 'app-slide-up'
                }
            } else {
                //左右动画（默认）
                if (isBack) {
                    this.transitionName = 'app-slide-right'
                } else {
                    this.transitionName = 'app-slide-left'
                }
            }
        },

        sessionStorage(path, num) {
            let conut = 0;
            let history = JSON.parse(window.sessionStorage['__history__'] || '{}');
            if (path === true) {
                let items = {};
                for (let i in history) {
                    if (history.hasOwnProperty(i)) {
                        if (parseInt(history[i]) <= num) {
                            items[i] = history[i];
                            conut++;
                        }
                    }
                }
                history = items;
                history['::count'] = Math.max(num, conut);
                window.sessionStorage['__history__'] = JSON.stringify(history);
                return history;
            }
            if (typeof num === 'undefined') {
                return parseInt(history[path] || 0);
            }
            if (path === "/") num = 1;
            history[path] = num;
            for (let key in history) {
                if (history.hasOwnProperty(key) && key !== '::count') {
                    conut++;
                }
            }
            history['::count'] = Math.max(num, conut);
            window.sessionStorage['__history__'] = JSON.stringify(history);
        },

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
            this.$store.state.windowMax768 = window.innerWidth <= 768
        },

        electronEvents() {
            if (!this.$Electron) {
                return;
            }
            window.__onBeforeUnload = () => {
                if (this.$Modal.removeLast()) {
                    return true;
                }
                if (this.cacheDrawerOverlay.length > 0) {
                    this.cacheDrawerOverlay[this.cacheDrawerOverlay.length - 1].close();
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
        }
    }
}
</script>

<style lang="scss" scoped>
    .child-view {
        position: absolute;
        width: 100%;
        min-height: 100%;
        transition: all .3s cubic-bezier(.55, 0, .1, 1);
    }
    .app-slide-no-leave-to {display: none;}
    /**
     * 左右模式
     */
    .app-slide-left-leave-active{z-index:1;transform:translate(0,0)}
    .app-slide-left-leave-to{z-index:1;transform:translate(0,0)}
    .app-slide-left-enter-active{opacity:0;z-index:2;transform:translate(30%,0)}
    .app-slide-left-enter-to{opacity:1;z-index:2;transform:translate(0,0)}
    .app-slide-right-leave-active{opacity:1;z-index:2;transform:translate(0,0)}
    .app-slide-right-leave-to{opacity:0;z-index:2;transform:translate(30%,0)}
    .app-slide-right-enter-active{z-index:1;transform:translate(0,0)}
    .app-slide-right-enter{z-index:1;transform:translate(0,0)}

    /**
     * 上下模式
     */
    .app-slide-up-leave-active{z-index:1;transform:translate(0,0)}
    .app-slide-up-leave-to{z-index:1;transform:translate(0,0)}
    .app-slide-up-enter-active{opacity:0;z-index:2;transform:translate(0,20%)}
    .app-slide-up-enter-to{opacity:1;z-index:2;transform:translate(0,0)}
    .app-slide-down-leave-active{opacity:1;z-index:2;transform:translate(0,0)}
    .app-slide-down-leave-to{opacity:0;z-index:2;transform:translate(0,20%)}
    .app-slide-down-enter-active{z-index:1;transform:translate(0,0)}
    .app-slide-down-enter{z-index:1;transform:translate(0,0)}
</style>
