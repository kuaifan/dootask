const stateCommon = {
    setStorage(key, value) {
        return this._storage(key, value);
    },

    getStorage(key, def = null) {
        let value = this._storage(key);
        return value || def;
    },

    getStorageString(key, def = '') {
        let value = this._storage(key);
        return typeof value === "string" || typeof value === "number" ? value : def;
    },

    getStorageNumber(key, def = 0) {
        let value = this._storage(key);
        return typeof value === "number" ? value : def;
    },

    getStorageBoolean(key, def = false) {
        let value = this._storage(key);
        return typeof value === "boolean" ? value : def;
    },

    getStorageArray(key, def = {}) {
        let value = this._storage(key);
        return this._isArray(value) ? value : def;
    },

    getStorageJson(key, def = {}) {
        let value = this._storage(key);
        return this._isJson(value) ? value : def;
    },

    _isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    },

    _isJson(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
    },

    _storage(key, value) {
        let keyName = 'state';
        if (typeof value === 'undefined') {
            return this._loadFromlLocal('__::', key, '', '__' + keyName + '__');
        } else {
            this._savaToLocal('__::', key, value, '__' + keyName + '__');
        }
    },

    _savaToLocal(id, key, value, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                seller = {};
                seller[id] = {};
            } else {
                seller = JSON.parse(seller);
                if (!seller[id]) {
                    seller[id] = {};
                }
            }
            seller[id][key] = value;
            window.localStorage[keyName] = JSON.stringify(seller);
        } catch (e) {
        }
    },

    _loadFromlLocal(id, key, def, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                return def;
            }
            seller = JSON.parse(seller)[id];
            if (!seller || typeof seller[key] === 'undefined') {
                return def;
            }
            return seller[key];
        } catch (e) {
            return def;
        }
    },

    _count(obj) {
        try {
            if (typeof obj === "undefined") {
                return 0;
            }
            if (typeof obj === "number") {
                obj += "";
            }
            if (typeof obj.length === 'number') {
                return obj.length;
            } else {
                let i = 0, key;
                for (key in obj) {
                    i++;
                }
                return i;
            }
        } catch (e) {
            return 0;
        }
    },

    _runNum(str, fixed) {
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

    _cloneJSON(myObj) {
        if(typeof(myObj) !== 'object') return myObj;
        if(myObj === null) return myObj;
        return this._jsonParse(this._jsonStringify(myObj))
    },

    _jsonParse(str, defaultVal) {
        if (str === null) {
            return defaultVal ? defaultVal : {};
        }
        if (typeof str === "object") {
            return str;
        }
        try {
            return JSON.parse(str.replace(/\n/g,"\\n").replace(/\r/g,"\\r"));
        } catch (e) {
            return defaultVal ? defaultVal : {};
        }
    },

    _jsonStringify(json, defaultVal) {
        if (typeof json !== 'object') {
            return json;
        }
        try{
            return JSON.stringify(json);
        }catch (e) {
            return defaultVal ? defaultVal : "";
        }
    }
};

const projectChatShow = stateCommon.getStorageBoolean('projectChatShow', true);
const projectListPanel = stateCommon.getStorageBoolean('projectListPanel', true);

const userInfo = stateCommon.getStorageJson('userInfo');
const userId = userInfo.userid = stateCommon._runNum(userInfo.userid);
const userToken = userInfo.token;

export default Object.assign(stateCommon, {
    projectChatShow,
    projectListPanel,

    userId,
    userInfo,
    userToken,

    projectLoad: 0,
    projectDetail: {
        id: 0,
        project_column: [],
        project_user: []
    },

    cacheProject: {},
    cacheUserBasic: {},
})
