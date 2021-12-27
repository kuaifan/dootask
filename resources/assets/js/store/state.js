const method = {
    apiUrl(str) {
        if (str.substring(0, 2) === "//" ||
            str.substring(0, 7) === "http://" ||
            str.substring(0, 8) === "https://" ||
            str.substring(0, 6) === "ftp://" ||
            str.substring(0, 1) === "/") {
            return str;
        }
        if (window.systemInformation && typeof window.systemInformation.apiUrl === "string") {
            str = window.systemInformation.apiUrl + str;
        } else {
            str = window.location.origin + "/api/" + str;
        }
        while (str.indexOf("/../") !== -1) {
            str = str.replace(/\/(((?!\/).)*)\/\.\.\//, "/")
        }
        return str
    },

    Time(v) {
        let time
        if (typeof v === "string" && this.strExists(v, "-")) {
            v = v.replace(/-/g, '/');
            time = new Date(v).getTime();
        } else {
            time = new Date().getTime();
        }
        return Math.round(time / 1000)
    },

    Date(v) {
        if (typeof v === "string" && this.strExists(v, "-")) {
            v = v.replace(/-/g, '/');
        }
        return new Date(v);
    },

    zeroFill(str, length, after) {
        str+= "";
        if (str.length >= length) {
            return str;
        }
        let _str = '', _ret = '';
        for (let i = 0; i < length; i++) {
            _str += '0';
        }
        if (after || typeof after === 'undefined') {
            _ret = (_str + "" + str).substr(length * -1);
        } else {
            _ret = (str + "" + _str).substr(0, length);
        }
        return _ret;
    },

    formatDate(format, v) {
        if (typeof format === 'undefined' || format === '') {
            format = 'Y-m-d H:i:s';
        }
        let dateObj;
        if (v instanceof Date) {
            dateObj = v;
        } else {
            if (typeof v === 'undefined') {
                dateObj = new Date();
            } else if (/^(-)?\d{1,10}$/.test(v)) {
                dateObj = new Date(v * 1000);
            } else {
                dateObj = this.Date(v);
            }
        }
        //
        format = format.replace(/Y/g, dateObj.getFullYear() + "");
        format = format.replace(/m/g, this.zeroFill(dateObj.getMonth() + 1, 2));
        format = format.replace(/d/g, this.zeroFill(dateObj.getDate(), 2));
        format = format.replace(/H/g, this.zeroFill(dateObj.getHours(), 2));
        format = format.replace(/i/g, this.zeroFill(dateObj.getMinutes(), 2));
        format = format.replace(/s/g, this.zeroFill(dateObj.getSeconds(), 2));
        return format;
    },

    date2string(params, format) {
        if (params === null) {
            return params;
        }
        if (typeof format === "undefined") {
            format = "Y-m-d H:i:s";
        }
        if (params instanceof Date) {
            params = this.formatDate(format, params);
        } else if (this.isJson(params)) {
            for (let key in params) {
                if (!params.hasOwnProperty(key)) continue;
                params[key] = this.date2string(params[key], format);
            }
        } else if (this.isArray(params)) {
            params.forEach((val, index) => {
                params[index] = this.date2string(val, format);
            });
        }
        return params;
    },

    setStorage(key, value) {
        return this.storage(key, value);
    },

    getStorage(key, def = null) {
        let value = this.storage(key);
        return value || def;
    },

    getStorageString(key, def = '') {
        let value = this.storage(key);
        return typeof value === "string" || typeof value === "number" ? value : def;
    },

    getStorageInt(key, def = 0) {
        let value = this.storage(key);
        return typeof value === "number" ? value : def;
    },

    getStorageBoolean(key, def = false) {
        let value = this.storage(key);
        return typeof value === "boolean" ? value : def;
    },

    getStorageArray(key, def = []) {
        let value = this.storage(key);
        return this.isArray(value) ? value : def;
    },

    getStorageJson(key, def = {}) {
        let value = this.storage(key);
        return this.isJson(value) ? value : def;
    },

    isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    },

    isJson(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
    },

    inArray(key, array) {
        if (!this.isArray(array)) {
            return false;
        }
        return array.includes(key);
    },

    storage(key, value) {
        if (!key) {
            return;
        }
        let keyName = '__state__';
        if (key.substring(0, 5) === 'cache') {
            keyName = '__state:' + key + '__';
        }
        if (typeof value === 'undefined') {
            return this.loadFromlLocal(key, '', keyName);
        } else {
            this.savaToLocal(key, value, keyName);
        }
    },

    savaToLocal(key, value, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                seller = {};
            } else {
                seller = JSON.parse(seller);
            }
            seller[key] = value;
            window.localStorage[keyName] = JSON.stringify(seller);
        } catch (e) {
        }
    },

    loadFromlLocal(key, def, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                return def;
            }
            seller = JSON.parse(seller);
            if (!seller || typeof seller[key] === 'undefined') {
                return def;
            }
            return seller[key];
        } catch (e) {
            return def;
        }
    },

    runNum(str, fixed) {
        let _s = Number(str);
        if (_s + "" === "NaN") {
            _s = 0;
        }
        if (/^[0-9]*[1-9][0-9]*$/.test(fixed)) {
            _s = _s.toFixed(fixed);
            let rs = _s.indexOf('.');
            if (rs < 0) {
                _s += ".";
                for (let i = 0; i < fixed; i++) {
                    _s += "0";
                }
            }
        }
        return _s;
    },

    cloneJSON(myObj) {
        if (typeof (myObj) !== 'object') return myObj;
        if (myObj === null) return myObj;
        return this.jsonParse(this.jsonStringify(myObj))
    },

    jsonParse(str, defaultVal) {
        if (str === null) {
            return defaultVal ? defaultVal : {};
        }
        if (typeof str === "object") {
            return str;
        }
        try {
            return JSON.parse(str.replace(/\n/g, "\\n").replace(/\r/g, "\\r"));
        } catch (e) {
            return defaultVal ? defaultVal : {};
        }
    },

    jsonStringify(json, defaultVal) {
        if (typeof json !== 'object') {
            return json;
        }
        try {
            return JSON.stringify(json);
        } catch (e) {
            return defaultVal ? defaultVal : "";
        }
    },
};

