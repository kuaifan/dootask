<template>
    <div id="app">
        <keep-alive>
            <router-view class="child-view"></router-view>
        </keep-alive>

        <!--任务操作-->
        <TaskOperation/>

        <!--会议管理-->
        <MeetingManager/>

        <!--下拉菜单-->
        <DropdownMenu/>

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
import MeetingManager from "./pages/manage/components/MeetingManager";
import DropdownMenu from "./components/DropdownMenu";
import {ctrlPressed} from "./mixins/ctrlPressed";
import {mapState} from "vuex";

export default {
    mixins: [ctrlPressed],

    components: {
        MeetingManager,
        DropdownMenu,
        TaskOperation,
        NetworkException,
        PreviewImageState,
        RightBottom,
        FloatSpinner,
        GuidePage
    },

    data() {
        return {
            routePath: null,
            appInter: null,
            countDown: Math.min(30, 60 - $A.daytz().second()),
            lastCheckUpgradeYmd: $A.daytz().format('YYYY-MM-DD')
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
        this.appInter = setInterval(this.appTimerHandler, 1000)
        $A.loadVConsole()
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.windowSizeListener)
        window.removeEventListener('scroll', this.windowScrollListener)
        window.removeEventListener('message', this.windowHandleMessage)
        this.appInter && clearInterval(this.appInter)
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
                $A.updateTimezone()
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
        appTimerHandler() {
            this.searchEnter()
            //
            this.countDown--
            if (this.countDown <= 0) {
                this.countDown = Math.min(30, 60 - $A.daytz().second())
                this.$store.dispatch("todayAndOverdue")
            }
        },
        searchEnter() {
            let row = $A(".search-container");
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

            this.$store.state.formOptions = {
                class: windowWidth > 576 ? '' : 'form-label-weight-bold',
                labelPosition: windowWidth > 576 ? 'right' : 'top',
                labelWidth: windowWidth > 576 ? 'auto' : '',
            }

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
            // 按下Ctrl|Command键打开
            if (this.isCtrlCommandPressed) {
                return true;
            }
            // 常见会议链接
            if (this.isMeetingUrlStrict(url)) {
                return true;
            }
            // 同域名规则
            if ($A.getDomain(url) == $A.getDomain($A.mainUrl())) {
                try {
                    const {pathname, searchParams} = new URL(url);
                    // uploads/                    上传文件
                    // api/dialog/msg/download     会话文件
                    // api/project/task/filedown   任务文件
                    if (/^\/(uploads|api\/dialog\/msg\/download|api\/project\/task\/filedown)/.test(pathname)) {
                        return true;
                    }
                    // api/file/content?down=yes   文件下载
                    if (/^\/api\/file\/content/.test(pathname) && searchParams.get('down') === 'yes') {
                        return true;
                    }
                } catch (e) { }
            }
            return false;
        },

        isMeetingUrlStrict(url) {
            const meetingDomains = [
                // 国际主流
                'web.zoom.us',
                'meeting.tencent.com',
                'meet.google.com',
                'teams.microsoft.com',
                'join.skype.com',
                'bluejeans.com',
                'webex.com',
                'voovmeeting.com',

                // 中国区
                'meeting.feishu.cn',
                'meeting.dingtalk.com',
                'jitsi.baidu.com',

                // 其他国际
                'whereby.com',
                'meet.jit.si',
                'gotomeeting.com',
                '8x8.vc',
                'lifesize.com',
                'starleaf.com',

                // 教育和企业
                'classroomscreen.com',
                'bigbluebutton.org'
            ];
            const lowerUrl = `${url}`.toLowerCase()
            return meetingDomains.some(domain => lowerUrl.indexOf(domain) !== -1);
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
                $A.updateTimezone()
                $A.IDBTest()
                if (this.lastCheckUpgradeYmd != $A.daytz().format('YYYY-MM-DD')) {
                    this.lastCheckUpgradeYmd = $A.daytz().format('YYYY-MM-DD')
                    $A.eeuiAppCheckUpdate();
                }
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
            window.__onMeetingEvent = (event) => {
                if (!$A.isJson(event)) {
                    return;
                }
                switch (event.act) {
                    // 获取用户信息
                    case "getInfo":
                        const isTourist = (event.uuid + '').indexOf('88888') !== -1;
                        this.$store.dispatch("call", {
                            url: isTourist ? 'users/meeting/tourist' : 'users/basic',
                            data: {
                                userid: isTourist ? event.uuid : (event.uuid + '').substring(6),
                                tourist_id: event.uuid,
                            }
                        }).then(({data}) => {
                            $A.eeuiAppSendMessage({
                                action: 'updateMeetingInfo',
                                infos: {
                                    uuid: event.uuid,
                                    avatar: isTourist ? data?.userimg : data[0]?.userimg,
                                    username: isTourist ? data?.nickname : data[0]?.nickname,
                                }
                            });
                        }).catch(({msg}) => {
                            $A.modalError(msg);
                        });
                        break;
                    // 加入成功
                    case "success":
                        this.$store.dispatch("closeMeetingWindow", "add")
                        break;
                    // 邀请
                    case "invent":
                        this.$store.dispatch("showMeetingWindow", {
                            type: "invitation",
                            meetingid: event.meetingid
                        })
                        break;
                    // 结束会议
                    case "endMeeting":
                        break;
                    // 加入失败
                    case "error":
                        this.$store.dispatch("closeMeetingWindow", "error")
                        break;
                    // 状态
                    case "status":
                        this.$store.state.appMeetingShow = event.status
                        break;
                    default:
                        break;
                }
            }
            // 键盘状态
            window.__onKeyboardStatus = (event) => {
                if (!$A.isJson(event)) {
                    // 兼容旧版本
                    event = $A.jsonParse(decodeURIComponent(event));
                }
                if (!$A.isJson(event)) {
                    return;
                }
                this.$store.state.keyboardType = event.keyboardType;
                this.$store.state.keyboardHeight = event.keyboardHeight;
                this.$store.state.safeAreaBottom = event.safeAreaBottom;
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
            $A.eeuiAppSetCachesString("languageWebBrowser", this.$L("浏览器打开"))
            $A.eeuiAppSetCachesString("languageWebRefresh", this.$L("刷新"))
            $A.eeuiAppSetCachesString("updateDefaultTitle", this.$L("发现新版本"))
            $A.eeuiAppSetCachesString("updateDefaultContent", this.$L("暂无更新介绍！"))
            $A.eeuiAppSetCachesString("updateDefaultCancelText", this.$L("以后再说"))
            $A.eeuiAppSetCachesString("updateDefaultUpdateText", this.$L("立即更新"))
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
