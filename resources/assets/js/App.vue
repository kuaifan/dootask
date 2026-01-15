<template>
    <div id="app" class="app-view">
        <!--顶部状态栏-->
        <div class="child-status-bar"></div>

        <!--主路由视图-->
        <keep-alive>
            <router-view class="child-view" @hook:mounted.once="onRouterViewMounted"/>
        </keep-alive>

        <!--底部导航栏-->
        <div class="child-navigation-bar"></div>

        <!--任务操作-->
        <TaskOperation/>

        <!--会议管理-->
        <MeetingManager/>

        <!--项目邀请-->
        <ProjectInvite/>

        <!--下拉菜单-->
        <DropdownMenu/>

        <!--全局浮窗加载器-->
        <FloatSpinner/>

        <!--右下角客户端-->
        <RightBottom/>

        <!--图片预览-->
        <PreviewImageState/>

        <!--身份提示-->
        <AuthException/>

        <!--引导页-->
        <GuidePage/>

        <!--会员详情窗口-->
        <UserDetail/>

        <!--AI 助理-->
        <AIAssistant/>

        <!--返回效果-->
        <MobileBack v-if="isFirstPage"/>

        <!--移动端通知-->
        <MobileNotification v-if="isFirstPage"/>

        <!--网络提示-->
        <NetworkException v-if="windowLandscape"/>
    </div>
</template>


<style lang="scss" scoped>
.app-view {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;

    .child-status-bar {
        flex-shrink: 0;
        height: var(--status-bar-height);
        background-color: var(--status-bar-color);
    }

    .child-navigation-bar {
        flex-shrink: 0;
        height: var(--navigation-bar-height);
        background-color: var(--navigation-bar-color);
    }

    .child-view {
        flex: 1;
        height: 0;
        will-change: transform;
        transition: all .3s cubic-bezier(.55, 0, .1, 1);
    }
}
</style>
<script>
import FloatSpinner from "./components/FloatSpinner";
import RightBottom from "./components/RightBottom";
import PreviewImageState from "./components/PreviewImage/state";
import AuthException from "./components/AuthException";
import NetworkException from "./components/NetworkException";
import GuidePage from "./components/GuidePage";
import TaskOperation from "./pages/manage/components/TaskOperation";
import MeetingManager from "./pages/manage/components/MeetingManager";
import ProjectInvite from "./pages/manage/components/ProjectInvite";
import MobileNotification from "./components/Mobile/Notification.vue";
import MobileBack from "./components/Mobile/Back.vue";
import DropdownMenu from "./components/DropdownMenu";
import {ctrlPressed} from "./mixins/ctrlPressed";
import {mapState} from "vuex";
import emitter from "./store/events";
import AIAssistant from "./components/AIAssistant";
import UserDetail from "./pages/manage/components/UserDetail.vue";
import {languageName} from "./language";