// 方法类
const state = { method };

// 浏览器宽度≤768返回true
state.windowMax768 = window.innerWidth <= 768;

// 数据缓存
state.cacheLoading = {};
state.cacheDrawerOverlay = [];
// User
state.cacheUserActive = {};
state.cacheUserWait = [];
state.cacheUserBasic = state.method.getStorageArray("cacheUserBasic");
// Dialog
state.cacheDialogs = state.method.getStorageArray("cacheDialogs");
// Project
state.cacheProjects = state.method.getStorageArray("cacheProjects");
state.cacheColumns = state.method.getStorageArray("cacheColumns");
state.cacheTasks = state.method.getStorageArray("cacheTasks");
// TablePanel
state.cacheTablePanel = state.method.getStorageArray("cacheTablePanel");
// ServerUrl
state.cacheServerUrl = state.method.getStorageString("cacheServerUrl")
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
state.userInfo = state.method.getStorageJson("userInfo");
state.userId = state.userInfo.userid = state.method.runNum(state.userInfo.userid);
state.userToken = state.userInfo.token;
state.userIsAdmin = state.method.inArray("admin", state.userInfo.identity);
state.userOnline = {};

// 会话聊天
state.dialogs = [];
state.dialogMsgs = [];
state.dialogMsgPush = {};
state.dialogOpenId = 0;

// 文件
state.files = [];
state.fileContent = {};

// 项目任务
state.projectId = 0;
state.projects = [];
state.projectTotal = 0;
state.projectLoad = 0;
state.columns = [];
state.taskId = 0;
state.tasks = [];
state.taskSubs = [];
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
