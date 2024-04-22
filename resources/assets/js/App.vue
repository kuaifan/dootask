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

        <!--图片预览-->
        <PreviewImageState/>

        <!--网络提示-->
        <NetworkException v-if="windowLandscape"/>

        <!--引导页-->
        <GuidePage/>
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
import GuidePage from "./components/GuidePage";
import TaskOperation from "./pages/manage/components/TaskOperation";
import {mapState} from "vuex";

export default {
    components: {TaskOperation, NetworkException, PreviewImageState, RightBottom, FloatSpinner, GuidePage},

    data() {
        return {
            routePath: null,
            searchInter: null,
        }
    },

    created() {
        this.electronEvents()
        this.eeuiEvents()
        this.otherEvents()
    },

    mounted() {
        window.addEventListener('resize', this.windowSizeListener)
        window.addEventListener('scroll', this.windowScrollListener)
        window.addEventListener('message', this.windowHandleMessage)
        this.searchInter = setInterval(this.searchEnter, 1000)
        $A.loadVConsole()
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.windowSizeListener)
        window.removeEventListener('scroll', this.windowScrollListener)
        window.removeEventListener('message', this.windowHandleMessage)
        this.searchInter && clearInterval(this.searchInter)
    },

    computed: {
        ...mapState(['ws', 'themeConf', 'windowOrientation']),
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
                if (this.userId > 0 && this.$isEEUiApp) {
                    $A.eeuiAppSendMessage({
                        action: 'initApp',
                        apiUrl: $A.apiUrl(''),
                        userid: this.userId,
                        token: this.userToken,
                        userAgent: window.navigator.userAgent,
                    });
                    setTimeout(_ => {
                        $A.eeuiAppSendMessage({
                            action: 'setUmengAlias',
                            url: $A.apiUrl('users/umeng/alias')
                        });
                    }, 6000)
                }
            },
            immediate: true
        },

        windowTouch: {
            handler(support) {
                if (support) {
                    $A("body").addClass("window-touch")
                } else {
                    $A("body").removeClass("window-touch")
                }
            },
            immediate: true
        },

        windowOrientation: {
            handler(direction) {
                $A("body").removeClass(["window-landscape", "window-portrait"])
                $A("body").addClass("window-" + direction)
            },
            immediate: true
        },

        windowActive(active) {
            if (active) {
                this.autoTheme()
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
                }, 600)
            } else {
                this.$store.dispatch("audioStop", true)
            }
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

        autoTheme() {
            if (this.themeConf === "auto") {
                this.$store.dispatch("synchTheme")
            }
        },

        windowSizeListener() {
            const windowWidth = $A(window).width(),
                windowHeight = $A(window).height(),
                windowOrientation = $A.screenOrientation()

            this.$store.state.windowTouch = "ontouchend" in document

            this.$store.state.windowWidth = windowWidth
            this.$store.state.windowHeight = windowHeight

            this.$store.state.windowOrientation = windowOrientation
            this.$store.state.windowLandscape = windowOrientation === 'landscape'
            this.$store.state.windowPortrait = windowOrientation === 'portrait'

            this.$store.state.formLabelPosition = windowWidth > 576 ? 'right' : 'top'
            this.$store.state.formLabelWidth = windowWidth > 576 ? 'auto' : ''

            $A.eeuiAppSendMessage({
                action: 'windowSize',
                width: windowWidth,
                height: windowHeight,
            });
        },

        windowScrollListener() {
            this.$store.state.windowScrollY = window.scrollY
        },

        windowHandleMessage({data}) {
            data = $A.jsonParse(data);
            if (data.action === 'eeuiAppSendMessage') {
                const items = $A.isArray(data.data) ? data.data : [data.data];
                items.forEach(item => {
                    $A.eeuiAppSendMessage(item);
                })
            }
        },

        isUseDefaultBrowser(url) {
            if (/web\.zoom\.us/i.test(url)
                || /meeting\.tencent\.com/i.test(url)
                || /meet\.google\.com/i.test(url)) {
                return true;
            }
            if ($A.getDomain(url) == $A.getDomain($A.mainUrl())) {
                try {
                    if (/^\/uploads\//i.test(new URL(url).pathname)) {
                        return true;
                    }
                } catch (e) { }
            }
            return false;
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
            window.__onBeforeOpenWindow = ({url}) => {
                if (this.isUseDefaultBrowser(url)) {
                    return false;   // 使用默认浏览器打开
                }
                this.$store.dispatch("openWebTabWindow", url)
                return true;        // 阻止默认打开
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
            $A.bindScreenshotKey(this.$store.state.cacheKeyboard);
            //
            this.$Electron.sendMessage('setMenuLanguage', {
                openInBrowser: this.$L("在浏览器中打开"),
                saveImageAs: this.$L("图片存储为..."),
                copyImage: this.$L("复制图片"),
                copyEmailAddress: this.$L("复制电子邮件地址"),
                copyLinkAddress: this.$L("复制链接地址"),
                copyImageAddress: this.$L("复制图片地址"),
                failedToSaveImage: this.$L("图片保存失败"),
                theImageFailedToSave: this.$L("图片无法保存"),
            });
        },

        eeuiEvents() {
            if (!this.$isEEUiApp) {
                return;
            }
            // APP进入前台
            window.__onAppActive = () => {
                this.autoTheme()
                $A.IDBTest()
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
                } else {
                    this.autoTheme()
                }
            }
            // 新窗口打开
            window.__onCreateTarget = (url) => {
                if (this.isUseDefaultBrowser(url)) {
                    $A.eeuiAppOpenWeb(url);
                    return;
                }
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url,
                        browser: true,
                        showProgress: true,
                    },
                })
            }
            // 会议事件
            window.__onMeetingEvent = ({act,uuid,meetingid}) => {
                switch (act) {
                    // 获取用户信息
                    case "getInfo":
                        const isTourist = (uuid+'').indexOf('88888') !== -1;
                        this.$store.dispatch("call", {
                            url: isTourist ? 'users/meeting/tourist' : 'users/basic',
                            data: {
                                userid: isTourist ? uuid : (uuid+'').substring(6),
                                tourist_id: uuid,
                            }
                        }).then(({data}) => {
                            $A.eeuiAppSendMessage({
                                action: 'updateMeetingInfo',
                                infos: {
                                    uuid: uuid,
                                    avatar: isTourist ? data?.userimg : data[0]?.userimg,
                                    username: isTourist ? data?.nickname : data[0]?.nickname,
                                }
                            });
                        }).catch(({msg}) => {
                            $A.modalError(msg);
                        });
                        break;
                    //加入成功
                    case "success":
                        this.$store.dispatch("closeMeetingWindow","add")
                        break;
                    // 邀请
                    case "invent":
                        this.$store.dispatch("showMeetingWindow",{
                            type: "invitation",
                            meetingid: meetingid
                        })
                        break;
                    //结束会议
                    case "endMeeting":
                        break;
                    //加入失败
                    case "error":
                        this.$store.dispatch("closeMeetingWindow","error")
                        break;
                    default:
                        break;
                }
            }
            // 键盘状态
            window.__onKeyboardStatus = (data) => {
                const message = $A.jsonParse(decodeURIComponent(data));
                this.$store.state.keyboardType = message.keyboardType;
                this.$store.state.keyboardHeight = message.keyboardHeight;
                this.$store.state.safeAreaBottom = message.safeAreaBottom;
            }
            // 通知权限
            window.__onNotificationPermissionStatus = (ret) => {
                this.$store.state.appNotificationPermission = $A.runNum(ret) == 1;
            }
            // 前往页面
            window.__handleLink = (path) => {
                this.goForward({ path: (path || '').indexOf('/') !==0 ? "/" + path : path });
            }
            // 发送网页尺寸
            $A.eeuiAppSendMessage({
                action: 'windowSize',
                width: this.windowWidth,
                height: this.windowHeight,
            });
            // 取消长按振动
            $A.eeuiAppSetHapticBackEnabled(false)
            // 设置语言
            $A.eeuiAppSetVariate("languageWebBrowser", this.$L("浏览器打开"))
            $A.eeuiAppSetVariate("languageWebRefresh", this.$L("刷新"))
        },

        otherEvents() {
            if (!this.$isSoftware) {
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