export default {
    mixins: [ctrlPressed],

    components: {
        AIAssistant,
        UserDetail,
        MobileBack,
        MobileNotification,
        AuthException,
        MeetingManager,
        ProjectInvite,
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
            appInter: null,
            appActivated: true,

            countDown: Math.min(30, 60 - $A.daytz().second()),
            lastCheckUpgradeYmd: $A.daytz().format('YYYY-MM-DD'),
        }
    },

    created() {
        this.electronEvents()
        this.eeuiEvents()
        this.otherEvents()
    },

    mounted() {
        this.appInter = setInterval(this.appTimerHandler, 1000)
        $A.loadVConsole()
    },

    beforeDestroy() {
        this.appInter && clearInterval(this.appInter)
    },

    computed: {
        ...mapState(['ws', 'themeConf', 'windowOrientation', 'safeAreaSize', 'isFirstPage', 'mobileTabbar', 'themeName']),

        statusColor({routeName, windowLandscape}) {
            if (!routeName) {
                return null
            }
            if (windowLandscape) {
                return "#f4f5f7"
            }
            if (['login', 'manage-messenger', 'manage-project'].includes(routeName) || routeName.startsWith('manage-setting')) {
                return '#f8f8f8'
            }
            return null
        },

        navigationColor({routeName, windowLandscape, mobileTabbar}) {
            if (!routeName) {
                return null
            }
            if (windowLandscape) {
                return "#f4f5f7"
            }
            if (['login', 'manage-messenger', 'manage-project', 'manage-setting'].includes(routeName)) {
                return '#f8f8f8'
            }
            return mobileTabbar ? '#f8f8f8' : null
        },

        rootStyle() {
            return {
                '--window-width': `${this.windowWidth}px`,
                '--window-height': `${this.windowHeight}px`,
                '--window-scroll-y': `${this.windowScrollY}px`,
                '--status-bar-height': `${this.safeAreaSize.top}px`,
                '--status-bar-color': this.statusColor || '#ffffff',
                '--navigation-bar-height': `${this.safeAreaSize.bottom}px`,
                '--navigation-bar-color': this.navigationColor || '#ffffff',
            }
        },
    },

    watch: {
        '$route': {
            handler({name, path, params}) {
                this.$store.state.routeName = name
                this.$store.state.routePath = path
                this.$store.state.mobileTabbar = (name === 'manage-project' && !/^\d+$/.test(params.projectId)) || ['manage-dashboard', 'manage-messenger', 'manage-application'].includes(name);
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
                if (this.$isEEUIApp) {
                    this.umengAliasTimer && clearTimeout(this.umengAliasTimer)
                    if (this.userId > 0) {
                        // 给 APP 发送初始化消息
                        $A.eeuiAppSendMessage({
                            action: 'initApp',
                            apiUrl: $A.apiUrl(''),
                            userid: this.userId,
                            token: this.userToken,
                            language: languageName,
                            userAgent: window.navigator.userAgent,
                        });

                        // 更新设备信息
                        $A.eeuiAppGetDeviceInfo().then(async info => {
                            let deviceName = info.deviceName || info.modelName
                            if (info.systemName === 'Android') {
                                if ($A.strExists(info.modelName, info.brand)) {
                                    deviceName = info.modelName
                                } else {
                                    deviceName = info.brand + ' ' + info.model
                                }
                            }
                            await this.$store.dispatch("call", {
                                url: "users/device/edit",
                                data: {
                                    device_name: deviceName,
                                    app_brand: info.brand,
                                    app_model: info.model,
                                    app_os: info.systemName + ' ' + info.systemVersion,
                                }
                            })
                        }).catch(console.log)

                        // 设置友盟别名
                        this.umengAliasTimer = setTimeout(_ => {
                            this.umengAliasTimer = null;
                            $A.eeuiAppSendMessage({
                                action: 'setUmengAlias',
                                url: $A.apiUrl('users/umeng/alias')
                            });
                        }, 6000)
                    } else {
                        // 删除友盟别名
                        $A.eeuiAppSendMessage({
                            action: 'delUmengAlias',
                            url: $A.apiUrl('users/umeng/alias')
                        });
                    }
                }
            },
            immediate: true
        },

        rootStyle: {
            handler(style) {
                for (const key in style) {
                    document.documentElement.style.setProperty(key, style[key])
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

        isCtrlCommandPressed: {
            handler(status) {
                this.$store.state.isModKey = status
            },
            immediate: true
        },

        windowActive(active) {
            if (!active) {
                this.$store.dispatch("audioStop", true)
                return
            }

            this.autoTheme()
            $A.updateTimezone()

            this.__windowTimer && clearTimeout(this.__windowTimer)
            this.__timeoutTimer && clearTimeout(this.__timeoutTimer)
            this.__windowTimer = setTimeout(async () => {
                try {
                    await this.$store.dispatch("call", {url: "users/socket/status"})
                    await new Promise((resolve, reject) => {
                        this.$store.dispatch("websocketSend", {
                            type: 'handshake',
                            callback: (_, ok) => {
                                ok ? resolve() : reject(new Error('Handshake failed'));
                            }
                        });
                        this.__timeoutTimer = setTimeout(() => reject(new Error('Handshake timeout')), 6000);
                    });
                } catch {
                    await this.$store.dispatch("websocketConnection")
                }
            }, 600)
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
            buttons.each((_, item) => {
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

        onRouterViewMounted() {
            document.documentElement.setAttribute("data-platform", $A.isElectron ? "desktop" : $A.isEEUIApp ? "app" : "web")
        },

        /**
         * 获取链接打开方式
         * @param url
         * @returns {number}    // 0: 默认 1: 浏览器打开 2: 阻止打开
         */
        getUrlMethodType(url) {
            // 按下Ctrl|Command键打开
            if (this.isCtrlCommandPressed) {
                return 1;
            }
            // 应用协议链接
            if (this.isApplicationProtocol(url)) {
                return 1;
            }
            // 常见会议链接
            if (this.isMeetingUrlStrict(url)) {
                return 1;
            }
            // 同域名下载链接
            if (this.isDownloadUrl(url)) {
                return 1;
            }
            // 同域名规则
            if ($A.getDomain(url) == $A.mainDomain()) {
                try {
                    const {pathname, searchParams} = new URL(url);
                    // meeting/1234567890/xxxxx    会议
                    if (/^\/meeting\/\d+\/\S+$/.test(pathname)) {
                        const meetingId = pathname.split('/')[2];
                        emitter.emit('addMeeting', {
                            type: 'join',
                            meetingid: meetingId,
                            meetingdisabled: true,
                        });
                        return 2;
                    }
                    // manage/project/invite/xxxx 项目邀请
                    // manage/project/invite?code=xxxx 项目邀请
                    if (/^\/manage\/project\/invite/.test(pathname)) {
                        const paths = pathname.split('/')
                        const code = paths.length > 4 ? paths[4] : searchParams.get('code')
                        emitter.emit('openProjectInvite', {code})
                        return 2;
                    }
                } catch (e) { }
            }
            return 0;
        },

        isDownloadUrl(url) {
            if ($A.getDomain(url) == $A.mainDomain()) {
                try {
                    const {pathname, searchParams} = new URL(url);
                    // 匹配常见的下载相关路径
                    const downloadPathPatterns = [
                        '/uploads',                                   // 上传文件
                        '/api/dialog/msg/download',                  // 会话文件
                        '/api/project/task/filedown',                // 任务文件
                        '/api/file/download/pack',                   // 文件打包下载
                        '/api/approve/down',                         // 审批导出下载
                        '/api/project/task/down',                    // 任务导出下载
                        '/api/system/checkin/down'                   // 签到导出下载
                    ];
                    if (downloadPathPatterns.some(pattern => $A.leftExists(pathname, pattern))) {
                        return true;
                    }
                    // 匹配文件内容下载（/api/file/content 带参数 down=yes）
                    if ($A.leftExists(pathname, '/api/file/content') && searchParams.get('down') === 'yes') {
                        return true;
                    }
                } catch (e) {}
            }
            return false;
        },

        isApplicationProtocol(url) {
            const protocols = [
                'thunder:',             // 迅雷专有链接
                'ed2k:',                // 电驴链接
                'magnet:',              // 磁力链接
                'weixin:',              // 微信
                'alipays:',             // 支付宝
                'mqq:',                 // QQ
                'dingtalk:',            // 钉钉
                'baidumap:',            // 百度地图
                'iosamap:',             // 高德地图iOS
                'androidamap:',         // 高德地图Android
                'tel:',                 // 电话
                'sms:',                 // 短信
                'mailto:',              // 邮件
                'market:',              // 应用市场
                'intent:',              // Android Intent
                'taobao:',              // 淘宝
                'tmall:',               // 天猫
                'jd:',                  // 京东
                'pinduoduo:',           // 拼多多
                'vnd.youtube:',         // YouTube应用
                'zhihu:',               // 知乎
                'bilibili:',            // B站
                'snssdk1128:',          // 抖音
                'kwai:',                // 快手
                'fb:',                  // Facebook
                'twitter:',             // Twitter
                'instagram:',           // Instagram
                'linkedin:'             // LinkedIn
            ];
            const lowerUrl = `${url}`.toLowerCase()
            return protocols.some(protocol => lowerUrl.startsWith(protocol));
        },

        isMeetingUrlStrict(url) {
            const meetingDomains = [
                // 视频会议（内置浏览器无法正常使用摄像头/麦克风）
                'zoom.us',              // Zoom 所有子域名
                'meeting.tencent.com',  // 腾讯会议
                'teams.microsoft.com',  // Teams 会议
                'meet.google.com',      // Google Meet
                'meeting.feishu.cn',    // 飞书会议
                'meeting.dingtalk.com', // 钉钉会议
                'webex.com',            // Webex
                'bluejeans.com',        // BlueJeans
                'goto.com',             // GoToMeeting
                'gotomeeting.com',      // GoToMeeting 旧域名
                '8x8.vc',              // 8x8
                'meet.jit.si',          // Jitsi
                'jitsi.baidu.com',      // 百度 Jitsi
                'whereby.com',          // Whereby
                'lifesize.com',         // Lifesize
                'starleaf.com',         // StarLeaf
                'classroomscreen.com',  // ClassroomScreen
                'bigbluebutton.org',    // BigBlueButton
                'matrix.to',            // Matrix
                'meetings.vonage.com',  // Vonage Video
                'voovmeeting.com',      // 腾讯会议国际版
                'skype.com',            // Skype

                // 需要调用系统API的场景
                'maps.google.com',      // Google地图
                'maps.apple.com',       // 苹果地图
                'amap.com',             // 高德地图
                'map.baidu.com',        // 百度地图
                'map.qq.com',           // 腾讯地图
                'mapurl.cn',            // 百度地图短链接

                // 支付场景（需要调用系统支付组件）
                'alipay.com',           // 支付宝
                'pay.weixin.qq.com',    // 微信支付
                'paypal.com/cgi-bin',   // PayPal支付流程
                'checkout.stripe.com',   // Stripe支付流程
                'pay.google.com',       // Google Pay
                'pay.qq.com',           // QQ钱包
                'pay.baidu.com',        // 百度支付

                // 应用商店和应用分发（需要系统处理）
                'apps.apple.com',       // iOS App Store
                'play.google.com',      // Google Play
                'itunes.apple.com',     // iTunes
                'apps.samsung.com',     // Samsung Store
                'microsoft.com/store',   // Microsoft Store
                'amazon.com/apps',      // Amazon Appstore
                'apk.qq.com',           // 应用宝
                'app.mi.com',           // 小米应用商店
                'app.hicloud.com',      // 华为应用市场

                // 文件处理（需要系统能力）
                'pan.baidu.com',        // 百度网盘
                'aliyundrive.com',      // 阿里云盘
                'drive.google.com',     // Google Drive
                'onedrive.live.com',    // OneDrive
                'xunlei.com',           // 迅雷

                // 即时通讯（需要系统通知和持久连接）
                'wx.qq.com',            // 微信网页版
                'im.qq.com',            // QQ
                'web.whatsapp.com',     // WhatsApp Web
                'web.telegram.org',     // Telegram Web
                'discord.com/channels', // Discord语音频道
                'messenger.com/call',   // Facebook Messenger通话
                'workspace.dingtalk.com', // 钉钉工作台

                // 媒体流（需要特殊权限或编解码）
                'douyin.com/live',      // 抖音直播
                'live.kuaishou.com',    // 快手直播
                'live.bilibili.com',    // B站直播
                'douyu.com/room',       // 斗鱼直播间
                'yy.com/x/',            // YY直播
                'inke.cn/live',         // 映客直播
                'facebook.com/live',    // Facebook直播
                'instagram.com/live',   // Instagram直播
                'youtube.com/live',     // YouTube直播
                'twitch.tv/live',       // Twitch直播
            ];
            const lowerUrl = `${url}`.toLowerCase()
            return meetingDomains.some(domain => lowerUrl.indexOf(domain) !== -1);
        },

        electronEvents() {
            if (!this.$Electron) {
                return;
            }
            window.__onBeforeUnload = () => {
                this.$store.dispatch("onBeforeUnload");
                if (this.$Modal.removeLast()) {
                    return true;
                }
            }
            this.$Electron.sendMessage('registerCloseInterceptor')
            window.__onBeforeOpenWindow = ({url}) => {
                const urlType = this.getUrlMethodType(url)
                if (urlType === 2) {
                    // 阻止打开
                    return true;
                } else if (urlType === 1) {
                    // 使用默认浏览器打开
                    if (this.isDownloadUrl(url)) {
                        this.$store.dispatch('downUrl', url)
                        return true;
                    }
                    return false;
                }
                // 使用内置浏览器打开
                this.$store.dispatch('openWindow', url)
                return true
            }
            this.$Electron.listener('browserWindowBlur', _ => {
                this.$store.state.windowActive = false;
            })
            this.$Electron.listener('browserWindowFocus', _ => {
                this.$store.state.windowActive = true;
            })
            this.$Electron.listener('systemThemeChanged', _ => {
                this.autoTheme()
            })
            this.$Electron.listener('openDownloadWindow', _ => {
                $A.Electron.request({
                    action: 'openDownloadWindow',
                    language: languageName,
                    theme: this.themeName,
                });
            })
            $A.bindScreenshotKey(this.$store.state.cacheKeyboard);
            //
            this.$Electron.sendMessage('setMenuLanguage', {
                copy: this.$L("复制"),
                back: this.$L("后退"),
                forward: this.$L("前进"),
                reload: this.$L("重新加载"),
                print: this.$L("打印"),
                openInBrowser: this.$L("在浏览器中打开"),
                openInDefaultBrowser: this.$L("默认浏览器打开"),
                saveImageAs: this.$L("图片存储为..."),
                copyImage: this.$L("复制图片"),
                copyEmailAddress: this.$L("复制电子邮件地址"),
                copyLinkAddress: this.$L("复制链接地址"),
                copyImageAddress: this.$L("复制图片地址"),
                moveToNewWindow: this.$L("将标签页移至新窗口"),
                failedToSaveImage: this.$L("图片保存失败"),
                theImageFailedToSave: this.$L("图片无法保存"),
            });
        },

        eeuiEvents() {
            if (!this.$isEEUIApp) {
                return;
            }
            // 隐藏快照
            setTimeout(() => {
                this.appActivated && $A.eeuiAppHideWebviewSnapshot()
            }, 500)
            // APP进入前台
            window.__onAppActive = async () => {
                this.appActivated = true

                // IndexedDB 测试失败时会自动刷新页面,这里提前返回防止后续代码执行
                if (!await $A.IDBTest()) {
                    return;
                }

                this.autoTheme()
                $A.updateTimezone()
                $A.eeuiAppHideWebviewSnapshot()
                this.$store.dispatch("safeAreaInsets")
                const nowYmd = $A.daytz().format('YYYY-MM-DD')
                if (this.lastCheckUpgradeYmd != nowYmd) {
                    this.lastCheckUpgradeYmd = nowYmd
                    $A.eeuiAppCheckUpdate();
                }
            }
            // APP进入后台
            window.__onAppDeactive = () => {
                this.appActivated = false
                setTimeout(() => {
                    if (this.appActivated) {
                        // 如果APP处于激活状态，则不显示快照
                        return;
                    }
                    $A.eeuiAppGetWebviewSnapshot(ok => {
                        if (!ok || this.appActivated) {
                            // 如果获取快照失败，或者APP处于激活状态，则不显示快照
                            return;
                        }
                        $A.eeuiAppShowWebviewSnapshot()
                    });
                }, 500);
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
                const urlType = this.getUrlMethodType(url)
                if (urlType === 2) {
                    // 阻止打开
                    return;
                } else if (urlType === 1) {
                    // 使用默认浏览器打开
                    $A.eeuiAppOpenWeb(url);
                    return;
                }
                // App 内置浏览器打开
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {url},
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
                this.$store.state.keyboardShow = event.keyboardType === 'show';
                this.$store.state.keyboardHeight = event.keyboardHeight;
                $A.eeuiAppShakeToEditEnabled(this.$store.state.keyboardShow)
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
            $A.eeuiAppSetCachesString("languageWebBack", this.$L("后退"))
            $A.eeuiAppSetCachesString("languageWebForward", this.$L("前进"))
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
