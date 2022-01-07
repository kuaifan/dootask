const state = {};

// 浏览器宽度
state.windowWidth = window.innerWidth;

// 浏览器宽度≤768返回true
state.windowMax768 = window.innerWidth <= 768;

// 数据缓存
state.cacheLoading = {};
state.cacheDrawerOverlay = [];

// User
state.cacheUserActive = {};
state.cacheUserWait = [];
state.cacheUserBasic = $A.getStorageArray("cacheUserBasic");

// Dialog
state.cacheDialogs = $A.getStorageArray("cacheDialogs");

// Project
state.cacheProjects = $A.getStorageArray("cacheProjects");
state.cacheColumns = $A.getStorageArray("cacheColumns");
state.cacheTasks = $A.getStorageArray("cacheTasks");
state.cacheProjectParameter = $A.getStorageArray("cacheProjectParameter");

// ServerUrl
state.cacheServerUrl = $A.getStorageString("cacheServerUrl")
if (state.cacheServerUrl && window.systemInformation) {
    window.systemInformation.apiUrl = state.cacheServerUrl;
}

// Ajax
state.ajaxWsReady = false;
state.ajaxWsListener = [];

// Websocket
state.ws = null;
state.wsMsg = {};
state.wsCall = {};
state.wsTimeout = null;
state.wsListener = {};
state.wsReadTimeout = null;
state.wsReadWaitList = [];

// 会员信息
state.userInfo = $A.getStorageJson("userInfo");
state.userId = state.userInfo.userid = $A.runNum(state.userInfo.userid);
state.userToken = state.userInfo.token;
state.userIsAdmin = $A.inArray("admin", state.userInfo.identity);
state.userOnline = {};

// 会话聊天
state.dialogMsgs = [];
state.dialogMsgPush = {};
state.dialogOpenId = 0;

// 文件
state.files = [];
state.fileContent = {};

// 项目任务
state.projectId = 0;
state.projectTotal = 0;
state.projectLoad = 0;
state.taskId = 0;
state.taskContents = [];
state.taskFiles = [];
state.taskLogs = [];

// 任务优先级
state.taskPriority = [];

// 列表背景色
state.columnColorList = [
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
];

// 任务背景色
state.taskColorList = [
    {name: '默认', color: ''},
    {name: '黄色', color: '#fffae6'},
    {name: '蓝色', color: '#e5f5ff'},
    {name: '绿色', color: '#ecffe5'},
    {name: '粉色', color: '#ffeaee'},
    {name: '紫色', color: '#f6ecff'},
    {name: '灰色', color: '#f3f3f3'},
];

export default state
