const stateData = {
    // 是否桌面端
    isDesktop: $A.isDesktop(),

    // 浏览器宽度
    windowWidth: window.innerWidth,

    // 浏览器宽度≤768返回true
    windowMax768: window.innerWidth <= 768,

    // 数据缓存
    cacheLoading: {},

    // DrawerOverlay
    cacheDrawerIndex: 0,
    cacheDrawerOverlay: [],

    // User
    cacheUserActive: {},
    cacheUserWait: [],
    cacheUserBasic: $A.getStorageArray("cacheUserBasic"),

    // Dialog
    cacheDialogs: $A.getStorageArray("cacheDialogs"),
    cacheUnreads: {},

    // Project
    cacheProjects: $A.getStorageArray("cacheProjects"),
    cacheColumns: $A.getStorageArray("cacheColumns"),
    cacheTasks: $A.getStorageArray("cacheTasks"),
    cacheProjectParameter: $A.getStorageArray("cacheProjectParameter"),
    cacheTaskBrowse: $A.getStorageArray("cacheTaskBrowse"),

    // ServerUrl
    cacheServerUrl: $A.getStorageString("cacheServerUrl"),

    // Ajax
    ajaxWsReady: false,
    ajaxWsListener: [],

    // Websocket
    ws: null,
    wsMsg: {},
    wsCall: {},
    wsTimeout: null,
    wsRandom: 0,
    wsOpenNum: 0,
    wsListener: {},
    wsReadTimeout: null,
    wsReadWaitList: [],

    // 会员信息
    userInfo: $A.getStorageJson("userInfo"),
    userId: 0,
    userToken: '',
    userIsAdmin: false,
    userOnline: {},

    // 会话聊天
    dialogMsgs: [],
    dialogOpenId: 0,

    // 文件
    files: [],
    fileContent: {},

    // 项目任务
    projectId: 0,
    projectTotal: 0,
    projectLoad: 0,
    taskId: 0,
    taskCompleteTemps: [],
    taskContents: [],
    taskFiles: [],
    taskLogs: [],

    // 任务等待状态
    taskLoading: [],

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
        {name: '灰色', color: '#444444'},
        {name: '棕色', color: '#947364'},
        {name: '橘色', color: '#faaa6c'},
        {name: '黄色', color: '#f2d86d'},
        {name: '绿色', color: '#73b45c'},
        {name: '蓝色', color: '#51abea'},
        {name: '紫色', color: '#b583e3'},
        {name: '粉色', color: '#ff819c'},
        {name: '红色', color: '#ff7070'},
    ],

    // 任务背景色
    taskColorList: [
        {name: '默认', color: ''},
        {name: '黄色', color: '#fffae6'},
        {name: '蓝色', color: '#e5f5ff'},
        {name: '绿色', color: '#ecffe5'},
        {name: '粉色', color: '#ffeaee'},
        {name: '紫色', color: '#f6ecff'},
        {name: '灰色', color: '#f3f3f3'},
    ],

    // 主题皮肤
    themeMode: $A.getStorageString("cacheThemeMode"),
    themeList: [
        {name: '跟随系统', value: 'auto'},
        {name: '明亮', value: 'light'},
        {name: '暗黑', value: 'dark'},
    ],
    themeIsDark: false,

    // 客户端新版本号
    clientNewVersion: null,

    // 预览图片
    previewImageIndex: 0,
    previewImageList: [],
};

// 会员信息
if (stateData.userInfo.userid) {
    stateData.userId = stateData.userInfo.userid = $A.runNum(stateData.userInfo.userid);
    stateData.userToken = stateData.userInfo.token;
    stateData.userIsAdmin = $A.inArray("admin", stateData.userInfo.identity);
}

// ServerUrl
if (stateData.cacheServerUrl) {
    window.systemInfo.apiUrl = stateData.cacheServerUrl;
}

// 主题皮肤
switch (stateData.themeMode) {
    case 'dark':
        $A.dark.enableDarkMode()
        break;
    case 'light':
        $A.dark.disableDarkMode()
        break;
    default:
        stateData.themeMode = "auto"
        $A.dark.autoDarkMode()
        break;
}
stateData.themeIsDark = $A.dark.isDarkEnabled();

export default stateData
