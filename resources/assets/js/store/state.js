const method = {
    apiUrl(str) {
        if (str.substring(0, 2) === "//" ||
            str.substring(0, 7) === "http://" ||
            str.substring(0, 8) === "https://" ||
            str.substring(0, 6) === "ftp://" ||
            str.substring(0, 1) === "/") {
            return str;
        }
        return window.location.origin + '/api/' + str;
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
                dateObj = new Date(v);
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
            keyName = '__state:Cache__';
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

// 变量缓存
[
    'projectTablePanel',    // 项目面板显示类型
    'taskMyShow',           // 项目面板显示我的任务
    'taskUndoneShow',       // 项目面板显示未完成任务
    'taskCompletedShow'     // 项目面板显示已完成任务
].forEach((key) => {
    state[key] = state.method.getStorageBoolean("boolean:" + key, true)
});

[
    'projectChatShow',      // 项目聊天显示
    'projectCompleteShow'   // 项目面板显示已完成列表
].forEach((key) => {
    state[key] = state.method.getStorageBoolean("boolean:" + key, false)
});

// ajax
state.ajaxLoadNum = 0;
state.ajaxWsReady = false;
state.ajaxWsListener = [];

// 数据缓存
state.cacheUserBasic = state.method.getStorageJson("cacheUserBasic");
state.cacheDialogMsg = state.method.getStorageJson("cacheDialogMsg");
state.cacheProjectList = state.method.getStorageArray("cacheProjectList");

// 会员信息
state.userInfo = state.method.getStorageJson("userInfo");
state.userId = state.userInfo.userid = state.method.runNum(state.userInfo.userid);
state.userToken = state.userInfo.token;
state.userIsAdmin = state.method.inArray("admin", state.userInfo.identity);
state.userOnline = {};

// Websocket
state.ws = null;
state.wsMsg = {};
state.wsCall = {};
state.wsTimeout = null;
state.wsListener = {};
state.wsReadTimeout = null;
state.wsReadWaitList = [];

// 项目任务
state.projectLoad = 0;
state.projectList = state.cacheProjectList;
state.projectDetail = {id: 0, project_column: [], project_user: []};
state.projectOpenTask = {_show: false, id: 0, task_user: [], task_tag: []};
state.projectTaskContent = {};
state.projectTaskFiles = {};
state.projectSubTask = {};
state.calendarTask = [];

// 会话聊天
state.dialogId = 0;
state.dialogList = [];
state.dialogDetail = {};
state.dialogMsgUnread = 0;
state.dialogMsgLoad = 0;
state.dialogMsgList = [];
state.dialogMsgCurrentPage = 1;
state.dialogMsgHasMorePages = false;

// 任务优先级
state.taskPriority = [];

export default state
