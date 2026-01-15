const windowWidth = $A(window).width(),
    windowHeight = $A(window).height(),
    windowOrientation = $A.screenOrientation(),
    windowIsFullScreen = $A.isFullScreen();

export default {
    // 客户端ID（希望不变的，除非清除浏览器缓存或者卸载应用）
    clientId: "",

    // 缓存版本号（如果想升级后清除客户端缓存则修改此参数值）
    cacheVersion: "v17",

    // 窗口是否激活
    windowActive: true,

    // 窗口滚动条位置
    windowScrollY: 0,

    // 安全区域尺寸（顶部状态栏、底部导航栏）
    safeAreaSize: {top: 0, bottom: 0, data: null},

    // 浏览器可视区域高度
    viewportHeight: 0,

    // 浏览器支持触摸事件
    windowTouch: "ontouchend" in document,

    // 浏览器尺寸信息
    windowWidth: windowWidth,
    windowHeight: windowHeight,

    // 是否第一个页面（eeui）
    isFirstPage: false,

    // 浏览器窗口方向
    windowOrientation: windowOrientation,
    windowLandscape: windowOrientation === 'landscape', // 横屏
    windowPortrait: windowOrientation === 'portrait',   // 竖屏

    // 是否全屏
    windowIsFullScreen: windowIsFullScreen,

    // 表单布局
    formOptions: {
        class: windowWidth > 576 ? '' : 'form-label-weight-bold',
        labelPosition: windowWidth > 576 ? 'right' : 'top',
        labelWidth: windowWidth > 576 ? 'auto' : '',
    },

    // 键盘状态
    keyboardShow: false,    // 键盘可见
    keyboardHeight: 0,      // 键盘高度

    // 是否按下Ctrl/Command键
    isModKey: false,

    // App通知权限
    appNotificationPermission: true,

    // 播放中的音频地址
    audioPlaying: null,

    // 路由记录
    routeName: null,
    routePath: null,
    routeHistorys: [],
    routeHistoryLast: {},
    routeLoading: 0,

    // 显示移动端Tabbar
    mobileTabbar: false,

    // 请求时间
    callAt: [],

    // 加载状态
    loads: [],
    loadDashboardTasks: false,
    loadUserBasic: false,
    loadProjects: 0,
    loadDialogs: 0,
    loadDialogAuto: false,
    loadDialogLatestId: 0,
    floatSpinnerTimer: [],
    floatSpinnerLoad: 0,

    // 滑动返回
    touchBackInProgress: false,

    // User
    cacheUserWait: [],
    cacheUserBasic: [],
    cacheUserExtra: {},

    // 日历
    cacheCalendarView: null,

    // Dialog
    cacheDialogs: [],

    // Project
    cacheProjects: [],
    cacheColumns: [],
    cacheTasks: [],
    cacheProjectParameter: [],

    // Emoji
    cacheEmojis: [],

    // ServerUrl
    cacheServerUrl: "",

    // keyboard
    cacheKeyboard: {},

    // Ajax
    ajaxAuthException: null,
    ajaxNetworkException: null,

    // Websocket
    ws: null,
    wsCall: {},
    wsTimeout: null,
    wsRandom: 0,
    wsOpenNum: 0,

    // 会员信息
    userInfo: {},
    userId: 0,
    userToken: '',
    userIsAdmin: false,
    userAvatar: {},

    // 会话聊天
    dialogId: 0,
    dialogMsgId: 0,
    dialogMsgKeep: 30,
    dialogSearchMsgId: 0,
    dialogIns: [],
    dialogMsgs: [],
    dialogTodos: [],
    dialogMsgTops: [],
    dialogHistory: [],
    dialogDrafts: [],
    dialogQuotes: [],
    dialogMsgTransfer: {time: 0},
    dialogSseList: [],
    dialogDroupWordChain: {},
    dialogGroupVote: {},
    dialogModalShow: false,
    dialogCommonCountCache: {},

    // 搜索关键词（主要用于移动端判断滑动返回）
    messengerSearchKey: {dialog: '', contacts: ''},

    // 阅读消息
    readLoadNum: 0,
    readTimeout: null,
    readWaitData: {},

    // 文件
    fileLists: [],
    fileLinks: [],
    filePackLists: [],
    fileShakeId: 0,

    // 项目任务
    projectId: 0,
    projectTotal: 0,
    projectLoad: 0,
    taskId: 0,
    taskCompleteTemps: [],
    taskContents: [],
    taskFiles: [],
    taskLogs: [],
    taskOperation: {},
    taskRelatedCache: {},
    taskArchiveView: 0,
    taskTemplates: [],
    taskLatestId: 0,

    // 任务等待状态
    taskOneLoad: {},

    // 任务流程信息
    taskFlows: [],
    taskFlowItems: [],

    // 任务优先级
    taskPriority: [],

    // 项目创建列表模板
    columnTemplate: [],

    // 列表背景色
    columnColorList: [
        {name: '默认', color: ''},
        {name: '蓝色', color: '#51abea'},
        {name: '绿色', color: '#73b45c'},
        {name: '黄色', color: '#f2d86d'},
        {name: '橙色', color: '#faaa6c'},
        {name: '红色', color: '#ff7070'},
        {name: '紫色', color: '#b583e3'},
        {name: '粉色', color: '#ff819c'},
        {name: '青色', color: '#3ad1c8'},
        {name: '棕色', color: '#947364'},
        {name: '灰色', color: '#999999'},
        {name: '深蓝', color: '#2b5caa'},
        {name: '深绿', color: '#1a7f5a'},
        {name: '金色', color: '#ffd700'},
        {name: '湖蓝', color: '#00bcd4'},
    ],

    // 任务背景色
    taskColorList: [
        {name: '默认', color: '', primary: ''},
        {name: '黄色', color: '#fffae6', primary: '#f2d86d'},
        {name: '蓝色', color: '#e5f5ff', primary: '#51abea'},
        {name: '绿色', color: '#ecffe5', primary: '#73b45c'},
        {name: '粉色', color: '#ffeaee', primary: '#ff819c'},
        {name: '紫色', color: '#f6ecff', primary: '#b583e3'},
        {name: '灰色', color: '#f3f3f3', primary: '#999999'},
    ],

    // 主题皮肤
    themeConf: window.localStorage.getItem("__system:themeConf__"), // auto|light|dark
    themeName: null, // 自动生成
    themeList: [
        {name: '跟随系统', value: 'auto'},
        {name: '明亮', value: 'light'},
        {name: '暗黑', value: 'dark'},
    ],

    // 客户端新版本号
    clientNewVersion: null,

    // 客户端下载地址（网页版）
    clientDownloadUrl: '',

    // 预览图片
    previewImageIndex: 0,
    previewImageList: [],

    // 工作报告未读数量
    reportUnreadNumber: 0,

    // 加密相关
    apiKeyData: {},
    localKeyPair: {},
    localKeyLock: false,

    // 系统设置
    systemConfig: {},

    // 审批待办未读数量
    approveUnreadNumber: 0,

    // 会议
    meetingWindow: {
        show: false,
        type: "",
        meetingid: 0
    },
    appMeetingShow: false,

    // 翻译
    cacheTranslationLanguage: '',
    cacheTranslations: [],

    // 下拉菜单操作
    menuOperation: {},

    // 长按数据
    longpressData: {type: '', data: null, element: null},

    // 微应用数据
    microApps: [],
    microAppsInstalled: [],
    microAppsIds: [],
    microAppsMenus: [],

    // MCP 服务器状态
    mcpServerStatus: {running: 'stopped'},
};
