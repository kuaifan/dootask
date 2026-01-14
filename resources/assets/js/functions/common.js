const localforage = require("localforage");
const dayjs = require("dayjs");
const utc = require("dayjs/plugin/utc")
const timezone = require("dayjs/plugin/timezone");

/**
 * 基础函数
 */
(function (window, $, undefined) {
    window.systemInfo = window.systemInfo || {};
    window.modalTransferIndex = 1000;
    localforage.config({name: 'DooTask', storeName: 'common'});

    /**
     * =============================================================================
     * *******************************   基础函数类   *******************************
     * =============================================================================
     */
    $.extend({
        /**
         * 是否数组
         * @param obj
         * @returns {boolean}
         */
        isArray(obj) {
            return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
        },

        /**
         * 是否数组对象
         * @param obj
         * @returns {boolean}
         */
        isJson(obj) {
            return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
        },

        /**
         * 是否在数组里
         * @param key
         * @param array
         * @param regular
         * @returns {boolean|*}
         */
        inArray(key, array, regular = false) {
            if (!this.isArray(array)) {
                return false;
            }
            if (regular) {
                return !!array.find(item => {
                    if (item && item.indexOf("*")) {
                        const rege = new RegExp("^" + item.replace(/[-\/\\^$+?.()|[\]{}]/g, '\\$&').replace(/\*/g, '.*') + "$", "g")
                        if (rege.test(key)) {
                            return true
                        }
                    }
                    return item == key
                });
            } else {
                return array.includes(key);
            }
        },

        /**
         * 随机获取范围
         * @param Min
         * @param Max
         * @returns {*}
         */
        randNum(Min,Max){
            let Range = Max - Min;
            let Rand = Math.random();
            return Min + Math.round(Rand * Range); //四舍五入
        },

        /**
         * 获取数组最后一个值
         * @param array
         * @returns {boolean}
         */
        last(array) {
            let str = false;
            if (typeof array === 'object' && array.length > 0) {
                str = array[array.length - 1];
            }
            return str;
        },

        /**
         * 字符串是否包含
         * @param string
         * @param find
         * @param lower
         * @returns {boolean}
         */
        strExists(string, find, lower = false) {
            string += "";
            find += "";
            if (lower !== true) {
                string = string.toLowerCase();
                find = find.toLowerCase();
            }
            return (string.indexOf(find) !== -1);
        },

        /**
         * 字符串是否左边包含
         * @param string
         * @param find
         * @param lower
         * @returns {boolean}
         */
        leftExists(string, find, lower = false) {
            string += "";
            find += "";
            if (lower !== true) {
                string = string.toLowerCase();
                find = find.toLowerCase();
            }
            return (string.substring(0, find.length) === find);
        },

        /**
         * 删除左边字符串
         * @param string
         * @param find
         * @param lower
         * @returns {string}
         */
        leftDelete(string, find, lower = false) {
            string += "";
            find += "";
            if (this.leftExists(string, find, lower)) {
                string = string.substring(find.length)
            }
            return string ? string : '';
        },

        /**
         * 字符串是否右边包含
         * @param string
         * @param find
         * @param lower
         * @returns {boolean}
         */
        rightExists(string, find, lower = false) {
            string += "";
            find += "";
            if (lower !== true) {
                string = string.toLowerCase();
                find = find.toLowerCase();
            }
            return (string.substring(string.length - find.length) === find);
        },

        /**
         * 删除右边字符串
         * @param string
         * @param find
         * @param lower
         * @returns {string}
         */
        rightDelete(string, find, lower = false) {
            string += "";
            find += "";
            if (this.rightExists(string, find, lower)) {
                string = string.substring(0, string.length - find.length)
            }
            return string ? string : '';
        },

        /**
         * 取字符串中间
         * @param string
         * @param start
         * @param end
         * @returns {*}
         */
        getMiddle(string, start = null, end = null) {
            string = string.toString();
            if (this.isHave(start) && this.strExists(string, start)) {
                string = string.substring(string.indexOf(start) + start.length);
            }
            if (this.isHave(end) && this.strExists(string, end)) {
                string = string.substring(0, string.indexOf(end));
            }
            return string;
        },

        /**
         * 截取字符串
         * @param string
         * @param start
         * @param end
         * @returns {string}
         */
        subString(string, start, end) {
            string += "";
            if (!this.isHave(end)) {
                end = string.length;
            }
            return string.substring(start, end);
        },

        /**
         * 随机字符
         * @param len
         * @returns {string}
         */
        randomString(len) {
            len = len || 32;
            let $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
            let maxPos = $chars.length;
            let pwd = '';
            for (let i = 0; i < len; i++) {
                pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
            }
            return pwd;
        },

        /**
         * 判断是否有
         * @param val
         * @param {boolean} enhanced
         * @returns {boolean}
         */
        isHave(val, enhanced = false) {
            // 基础检查
            if (val === null || val === "null" || val === undefined || val === "undefined" || !val) {
                return false;
            }
            // 增强检查
            if (enhanced) {
                if (Array.isArray(val)) return val.length > 0;
                if (typeof val === 'object' && val.constructor === Object) return Object.keys(val).length > 0;
            }
            return true;
        },

        /**
         * 判断是否为真
         * @param value
         * @returns {boolean}
         */
        isTrue(value) {
            const type = typeof value;
            if (type === 'boolean') return value === true;
            if (type === 'number') return value === 1;
            if (type === 'string') return value.toLowerCase() === 'true' || value === '1';
            return false;
        },

        /**
         * 相当于 intval
         * @param str
         * @param fixed
         * @returns {number}
         */
        runNum(str, fixed = null) {
            let _s = Number(str);
            if (_s + "" === "NaN") {
                _s = 0;
            }
            if (fixed && /^[0-9]*[1-9][0-9]*$/.test(fixed)) {
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

        /**
         * 补零
         * @param str
         * @param length
         * @param after
         * @returns {*}
         */
        zeroFill(str, length, after = false) {
            if (after) {
                return `${str}`.padEnd(length, '0')
            }
            return `${str}`.padStart(length, '0')
        },

        /**
         * 检测手机号码格式
         * @param str
         * @returns {boolean}
         */
        isMobile(str) {
            return /^1([3456789])\d{9}$/.test(str);
        },

        /**
         * 检测邮箱地址格式
         * @param email
         * @returns {boolean}
         */
        isEmail(email) {
            return /^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*\.)+[a-zA-Z]*)$/i.test(email);
        },

        /**
         * 根据两点间的经纬度计算距离
         * @param lng1
         * @param lat1
         * @param lng2
         * @param lat2
         * @returns {string|*}
         */
        getDistance(lng1, lat1, lng2, lat2) {
            let DEF_PI = 3.14159265359;         // PI
            let DEF_2PI = 6.28318530712;        // 2*PI
            let DEF_PI180 = 0.01745329252;      // PI/180.0
            let DEF_R = 6370693.5;              // radius of earth
            //
            let ew1, ns1, ew2, ns2;
            let dx, dy, dew;
            let distance;
            // 角度转换为弧度
            ew1 = lng1 * DEF_PI180;
            ns1 = lat1 * DEF_PI180;
            ew2 = lng2 * DEF_PI180;
            ns2 = lat2 * DEF_PI180;
            // 经度差
            dew = ew1 - ew2;
            // 若跨东经和西经180 度，进行调整
            if (dew > DEF_PI)
                dew = DEF_2PI - dew;
            else if (dew < -DEF_PI)
                dew = DEF_2PI + dew;
            dx = DEF_R * Math.cos(ns1) * dew; // 东西方向长度(在纬度圈上的投影长度)
            dy = DEF_R * (ns1 - ns2); // 南北方向长度(在经度圈上的投影长度)
            // 勾股定理求斜边长
            distance = Math.sqrt(dx * dx + dy * dy).toFixed(0);
            return distance;
        },

        /**
         * 设置网页标题
         * @param title
         */
        setTile(title) {
            document.title = title;
            let mobile = navigator.userAgent.toLowerCase();
            if (/iphone|ipad|ipod/.test(mobile)) {
                let iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.setAttribute('src', '/favicon.ico');
                let iframeCallback = function () {
                    setTimeout(function () {
                        iframe.removeEventListener('load', iframeCallback);
                        document.body.removeChild(iframe)
                    }, 0)
                };
                iframe.addEventListener('load', iframeCallback);
                document.body.appendChild(iframe)
            }
        },

        /**
         * 克隆对象
         * @param value
         * @param useParse
         * @returns {*}
         */
        cloneJSON(value, useParse = false) {
            if (useParse === true) {
                return $A.jsonParse($A.jsonStringify(value))
            }
            try {
                return structuredClone(value);
            } catch (e) {
                if (typeof value !== 'object' || value === null) return value;
                return $A.jsonParse($A.jsonStringify(value))
            }
        },

        /**
         * 将一个 JSON 字符串转换为对象（已try）
         * @param str
         * @param defaultVal
         * @returns {*}
         */
        jsonParse(str, defaultVal = undefined) {
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

        /**
         * 将 JavaScript 值转换为 JSON 字符串（已try）
         * @param json
         * @param defaultVal
         * @returns {string}
         */
        jsonStringify(json, defaultVal = undefined) {
            if (typeof json !== 'object') {
                return json;
            }
            try{
                return JSON.stringify(json);
            }catch (e) {
                return defaultVal ? defaultVal : "";
            }
        },

        /**
         * 监听对象尺寸发生改变
         * @param obj
         * @param callback
         */
        resize(obj, callback) {
            let myObj = $A(obj);
            if (myObj.length === 0) return;
            let height = parseInt(myObj.outerHeight()),
                width = parseInt(myObj.outerWidth());
            let inter = setInterval(()=>{
                if (myObj.length === 0) clearInterval(inter);
                let tmpHeight = parseInt(myObj.outerHeight()),
                    tmpWidth = parseInt(myObj.outerWidth());
                if (height !== tmpHeight || width !== tmpWidth) {
                    height = tmpHeight;
                    width = tmpWidth;
                    $A.openLog && console.log(width, height);
                    if (typeof callback === 'function') callback();
                }
            }, 250);
        },

        /**
         * 获取屏幕方向
         * @returns {string}
         */
        screenOrientation() {
            /*try {
                if (typeof window.screen.orientation === "object") {
                    return $A.strExists(window.screen.orientation.type, 'portrait') ? 'portrait' : 'landscape'
                }
            } catch (e) {
                //
            }*/ // 注释原因：有些设备宽和高对调了
            return $A(window).width() - $A(window).height() > 50 ? "landscape" : "portrait"
        },

        /**
         * 是否IOS
         * @returns {boolean|string}
         */
        isIos() {
            let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
            return ua && /iphone|ipad|ipod|ios/.test(ua);
        },

        /**
         * 是否iPad
         * @returns {boolean|string}
         */
        isIpad() {
            let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
            return ua && /ipad/.test(ua);
        },

        /**
         * 是否安卓
         * @returns {boolean|string}
         */
        isAndroid() {
            let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
            return ua && ua.indexOf('android') > 0;
        },

        /**
         * 是否微信
         * @returns {boolean}
         */
        isWeixin() {
            let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
            return (ua.match(/MicroMessenger/i) + '' === 'micromessenger');
        },

        /**
         * 是否Chrome
         * @returns {boolean}
         */
        isChrome() {
            let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
            return (ua.match(/Chrome/i) + '' === 'chrome');
        },

        /**
         * 是否桌面端
         * @returns {boolean}
         */
        isDesktop(){
            let ua = typeof window !== 'undefined' && window.navigator.userAgent;
            return !ua.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i);
        },

        /**
         * 获取对象
         * @param obj
         * @param keys
         * @param defaultValue
         * @returns {string|*}
         */
        getObject(obj, keys, defaultValue = "") {
            let keyArray;
            if (typeof keys === 'string') {
                keyArray = keys.replace(/,/g, "|").replace(/\./g, "|").split("|");
            } else if (Array.isArray(keys)) {
                keyArray = keys;
            } else {
                return defaultValue;
            }
            let result = obj;
            for (let i = 0; i < keyArray.length; i++) {
                let key = keyArray[i];
                if (result == null) {
                    return defaultValue;
                }
                if (typeof key === 'string' && /^\d+$/.test(key)) {
                    key = parseInt(key, 10);
                }
                result = result[key];
            }
            return result === undefined ? defaultValue : result;
        },

        /**
         * 统计数组或对象长度
         * @param obj
         * @returns {number}
         */
        count(obj) {
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

        /**
         * 获取文本长度
         * @param string
         * @returns {number}
         */
        stringLength(string) {
            if (typeof string === "number" || typeof string === "string") {
                return (string + "").length
            }
            return 0;
        },

        /**
         * 获取数组长度（处理数组不存在）
         * @param array
         * @returns {number|*}
         */
        arrayLength(array) {
            if (array) {
                try {
                    return array.length;
                } catch (e) {
                    return 0
                }
            }
            return 0;
        },

        /**
         * 将数组或对象内容部分拼成字符串
         * @param obj
         * @returns {string}
         */
        objImplode(obj) {
            if (obj === null) {
                return "";
            }
            let str = "";
            $A.each(obj, (key, val) => {
                if (val !== null) {
                    if (typeof val === "object" && this.count(val) > 0) {
                        str += this.objImplode(val);
                    } else {
                        str += String(val);
                    }
                }
            });
            return str.replace(/\s/g, "").replace(/undefined/g, "");
        },

        /**
         * 指定键获取url参数
         * @param key
         * @returns {*}
         */
        urlParameter(key) {
            const params = this.urlParameterAll();
            return typeof key === "undefined" ? params : params[key];
        },

        /**
         * 获取所有url参数
         * @returns {{}}
         */
        urlParameterAll() {
            const search = window.location.search || window.location.hash || "";
            const index = search.indexOf("?");
            const arr = index !== -1 ? search.substring(index + 1).split("&") : [];
            const params = {};
            for (let i = 0; i < arr.length; i++) {
                const data = arr[i].split("=");
                if (data.length === 2) {
                    params[data[0]] = data[1];
                }
            }
            return params;
        },

        /**
         * 删除地址中的参数
         * @param url
         * @param keys
         * @returns {string|*}
         */
        removeURLParameter(url, keys) {
            if (keys instanceof Array) {
                keys.forEach((key) => {
                    url = $A.removeURLParameter(url, key)
                });
                return url;
            }
            try {
                // 使用URL API正确解析URL各部分
                const urlObj = new URL(url);
                urlObj.searchParams.delete(keys);
                return urlObj.toString();
            } catch (e) {
                // 如果URL解析失败，回退到简单的字符串处理方法
                const urlparts = url.split('?');
                if (urlparts.length >= 2) {
                    const prefix = encodeURIComponent(keys) + '=';
                    const pars = urlparts[1].split(/[&;]/g);
                    for (let i = pars.length; i-- > 0;) {
                        if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                            pars.splice(i, 1);
                        }
                    }
                    return urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
                }
                return url;
            }
        },

        /**
         * 连接加上参数
         * @param url
         * @param params
         * @returns {*}
         */
        urlAddParams(url, params) {
            if (!$A.isJson(params)) {
                return url;
            }
            try {
                // 使用URL API正确解析URL各部分
                const urlObj = new URL(url);
                for (let key in params) {
                    if (!params.hasOwnProperty(key)) {
                        continue;
                    }
                    urlObj.searchParams.set(key, params[key]);
                }
                return urlObj.toString();
            } catch (e) {
                // 如果URL解析失败，回退到简单的字符串拼接方法
                if (url) {
                    url = this.removeURLParameter(url, Object.keys(params))
                }
                url += "";
                url += url.indexOf("?") === -1 ? '?' : '';
                for (let key in params) {
                    if (!params.hasOwnProperty(key)) {
                        continue;
                    }
                    url += '&' + key + '=' + encodeURIComponent(params[key]);
                }
                return this.rightDelete(url.replace("?&", "?"), '?');
            }
        },

        /**
         * 替换url中的hash
         * @param {string} url - 要修改的URL；如果只提供一个参数，则作为新的hash路径，URL默认为当前页面
         * @param {string} [path] - 新的hash路径，可包含或不包含#前缀
         * @returns {string} 替换hash后的URL
         */
        urlReplaceHash(url, path = undefined) {
            // 如果只传了一个参数，将其视为path，url默认为当前页面
            if (path === undefined) {
                path = url;
                url = window.location.href;
            }

            // 确保url有值
            url = url || window.location.href;

            try {
                // 使用URL API正确解析URL各部分
                const urlObj = new URL(url);

                // 确保path是字符串并格式正确
                path = String(path || '');
                if (path && path.startsWith('#')) {
                    path = path.substring(1);
                }

                // 设置新的hash
                urlObj.hash = path;

                return urlObj.toString();
            } catch (e) {
                // 如果URL解析失败，回退到简单的字符串替换方法
                if (!path) {
                    return url.replace(/#.*$/, '');
                }

                const hashPath = path.startsWith('#') ? path : '#' + path;
                if (url.includes('#')) {
                    return url.replace(/#.*$/, hashPath);
                } else {
                    return url + hashPath;
                }
            }
        },

        /**
         * 刷新当前地址
         * @returns {string}
         */
        reloadUrl() {
            if ($A.isEEUIApp && $A.isAndroid()) {
                let url = window.location.href;
                let key = '_='
                let reg = new RegExp(key + '\\d+');
                let timestamp = $A.dayjs().valueOf();
                if (url.indexOf(key) > -1) {
                    url = url.replace(reg, key + timestamp);
                } else {
                    if (url.indexOf('\?') > -1) {
                        let urlArr = url.split('\?');
                        if (urlArr[1]) {
                            url = urlArr[0] + '?' + key + timestamp + '&' + urlArr[1];
                        } else {
                            url = urlArr[0] + '?' + key + timestamp;
                        }
                    } else {
                        if (url.indexOf('#') > -1) {
                            url = url.split('#')[0] + '?' + key + timestamp + location.hash;
                        } else {
                            url = url + '?' + key + timestamp;
                        }
                    }
                }
                $A.eeuiAppSetUrl(url);
            } else {
                window.location.reload();
            }
        },

        /**
         * 链接字符串
         * @param value 第一个参数为连接符
         * @returns {string}
         */
        stringConnect(...value) {
            let s = null;
            let text = "";
            value.forEach((val) => {
                if (s === null) {
                    s = val;
                }else if (val){
                    if (val && text) text+= s;
                    text+= val;
                }
            });
            return text;
        },

        /**
         * 判断两个对象是否相等
         * @param x
         * @param y
         * @returns {boolean}
         */
        objEquals(x, y) {
            let f1 = x instanceof Object;
            let f2 = y instanceof Object;
            if (!f1 || !f2) {
                return x === y
            }
            if (Object.keys(x).length !== Object.keys(y).length) {
                return false
            }
            for (let p in x) {
                if (x.hasOwnProperty(p)) {
                    let a = x[p] instanceof Object;
                    let b = y[p] instanceof Object;
                    if (a && b) {
                        if (!this.objEquals(x[p], y[p])) {
                            return false;
                        }
                    } else if (x[p] != y[p]) {
                        return false;
                    }
                }
            }
            return true;
        },

        /**
         * 输入框内插入文本
         * @param object
         * @param content
         */
        insert2Input(object, content) {
            if (object === null || typeof object !== "object") return;
            if (typeof object.length === 'number' && object.length > 0) object = object[0];

            let ele = typeof object.$el === "object" ? $A(object.$el) : $A(object);
            if (ele.length === 0) return;
            let eleDom = ele[0];

            if (eleDom.tagName != "INPUT" && eleDom.tagName != "TEXTAREA") {
                if (ele.find("input").length === 0) {
                    ele = ele.find("textarea");
                }else{
                    ele = ele.find("input");
                }
            }
            if (ele.length === 0) return;
            eleDom = ele[0];

            if (eleDom.tagName != "INPUT" && eleDom.tagName != "TEXTAREA") return;

            let text = ele.val();
            let { selectionStart, selectionEnd } = eleDom;

            ele.val(`${text.substring(0, selectionStart)}${content}${text.substring(selectionEnd, text.length)}`);
            eleDom.dispatchEvent(new Event('input'));

            setTimeout(() => {
                if (eleDom.setSelectionRange) {
                    let pos = text.substring(0, selectionStart).length + content.length;
                    eleDom.focus();
                    eleDom.setSelectionRange(pos, pos);
                }
            }, 10);
        },

        /**
         * 输入框数字限制
         * @param object
         * @param min
         * @param max
         * @returns
         */
        inputNumberLimit(object, min = null, max = null) {
            if (object === null || typeof object !== "object") return;
            if (object && typeof object.target === "object") {
                object = object.target;
            }
            let eleDom = null;
            if (object && typeof object.$el === "object") {
                eleDom = object.$el;
            } else if (typeof object.length === "number" && object.length > 0) {
                eleDom = object[0];
            } else if (object && (object.nodeType === 1 || object.tagName)) {
                eleDom = object;
            }
            if (!eleDom) return;

            let ele = $A(eleDom);
            if (ele.length === 0) return;

            if (eleDom.tagName != "INPUT" && eleDom.tagName != "TEXTAREA") {
                if (ele.find("input").length === 0) {
                    ele = ele.find("textarea");
                }else{
                    ele = ele.find("input");
                }
            }
            if (ele.length === 0) return;
            eleDom = ele[0];

            if (eleDom.tagName != "INPUT" && eleDom.tagName != "TEXTAREA") return;

            let val = parseFloat(ele.val());
            if (!isNaN(val)) {
                if (min !== null && val < min) {
                    val = min;
                }
                if (max !== null && val > max) {
                    val = max;
                }
                ele.val(val);
                eleDom.dispatchEvent(new Event('input'));
            }
        },

        /**
         * iOS上虚拟键盘引起的触控错位
         */
        iOSKeyboardFixer() {
            if (!this.isIos()) {
                return;
            }
            document.body.scrollTop = document.body.scrollTop + 1;
            document.body.scrollTop = document.body.scrollTop - 1;
        },

        /**
         * 动态加载js文件
         * @param url
         * @returns {Promise<unknown>}
         */
        loadScript(url) {
            return new Promise(async (resolve, reject) => {
                url = $A.originUrl(url)
                if (this.rightExists(url, '.css')) {
                    return resolve(this.loadCss(url))
                }
                //
                let i = 0
                while (this.__loadScript[url] === "loading") {
                    await new Promise(r => setTimeout(r, 1000))
                    i++
                    if (i > 30) {
                        return reject("加载超时")
                    }
                }
                if (this.__loadScript[url] === "loaded") {
                    return resolve(false)
                }
                this.__loadScript[url] = "loading"
                //
                const script = document.createElement("script")
                script.type = "text/javascript"
                if (script.readyState) {
                    script.onreadystatechange = () => {
                        if (script.readyState === "loaded" || script.readyState === "complete") {
                            script.onreadystatechange = null
                            this.__loadScript[url] = "loaded"
                            resolve(true)
                        }
                    }
                } else {
                    script.onload = () => {
                        this.__loadScript[url] = "loaded"
                        resolve(true)
                    }
                    script.onerror = (e) => {
                        this.__loadScript[url] = "error"
                        reject(e)
                    }
                }
                if (this.rightExists(url, '.js')) {
                    script.src = url + "?hash=" + window.systemInfo.version
                } else {
                    script.src = url
                }
                if (document.head) {
                    document.head.appendChild(script)
                } else {
                    document.body.appendChild(script)
                }
            })
        },
        loadScriptS(urls) {
            return new Promise(resolve => {
                let i = 0
                const recursiveCallback = () => {
                    if (++i < urls.length) {
                        this.loadScript(urls[i]).finally(recursiveCallback)
                    } else {
                        resolve()
                    }
                }
                this.loadScript(urls[0]).finally(recursiveCallback)
            })
        },
        __loadScript: {},

        /**
         * 动态加载css文件
         * @param url
         * @returns {Promise<unknown>}
         */
        loadCss(url) {
            return new Promise(async (resolve, reject) => {
                url = $A.originUrl(url)
                if (this.rightExists(url, '.js')) {
                    return resolve(this.loadScript(url))
                }
                //
                let i = 0
                while (this.__loadCss[url] === "loading") {
                    await new Promise(r => setTimeout(r, 1000))
                    i++
                    if (i > 30) {
                        return reject("加载超时")
                    }
                }
                if (this.__loadCss[url] === "loaded") {
                    return resolve(false)
                }
                this.__loadCss[url] = "loading"
                //
                const script = document.createElement('link')
                if (script.readyState) {
                    script.onreadystatechange = () => {
                        if (script.readyState == 'loaded' || script.readyState == 'complete') {
                            script.onreadystatechange = null
                            this.__loadCss[url] = "loaded"
                            resolve(true)
                        }
                    }
                } else {
                    script.onload = () => {
                        this.__loadCss[url] = "loaded"
                        resolve(true)

                    }
                    script.onerror = (e) => {
                        this.__loadCss[url] = "error"
                        reject(e)
                    }
                }
                script.rel = 'stylesheet'
                if (this.rightExists(url, '.css')) {
                    script.href = url + "?hash=" + window.systemInfo.version
                } else {
                    script.href = url
                }
                document.getElementsByTagName('head').item(0).appendChild(script)
            })
        },
        loadCssS(urls) {
            return new Promise(resolve => {
                let i = 0
                const recursiveCallback = () => {
                    if (++i < urls.length) {
                        this.loadCss(urls[i]).finally(recursiveCallback)
                    } else {
                        resolve()
                    }
                }
                this.loadCss(urls[0]).finally(recursiveCallback)
            })
        },
        __loadCss: {},

        /**
         * 动态加载iframe
         * @param url
         * @param loadedRemove
         * @returns {Promise<unknown>}
         */
        loadIframe(url, loadedRemove = 0) {
            return new Promise(async (resolve, reject) => {
                url = $A.originUrl(url)
                //
                let i = 0
                while (this.__loadIframe[url] === "loading") {
                    await new Promise(r => setTimeout(r, 1000))
                    i++
                    if (i > 30) {
                        return reject("加载超时")
                    }
                }
                if (this.__loadIframe[url] === "loaded") {
                    return resolve(false)
                }
                this.__loadIframe[url] = "loading"
                //
                const iframe = document.createElement("iframe")
                iframe.style.display = 'none'
                iframe.src = url
                iframe.onload = () => {
                    this.__loadIframe[url] = "loaded"
                    resolve(true)
                    if (loadedRemove > 0) {
                        setTimeout(() => {
                            document.body.removeChild(iframe)
                            delete this.__loadIframe[url]
                        }, loadedRemove)
                    }
                }
                iframe.onerror = (e) => {
                    this.__loadIframe[url] = "error"
                    reject(e)
                }
                document.body.appendChild(iframe)
            })
        },
        loadIframes(urls) {
            return new Promise(resolve => {
                let i = 0
                const recursiveCallback = () => {
                    if (++i < urls.length) {
                        this.loadIframe(urls[i]).finally(recursiveCallback)
                    } else {
                        resolve()
                    }
                }
                this.loadIframe(urls[0]).finally(recursiveCallback)
            })
        },
        __loadIframe: {},

        /**
         * 字节转换
         * @param bytes
         * @returns {string}
         */
        bytesToSize(bytes) {
            if (bytes === 0) return '0 B';
            let k = 1024;
            let sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            let i = Math.floor(Math.log(bytes) / Math.log(k));
            if (typeof sizes[i] === "undefined") {
                return '0 B';
            }
            return $A.runNum((bytes / Math.pow(k, i)), 2) + ' ' + sizes[i];
        },

        /**
         * html代码转义
         * @param sHtml
         * @returns {*}
         */
        html2Escape(sHtml) {
            if (!sHtml || sHtml == '') {
                return '';
            }
            return sHtml.replace(/[<>&"]/g, function (c) {
                return {'<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;'}[c];
            });
        },

        /**
         * 正则提取域名
         * @param weburl
         * @returns {string|string}
         */
        getDomain(weburl) {
            const urlReg = /http(s)?:\/\/([^\/]+)/i;
            const domain = `${weburl}`.match(urlReg);
            return ((domain != null && domain.length > 0) ? domain[2] : "").toLowerCase();
        },

        /**
         * 提取 URL 协议
         * @param weburl
         * @returns {string}
         */
        getProtocol(weburl) {
            try {
                return new URL(weburl).protocol
            } catch(e){
                return ""
            }
        },

        /**
         * 滚动到View
         * @param element
         * @param options
         */
        scrollToView(element, options) {
            if (!element) {
                return;
            }
            if (typeof options === "undefined" || options === true) {
                options = {block: "start", inline: "nearest"}
            } else if (options === false) {
                options = {block: "end", inline: "nearest"}
            }
            if (typeof options.scrollMode !== "undefined" && typeof window.scrollIntoView === "function") {
                window.scrollIntoView(element, options)
                return;
            }
            try {
                element.scrollIntoView(options);
            } catch (e) {
                if (typeof window.scrollIntoView === "function") {
                    window.scrollIntoView(element, options)
                }
            }
        },

        /**
         * 按需滚动到View
         * @param element
         * @param smooth
         */
        scrollIntoViewIfNeeded(element = null, smooth = false) {
            if (!element) {
                return;
            }
            if (!smooth && typeof element.scrollIntoViewIfNeeded === "function") {
                element.scrollIntoViewIfNeeded()
            } else {
                const options = {
                    block: "nearest",
                    inline: "nearest"
                }
                if (smooth) {
                    options.behavior = 'smooth'
                }
                $A.scrollToView(element, options)
            }
        },

        /**
         * 给元素添加一个class，过指定时间之后再去除这个class
         * @param element
         * @param className
         * @param duration
         */
        addClassWithTimeout(element, className, duration) {
            if (!element || !className || !duration) return;
            element.classList.add(className);
            setTimeout(() => {
                if (!element) return;
                element.classList.remove(className);
            }, duration);
        },

        /**
         * 滚动到元素并抖动
         * @param element
         * @param viewIfNeeded
         */
        scrollIntoAndShake(element, viewIfNeeded = true) {
            if (!element) return;
            const elements = Array.isArray(element) ? element : [element];
            elements.forEach(el => {
                if (el) {
                    viewIfNeeded && $A.scrollIntoViewIfNeeded(el);
                    $A.addClassWithTimeout(el, "common-shake", 800);
                }
            });
        },

        /**
         * 等比缩放尺寸
         * @param width
         * @param height
         * @param maxW
         * @param maxH
         * @returns {{width, height}|{width: number, height: number}}
         */
        scaleToScale(width, height, maxW, maxH = undefined) {
            const maxWidth = maxW;
            const maxHeight = typeof maxH === "undefined" ? maxW : maxH;
            let tempWidth;
            let tempHeight;
            if (width > 0 && height > 0) {
                if (width / height >= maxWidth / maxHeight) {
                    if (width > maxWidth) {
                        tempWidth = maxWidth;
                        tempHeight = (height * maxWidth) / width;
                    } else {
                        tempWidth = width;
                        tempHeight = height;
                    }
                } else {
                    if (height > maxHeight) {
                        tempHeight = maxHeight;
                        tempWidth = (width * maxHeight) / height;
                    } else {
                        tempWidth = width;
                        tempHeight = height;
                    }
                }
                return {width: parseInt(tempWidth), height: parseInt(tempHeight)};
            }
            return {width, height};
        },

        /**
         * 阻止滑动穿透
         * @param el
         */
        scrollPreventThrough(el) {
            if (!el) {
                return;
            }
            if (el.getAttribute("data-prevent-through") === "yes") {
                return;
            }
            el.setAttribute("data-prevent-through", "yes")
            //
            let targetY = null;
            el.addEventListener('touchstart', function (e) {
                targetY = Math.floor(e.targetTouches[0].clientY);
            });
            el.addEventListener('touchmove', function (e) {
                // 检测可滚动区域的滚动事件，如果滑到了顶部或底部，阻止默认事件
                let NewTargetY = Math.floor(e.targetTouches[0].clientY),    //本次移动时鼠标的位置，用于计算
                    sTop = el.scrollTop,        //当前滚动的距离
                    sH = el.scrollHeight,       //可滚动区域的高度
                    lyBoxH = el.clientHeight;   //可视区域的高度
                if (sTop <= 0 && NewTargetY - targetY > 0) {
                    // 下拉页面到顶
                    e.preventDefault();
                } else if (sTop >= sH - lyBoxH && NewTargetY - targetY < 0) {
                    // 上翻页面到底
                    e.preventDefault();
                }
            }, false);
        },

        /**
         * 获取元素属性
         * @param el
         * @param attrName
         * @param def
         * @returns {Property<any>|string|string}
         */
        getAttr(el, attrName, def = "") {
            return el ? el.getAttribute(attrName) : def;
        },

        /**
         * 排序JSON对象
         * @param obj
         * @param ignore
         * @returns {{}}
         */
        sortObject(obj, ignore = []) {
            return Object.keys(obj).sort().reduce(function (result, key) {
                if (!ignore.includes(key)) {
                    result[key] = obj[key];
                }
                return result;
            }, {});
        },

        /**
         * 从HTML中提取图片参数
         * @param imgTag
         * @returns {{original, src: (*|null), width: (number|*), height: (number|*)}}
         */
        extractImageParameter(imgTag) {
            const srcMatch = imgTag.match(/\s+src=(["'])([^'"]*)\1/i);
            const widthMatch = imgTag.match(/\s+width=(["'])([^'"]*)\1/i);
            const heightMatch = imgTag.match(/\s+height=(["'])([^'"]*)\1/i);
            return {
                src: srcMatch ? srcMatch[2] : null,
                width: $A.runNum(widthMatch ? widthMatch[2] : 0),
                height: $A.runNum(heightMatch ? heightMatch[2] : 0),
                original: imgTag,
            }
        },

        /**
         * 从HTML中提取所有图片参数
         * @param html
         * @returns {{original, src: (*|null), width: (number|*), height: (number|*)}[]}
         */
        extractImageParameterAll(html) {
            const imgTags = html.match(/<img\s+[^>]*?>/g) || [];
            return imgTags.map(imgTag => this.extractImageParameter(imgTag));
        },

        /**
         * 增强版的字符串截取
         * @param str
         * @param length
         * @param start
         * @param suffix
         * @returns {string}
         */
        cutString(str, length, start = 0, suffix = '...') {
            const chars = [...str];
            // 如果长度为负数，则从末尾开始计数
            if (length < 0) {
                length = Math.max(chars.length + length, 0);
            }
            // 如果起始位置为负数，则从末尾开始计数
            if (start < 0) {
                start = Math.max(chars.length + start, 0);
            }
            // 如果截取长度为0或起始位置超出字符串长度，返回空字符串
            if (length === 0 || start >= chars.length) {
                return '';
            }
            const sliced = chars.slice(start, start + length);
            // 只有当实际截取的长度小于原字符串长度时才添加后缀
            if (start + length < chars.length) {
                return sliced.join('') + suffix;
            }
            return sliced.join('');
        },

        /**
         * 获取两个数组后面的交集
         * @param arr1
         * @param arr2
         * @returns {*}
         */
        getLastSameElements(arr1, arr2) {
            return arr1.slice(-(arr1.filter((item, index) =>
                item === arr2[arr2.length - arr1.length + index]
            ).length));
        },

        /**
         * 查找元素并在失败时重试
         * @param {Function} findElementFn - 查找元素的函数
         * @param {number} maxAttempts - 最大尝试次数
         * @param {number} delayMs - 每次尝试之间的延迟(毫秒)
         * @returns {Promise<*>}
         */
        async findElementWithRetry(findElementFn, maxAttempts = 3, delayMs = 500) {
            for (let attempt = 1; attempt <= maxAttempts; attempt++) {
                const element = findElementFn();

                if (element) {
                    return element;
                }

                if (attempt < maxAttempts) {
                    await new Promise(resolve => setTimeout(resolve, delayMs));
                }
            }
            throw new Error(`Element not found after ${maxAttempts} attempts`);
        },

        /**
         * 轮询等待条件满足
         * @param {Function} conditionFn - 返回布尔值的条件函数
         * @param {number} intervalMs - 轮询间隔(毫秒,默认300ms)
         * @param {number} timeoutMs - 超时时间(毫秒,默认3000ms)
         * @returns {Promise<boolean>}
         */
        async waitForCondition(conditionFn, intervalMs = 300, timeoutMs = 3000) {
            const startTime = Date.now();

            while (Date.now() - startTime < timeoutMs) {
                if (conditionFn()) {
                    return true; // 条件满足
                }
                // 等待指定时间
                await new Promise(resolve => setTimeout(resolve, intervalMs));
            }

            throw new Error('等待条件超时');
        },

        /**
         * 执行指定次数的定时任务，返回一个可以取消的对象
         * @param {Function} fn 要执行的函数
         * @param {number} delay 首次执行的延迟时间(毫秒)
         * @param {number} interval 间隔时间(毫秒)
         * @param {number} times 执行次数
         * @returns {object} 包含 clear 方法的对象
         */
        repeatWithCount(fn, delay, interval = 0, times = 0) {
            if (typeof fn !== 'function') {
                return () => {}; // 返回空函数而不是null，保持返回类型一致
            }

            let count = 0;
            let timer = null;

            const clear = () => {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
            };

            const execute = () => {
                if (count >= times) {
                    clear();
                    return;
                }

                try {
                    if (fn(count) === true) {
                        clear();
                        return;
                    }
                } catch (error) {
                    clear();
                    console.error('Error in callback function:', error);
                    return;
                }

                count++;
                timer = setTimeout(execute, interval);
            };

            // 立即开始第一次执行
            timer = setTimeout(execute, delay);

            // 直接返回clear函数
            return clear;
        },

        /**
         * 通过URL生成base64图片
         * @param url       图片URL
         * @param quality   图片质量，默认0.8
         * @param maxWidth  最大宽度，0表示不限制
         * @param maxHeight 最大高度，0表示不限制
         * @returns {Promise<unknown>}
         */
        generateBase64Image(url, quality = 1, maxWidth = 0, maxHeight = 0) {
            return new Promise(resolve => {
                let canvas = document.createElement('canvas'),
                    ctx = canvas.getContext('2d'),
                    img = new Image;
                img.crossOrigin = 'Anonymous';
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    // 处理等比例缩放
                    if ((maxWidth > 0 || maxHeight > 0) && (width > 0 && height > 0)) {
                        // 计算宽高比
                        const ratio = width / height;

                        if (maxWidth > 0 && maxHeight > 0) {
                            // 同时指定了最大宽度和高度，按照最小比例缩放
                            if (width > maxWidth || height > maxHeight) {
                                const ratioWidth = maxWidth / width;
                                const ratioHeight = maxHeight / height;
                                const ratioMin = Math.min(ratioWidth, ratioHeight);

                                width = Math.round(width * ratioMin);
                                height = Math.round(height * ratioMin);
                            }
                        } else if (maxWidth > 0 && width > maxWidth) {
                            // 只指定了最大宽度
                            width = maxWidth;
                            height = Math.round(width / ratio);
                        } else if (maxHeight > 0 && height > maxHeight) {
                            // 只指定了最大高度
                            height = maxHeight;
                            width = Math.round(height * ratio);
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    let format = "png";
                    if ($A.rightExists(url, "jpg") || $A.rightExists(url, "jpeg")) {
                        format = "jpeg"
                    } else if ($A.rightExists(url, "webp")) {
                        format = "webp"
                    } else if ($A.rightExists(url, "git")) {
                        format = "git"
                    }
                    resolve(canvas.toDataURL(`image/${format}`, quality));

                    // 清理资源
                    canvas = null;
                    img = null;
                    ctx = null;
                };
                img.src = url;
            })
        },

        /**
         * 是否全屏（根据尺寸对比）
         * @returns {boolean}
         */
        isFullScreen() {
            const windowWidth = $A(window).width();
            const windowHeight = $A(window).height();
            const screenWidth = window.screen.width;
            const screenHeight = window.screen.height;

            // 如果高比宽大，对调宽高
            const adjustedWindowWidth = windowWidth > windowHeight ? windowWidth : windowHeight;
            const adjustedWindowHeight = windowWidth > windowHeight ? windowHeight : windowWidth;
            const adjustedScreenWidth = screenWidth > screenHeight ? screenWidth : screenHeight;
            const adjustedScreenHeight = screenWidth > screenHeight ? screenHeight : screenWidth;

            // 判断是否全屏，误差1内视为全屏
            const widthDiff = Math.abs(adjustedWindowWidth - adjustedScreenWidth);
            const heightDiff = Math.abs(adjustedWindowHeight - adjustedScreenHeight);

            return widthDiff <= 1 && heightDiff <= 1;
        }
    });

    /**
     * =============================================================================
     * *****************************   localForage   ******************************
     * =============================================================================
     */
    $.extend({
        __IDBTimer: {},

        async IDBTest() {
            try {
                if ($A.isIos()) {
                    await localforage.setItem('__test__', $A.dayjs().valueOf())
                }
                $A.openLog && console.log('IDBTest OK')
                return true;
            } catch (error) {
                if ($A.openLog) {
                    console.error('IDBTest Error: ', error)
                    $A.modalWarning({
                        content: error.message,
                        onOk: () => {
                            $A.reloadUrl();
                        }
                    });
                } else {
                    $A.reloadUrl();
                }
                return false;
            }
        },

        IDBSave(key, value, delay = 100) {
            if (typeof this.__IDBTimer[key] !== "undefined") {
                clearTimeout(this.__IDBTimer[key])
                delete this.__IDBTimer[key]
            }
            this.__IDBTimer[key] = setTimeout(async _ => {
                await localforage.setItem(key, value)
                delete this.__IDBTimer[key]
            }, delay)
        },

        IDBDel(key) {
            return localforage.removeItem(key)
        },

        IDBSet(key, value) {
            return localforage.setItem(key, value)
        },

        IDBRemove(key) {
            return localforage.removeItem(key)
        },

        /**
         * 清除缓存
         * @param {string[]} [keysToKeep] - 可选，需要保留的 key 数组
         * @returns {Promise<void>}
         */
        async IDBClear(keysToKeep = []) {
            if (!keysToKeep || !keysToKeep.length) {
                return localforage.clear();
            }
            const cached = {};
            await Promise.all(
                keysToKeep.map(async key => {
                    cached[key] = await this.IDBValue(key);
                })
            );
            await localforage.clear();
            await Promise.all(
                Object.entries(cached)
                    .filter(([, value]) => value !== null && value !== undefined)
                    .map(([key, value]) => this.IDBSet(key, value))
            );
        },

        IDBValue(key) {
            return localforage.getItem(key)
        },

        async IDBString(key, def = "") {
            const value = await this.IDBValue(key)
            return typeof value === "string" || typeof value === "number" ? value : def;
        },

        async IDBInt(key, def = 0) {
            const value = await this.IDBValue(key)
            return typeof value === "number" ? value : def;
        },

        async IDBBoolean(key, def = false) {
            const value = await this.IDBValue(key)
            return typeof value === "boolean" ? value : def;
        },

        async IDBArray(key, def = []) {
            const value = await this.IDBValue(key)
            return this.isArray(value) ? value : def;
        },

        async IDBJson(key, def = {}) {
            const value = await this.IDBValue(key)
            return this.isJson(value) ? value : def;
        }
    });

    /**
     * =============================================================================
     * *****************************   localStorage   ******************************
     * =============================================================================
     */
    $.extend({
        setStorage(key, value) {
            return this.__operationStorage(key, value);
        },

        getStorageValue(key) {
            return this.__operationStorage(key);
        },

        getStorageString(key, def = '') {
            let value = this.__operationStorage(key);
            return typeof value === "string" || typeof value === "number" ? value : def;
        },

        getStorageInt(key, def = 0) {
            let value = this.__operationStorage(key);
            return typeof value === "number" ? value : def;
        },

        getStorageBoolean(key, def = false) {
            let value = this.__operationStorage(key);
            return typeof value === "boolean" ? value : def;
        },

        getStorageArray(key, def = []) {
            let value = this.__operationStorage(key);
            return this.isArray(value) ? value : def;
        },

        getStorageJson(key, def = {}) {
            let value = this.__operationStorage(key);
            return this.isJson(value) ? value : def;
        },

        existsStorage(key) {
            return this.__operationStorage(key) !== null;
        },

        __operationStorage(key, value) {
            if (!key) {
                return;
            }
            let keyName = '__state__';
            const keyArr = key.split(".");
            if (keyArr.length > 1) {
                const stateName = keyArr.shift();
                keyName = '__state:' + stateName + '__';
                key = keyArr.join(".");
            }
            if (typeof value === 'undefined') {
                return this.__loadFromlLocal(key, null, keyName);
            } else {
                this.__savaToLocal(key, value, keyName);
            }
        },

        __savaToLocal(key, value, keyName) {
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

        __loadFromlLocal(key, def, keyName) {
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
    });

    /**
     * =============================================================================
     * *****************************   sessionStorage   ****************************
     * =============================================================================
     */
    $.extend({
        setSessionStorage(key, value) {
            return this.__operationSessionStorage(key, value);
        },

        getSessionStorageValue(key) {
            return this.__operationSessionStorage(key);
        },

        getSessionStorageString(key, def = '') {
            let value = this.__operationSessionStorage(key);
            return typeof value === "string" || typeof value === "number" ? value : def;
        },

        getSessionStorageInt(key, def = 0) {
            let value = this.__operationSessionStorage(key);
            return typeof value === "number" ? value : def;
        },

        __operationSessionStorage(key, value) {
            if (!key) {
                return;
            }
            let keyName = '__state__';
            if (key.substring(0, 5) === 'cache') {
                keyName = '__state:' + key + '__';
            }
            if (typeof value === 'undefined') {
                return this.__loadFromSession(key, '', keyName);
            } else {
                this.__savaToSession(key, value, keyName);
            }
        },

        __savaToSession(key, value, keyName) {
            try {
                if (typeof keyName === 'undefined') keyName = '__seller__';
                let seller = window.sessionStorage.getItem(keyName);
                if (!seller) {
                    seller = {};
                } else {
                    seller = JSON.parse(seller);
                }
                seller[key] = value;
                window.sessionStorage.setItem(keyName, JSON.stringify(seller))
            } catch (e) {
            }
        },

        __loadFromSession(key, def, keyName) {
            try {
                if (typeof keyName === 'undefined') keyName = '__seller__';
                let seller = window.sessionStorage.getItem(keyName);
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
    });

    /**
     * =============================================================================
     * *********************************   ihttp   *********************************
     * =============================================================================
     */
    $.extend({
        serializeObject(obj, parents) {
            if (typeof obj === 'string') return obj;
            let resultArray = [];
            let separator = '&';
            parents = parents || [];
            let newParents;

            function var_name(name) {
                if (parents.length > 0) {
                    let _parents = '';
                    for (let j = 0; j < parents.length; j++) {
                        if (j === 0) _parents += parents[j];
                        else _parents += '[' + encodeURIComponent(parents[j]) + ']';
                    }
                    return _parents + '[' + encodeURIComponent(name) + ']';
                }
                else {
                    return encodeURIComponent(name);
                }
            }

            function var_value(value) {
                return encodeURIComponent(value);
            }

            for (let prop in obj) {
                if (obj.hasOwnProperty(prop)) {
                    let toPush;
                    if (Array.isArray(obj[prop])) {
                        toPush = [];
                        for (let i = 0; i < obj[prop].length; i++) {
                            if (!Array.isArray(obj[prop][i]) && typeof obj[prop][i] === 'object') {
                                newParents = parents.slice();
                                newParents.push(prop);
                                newParents.push(i + '');
                                toPush.push($.serializeObject(obj[prop][i], newParents));
                            }
                            else {
                                toPush.push(var_name(prop) + '[]=' + var_value(obj[prop][i]));
                            }

                        }
                        if (toPush.length > 0) resultArray.push(toPush.join(separator));
                    }
                    else if (obj[prop] === null) {
                        resultArray.push(var_name(prop) + '=');
                    }
                    else if (typeof obj[prop] === 'object') {
                        // Object, convert to named array
                        newParents = parents.slice();
                        newParents.push(prop);
                        toPush = $.serializeObject(obj[prop], newParents);
                        if (toPush !== '') resultArray.push(toPush);
                    }
                    else if (typeof obj[prop] !== 'undefined' && obj[prop] !== '') {
                        // Should be string or plain value
                        resultArray.push(var_name(prop) + '=' + var_value(obj[prop]));
                    }
                    else if (obj[prop] === '') resultArray.push(var_name(prop));
                }
            }
            return resultArray.join(separator);
        },

        // Global Ajax Setup
        globalAjaxOptions: {},
        ajaxSetup (options) {
            if (options.type) options.method = options.type;
            $.each(options, function (optionName, optionValue) {
                $.globalAjaxOptions[optionName] = optionValue;
            });
        },

        // Ajax
        _jsonpRequests: 0,
        ihttp(options) {
            let defaults = {
                method: 'GET',
                data: false,
                async: true,
                cache: true,
                user: '',
                password: '',
                headers: {},
                xhrFields: {},
                statusCode: {},
                processData: true,
                dataType: 'text',
                contentType: 'application/x-www-form-urlencoded',
                timeout: 0
            };
            const callbacks = ['beforeSend', 'error', 'complete', 'success', 'statusCode'];


            //For jQuery guys
            if (options.type) options.method = options.type;

            // Merge global and defaults
            $.each($.globalAjaxOptions, function (globalOptionName, globalOptionValue) {
                if (callbacks.indexOf(globalOptionName) < 0) defaults[globalOptionName] = globalOptionValue;
            });

            // Function to run XHR callbacks and events
            function fireAjaxCallback(eventName, eventData, callbackName) {
                let a = arguments;
                if (eventName) $(document).trigger(eventName, eventData);
                if (callbackName) {
                    // Global callback
                    if (callbackName in $.globalAjaxOptions) $.globalAjaxOptions[callbackName](a[3], a[4], a[5], a[6]);
                    // Options callback
                    if (options[callbackName]) options[callbackName](a[3], a[4], a[5], a[6]);
                }
            }

            // Merge options and defaults
            $.each(defaults, function (prop, defaultValue) {
                if (!(prop in options)) options[prop] = defaultValue;
            });

            // Default URL
            if (!options.url) {
                options.url = window.location.toString();
            }
            // Parameters Prefix
            let paramsPrefix = options.url.indexOf('?') >= 0 ? '&' : '?';

            // UC method
            let _method = options.method.toUpperCase();
            // Data to modify GET URL
            if ((_method === 'GET' || _method === 'HEAD' || _method === 'OPTIONS' || _method === 'DELETE') && options.data) {
                let stringData;
                if (typeof options.data === 'string') {
                    // Should be key=value string
                    if (options.data.indexOf('?') >= 0) stringData = options.data.split('?')[1];
                    else stringData = options.data;
                }
                else {
                    // Should be key=value object
                    stringData = $.serializeObject(options.data);
                }
                if (stringData.length) {
                    options.url += paramsPrefix + stringData;
                    if (paramsPrefix === '?') paramsPrefix = '&';
                }
            }
            // JSONP
            if (options.dataType === 'json' && options.url.indexOf('callback=') >= 0) {
                let callbackName = '__jsonp_' + Date.now() + ($._jsonpRequests++);
                let abortTimeout;
                let callbackSplit = options.url.split('callback=');
                let requestUrl = callbackSplit[0] + 'callback=' + callbackName;
                if (callbackSplit[1].indexOf('&') >= 0) {
                    let addVars = callbackSplit[1].split('&').filter(function (el) {
                        return el.indexOf('=') > 0;
                    }).join('&');
                    if (addVars.length > 0) requestUrl += '&' + addVars;
                }

                // Create script
                let script = document.createElement('script');
                script.type = 'text/javascript';
                script.onerror = function () {
                    clearTimeout(abortTimeout);
                    fireAjaxCallback(undefined, undefined, 'error', null, 'scripterror');
                    fireAjaxCallback('ajaxComplete ajax:complete', {scripterror: true}, 'complete', null, 'scripterror');
                };
                script.src = requestUrl;

                // Handler
                window[callbackName] = function (data) {
                    clearTimeout(abortTimeout);
                    fireAjaxCallback(undefined, undefined, 'success', data);
                    script.parentNode.removeChild(script);
                    script = null;
                    delete window[callbackName];
                };
                document.querySelector('head').appendChild(script);

                if (options.timeout > 0) {
                    abortTimeout = setTimeout(function () {
                        script.parentNode.removeChild(script);
                        script = null;
                        fireAjaxCallback(undefined, undefined, 'error', null, 'timeout');
                    }, options.timeout);
                }

                return;
            }

            // Cache for GET/HEAD requests
            if (_method === 'GET' || _method === 'HEAD' || _method === 'OPTIONS' || _method === 'DELETE') {
                if (options.cache === false) {
                    options.url += (paramsPrefix + '_nocache=' + Date.now());
                }
            }

            // Create XHR
            const xhr = new XMLHttpRequest();

            // 添加请求开始时间记录
            const requestStartTime = Date.now();

            // Save Request URL
            xhr.requestUrl = options.url;
            xhr.requestParameters = options;

            // Open XHR
            xhr.open(_method, options.url, options.async, options.user, options.password);

            // Create POST Data
            let postData = null;

            if ((_method === 'POST' || _method === 'PUT' || _method === 'PATCH') && options.data) {
                if (options.processData) {
                    let postDataInstances = [ArrayBuffer, Blob, Document, FormData];
                    // Post Data
                    if (postDataInstances.indexOf(options.data.constructor) >= 0) {
                        postData = options.data;
                    }
                    else {
                        // POST Headers
                        let boundary = '---------------------------' + Date.now().toString(16);

                        if (options.contentType === 'multipart\/form-data') {
                            xhr.setRequestHeader('Content-Type', 'multipart\/form-data; boundary=' + boundary);
                        }
                        else {
                            xhr.setRequestHeader('Content-Type', options.contentType);
                        }
                        postData = '';
                        let _data = $.serializeObject(options.data);
                        if (options.contentType === 'multipart\/form-data') {
                            boundary = '---------------------------' + Date.now().toString(16);
                            _data = _data.split('&');
                            let _newData = [];
                            for (let i = 0; i < _data.length; i++) {
                                _newData.push('Content-Disposition: form-data; name="' + _data[i].split('=')[0] + '"\r\n\r\n' + _data[i].split('=')[1] + '\r\n');
                            }
                            postData = '--' + boundary + '\r\n' + _newData.join('--' + boundary + '\r\n') + '--' + boundary + '--\r\n';
                        }
                        else {
                            postData = _data;
                        }
                    }
                }
                else {
                    postData = options.data;
                }

            }

            // Additional headers
            if (options.headers) {
                $.each(options.headers, function (headerName, headerCallback) {
                    xhr.setRequestHeader(headerName, headerCallback);
                });
            }

            // Check for crossDomain
            if (typeof options.crossDomain === 'undefined') {
                options.crossDomain = /^([\w-]+:)?\/\/([^\/]+)/.test(options.url) && RegExp.$2 !== window.location.host;
            }

            if (!options.crossDomain) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            }

            if (options.xhrFields) {
                $.each(options.xhrFields, function (fieldName, fieldValue) {
                    xhr[fieldName] = fieldValue;
                });
            }

            let xhrTimeout;
            // Handle XHR
            xhr.onload = function (e) {
                if (xhrTimeout) clearTimeout(xhrTimeout);
                if ((xhr.status >= 200 && xhr.status < 300) || xhr.status === 0) {
                    // 计算请求耗时
                    const requestDuration = Date.now() - requestStartTime;

                    // 获取响应标头时间信息
                    const getResponseHeader = (header) => {
                        try {
                            return xhr.getResponseHeader(header);
                        } catch (e) {
                            return null;
                        }
                    }
                    const serverDate = getResponseHeader('Date');
                    const lastModified = getResponseHeader('Last-Modified');
                    const age = getResponseHeader('Age');

                    // 将时间信息添加到响应对象中
                    xhr.timeData = {
                        serverDate: serverDate,
                        lastModified: lastModified,
                        age: age,
                        duration: requestDuration
                    };

                    let responseData;
                    if (options.dataType === 'json') {
                        try {
                            responseData = JSON.parse(xhr.responseText);
                            fireAjaxCallback('ajaxSuccess ajax:success', {xhr: xhr}, 'success', responseData, xhr.status, xhr);
                        }
                        catch (err) {
                            console.error(err);
                            fireAjaxCallback('ajaxError ajax:error', {
                                xhr: xhr,
                                parseerror: true
                            }, 'error', xhr, 'parseerror');
                        }
                    }
                    else {
                        responseData = xhr.responseType === 'text' || xhr.responseType === '' ? xhr.responseText : xhr.response;
                        fireAjaxCallback('ajaxSuccess ajax:success', {xhr: xhr}, 'success', responseData, xhr.status, xhr);
                    }
                }
                else {
                    fireAjaxCallback('ajaxError ajax:error', {xhr: xhr}, 'error', xhr, xhr.status);
                }
                if (options.statusCode) {
                    if ($.globalAjaxOptions.statusCode && $.globalAjaxOptions.statusCode[xhr.status]) $.globalAjaxOptions.statusCode[xhr.status](xhr);
                    if (options.statusCode[xhr.status]) options.statusCode[xhr.status](xhr);
                }
                fireAjaxCallback('ajaxComplete ajax:complete', {xhr: xhr}, 'complete', xhr, xhr.status);
            };

            xhr.onerror = function (e) {
                if (xhrTimeout) clearTimeout(xhrTimeout);
                fireAjaxCallback('ajaxError ajax:error', {xhr: xhr}, 'error', xhr, xhr.status);
                fireAjaxCallback('ajaxComplete ajax:complete', {xhr: xhr, error: true}, 'complete', xhr, 'error');
            };

            // Ajax start callback
            fireAjaxCallback('ajaxStart ajax:start', {xhr: xhr}, 'start', xhr);
            fireAjaxCallback(undefined, undefined, 'beforeSend', xhr);

            // Timeout
            if (options.timeout > 0) {
                xhr.onabort = function () {
                    if (xhrTimeout) clearTimeout(xhrTimeout);
                };
                xhrTimeout = setTimeout(function () {
                    xhr.abort();
                    fireAjaxCallback('ajaxError ajax:error', {xhr: xhr, timeout: true}, 'error', xhr, 'timeout');
                    fireAjaxCallback('ajaxComplete ajax:complete', {
                        xhr: xhr,
                        timeout: true
                    }, 'complete', xhr, 'timeout');
                }, options.timeout);
            }

            // Send XHR
            xhr.send(postData);

            // Return XHR object
            return xhr;
        }
    });

    /**
     * =============================================================================
     * ***********************************   ajaxc   *******************************
     * =============================================================================
     */
    $.extend({
        ajaxc(params) {
            if (!params) return false;
            if (typeof params.url === 'undefined') return false;
            if (typeof params.data === 'undefined') params.data = {};
            if (typeof params.cache === 'undefined') params.cache = false;
            if (typeof params.method === 'undefined') params.method = 'GET';
            if (typeof params.timeout === 'undefined') params.timeout = 30000;
            if (typeof params.dataType === 'undefined') params.dataType = 'json';
            if (typeof params.before === 'undefined') params.before = () => { };
            if (typeof params.complete === 'undefined') params.complete = () => { };
            if (typeof params.after === 'undefined') params.after = () => { };
            if (typeof params.success === 'undefined') params.success = () => { };
            if (typeof params.error === 'undefined') params.error = () => { };
            if (typeof params.header == 'undefined') params.header = {};
            const key = $A.randomString(16);
            //
            params.before();
            $A.__ajaxList.push({
                key,
                id: params.requestId || null,
                url: params.url,
                request: $A.ihttp({
                    url: params.url,
                    data: params.data,
                    cache: params.cache,
                    headers: params.header,
                    method: params.method.toUpperCase(),
                    contentType: "OPTIONS",
                    crossDomain: true,
                    dataType: params.dataType,
                    timeout: params.timeout,
                    success: function (data, status, xhr) {
                        $A.__ajaxList = $A.__ajaxList.filter(val => val.key !== key);
                        params.complete();
                        params.success(data, status, xhr);
                        params.after(true);
                    },
                    error: function (xhr, status) {
                        $A.__ajaxList = $A.__ajaxList.filter(val => val.key !== key);
                        params.complete();
                        params.error(xhr, status);
                        params.after(false);
                    }
                })
            });
        },
        ajaxcCancel(requestId) {
            if (!requestId) {
                return 0;
            }
            let num = 0;
            $A.__ajaxList.forEach((val, index) => {
                if (val.id === requestId) {
                    num++;
                    if (val.request) {
                        val.request.abort();
                    }
                }
            });
            if (num > 0) {
                $A.__ajaxList = $A.__ajaxList.filter(val => val.id !== requestId);
            }
            return num;
        },
        __ajaxList: [],
    });

    /**
     * =============================================================================
     * ***********************************   time   ********************************
     * =============================================================================
     */

    dayjs.extend(utc);
    dayjs.extend(timezone);
    $.extend({
        /**
         * 时间对象
         * @param v
         * @returns {*|dayjs.Dayjs}
         */
        dayjs(v = undefined) {
            if (/^\d{13,}$/.test(v)) {
                return dayjs(Number(v));
            }
            if (/^\d{10,}$/.test(v)) {
                return dayjs(Number(v) * 1000);
            }
            if (v === null) {
                v = 0
            }
            return dayjs(v);
        },

        /**
         * 时间对象（减去时区差）
         * @param v
         * @returns {*|dayjs.Dayjs}
         */
        daytz(v = undefined) {
            const t = $A.dayjs(v)
            if ($A.timezoneDifference) {
                return t.subtract($A.timezoneDifference, "hour")
            }
            return t;
        },

        /**
         * 更新时区
         * @param tz
         * @returns {number}
         */
        updateTimezone(tz = undefined) {
            if (typeof tz !== "undefined") {
                $A.timezoneName = tz;
            }
            if (!$A.timezoneName) {
                return $A.timezoneDifference = 0;
            }
            const local = $A.daytz().startOf('hour');
            const server = local.tz($A.timezoneName);
            return $A.timezoneDifference = local.startOf('hour').diff(server.format("YYYY-MM-DD HH:mm:ss"), 'hour')
        },
        timezoneName: null,
        timezoneDifference: 0,

        /**
         * 对象中有Date格式的转成指定格式
         * @param value     支持类型：dayjs、Date、string
         * @param format    默认格式：YYYY-MM-DD HH:mm:ss
         * @returns {*}
         */
        newDateString(value, format = "YYYY-MM-DD HH:mm:ss") {
            if (value === null) {
                return value;
            }
            if (value instanceof dayjs || value instanceof Date || $A.isDateString(value)) {
                value = $A.dayjs(value).format(format);
            } else if ($A.isJson(value)) {
                value = Object.assign({}, value)
                for (let key in value) {
                    if (!value.hasOwnProperty(key)) continue;
                    value[key] = $A.newDateString(value[key], format);
                }
            } else if ($A.isArray(value)) {
                value = Object.assign([], value)
                value.forEach((val, index) => {
                    value[index] = $A.newDateString(val, format);
                });
            }
            return value;
        },

        /**
         * 对象中有Date格式的转成时间戳
         * @param value
         * @returns {number|*}
         */
        newTimestamp(value) {
            if (value === null) {
                return value;
            }
            if (value instanceof dayjs || value instanceof Date || $A.isDateString(value)) {
                value = $A.dayjs(value).unix();
            } else if ($A.isJson(value)) {
                value = Object.assign({}, value)
                for (let key in value) {
                    if (!value.hasOwnProperty(key)) continue;
                    value[key] = $A.newTimestamp(value[key]);
                }
            } else if ($A.isArray(value)) {
                value = Object.assign([], value)
                value.forEach((val, index) => {
                    value[index] = $A.newTimestamp(val);
                });
            }
            return value;
        },

        /**
         * 判断是否是日期格式
         * 支持格式：YYYY-MM-DD HH:mm:ss、YYYY-MM-DD HH:mm、YYYY-MM-DD HH、YYYY-MM-DD
         * @param value
         * @returns {boolean}
         */
        isDateString(value) {
            return typeof value === "string" && /^\d{4}[/-]\d{2}[/-]\d{2}(\s+\d{2}(:\d{2}(:\d{2})?)?)?$/i.test(value);
        },

        /**
         * 秒数倒计时，格式：00:00:00, 00:00, 0s
         * @param s
         * @returns {string}
         */
        secondsToTime(s) {
            let pre = '';
            if (s < 0) {
                pre = '-';
                s = -s;
            }
            let duration
            const days = Math.floor(s / 86400);
            const hours = Math.floor((s % 86400) / 3600);
            const minutes = Math.floor(((s % 86400) % 3600) / 60);
            const seconds = Math.floor(((s % 86400) % 3600) % 60);
            if (days > 0) {
                if (hours > 0) duration = days + "d," + $A.zeroFill(hours, 2) + "h";
                else if (minutes > 0) duration = days + "d," + $A.zeroFill(minutes, 2) + "min";
                else if (seconds > 0) duration = days + "d," + $A.zeroFill(seconds, 2) + "s";
                else duration = days + "d";
            }
            else if (hours > 0) duration = $A.zeroFill(hours, 2) + ":" + $A.zeroFill(minutes, 2) + ":" + $A.zeroFill(seconds, 2);
            else if (minutes > 0) duration = $A.zeroFill(minutes, 2) + ":" + $A.zeroFill(seconds, 2);
            else if (seconds > 0) duration = $A.zeroFill(seconds, 2) + "s";
            return pre + duration;
        },

        /**
         * 格式化时间（本地时间自动减去时区差）
         * @param date
         * @returns {string}
         */
        timeFormat(date) {
            const local = $A.daytz(),
                time = $A.dayjs(date);
            if (local.format("YYYY-MM-DD") === time.format("YYYY-MM-DD")) {
                return time.format("HH:mm")
            }
            if (local.clone().subtract(1, 'day').format('YYYY-MM-DD') === time.format("YYYY-MM-DD")) {
                return `${$A.L('昨天')} ${time.format("HH:mm")}`
            }
            if (local.year() === time.year()) {
                return time.format("MM-DD")
            }
            return time.format("YYYY-MM-DD") || '';
        },

        /**
         * 倒计时
         * @param s 开始时间自动减去时区差
         * @param e
         * @returns {string}
         */
        countDownFormat(s, e) {
            s = $A.daytz(s)
            e = $A.dayjs(e)
            const diff = e.diff(s, 'second');
            if (diff == 0) {
                return '0s';
            }
            if (Math.abs(diff) < 86400 * 7) {
                return $A.secondsToTime(diff);
            }
            return $A.timeFormat(e)
        },
    });

    /**
     * =============================================================================
     * ***********************************   sort   ********************************
     * =============================================================================
     */
    $.extend({
        /**
         * 计算排序值 （日期格式）
         * @param v1
         * @param v2
         * @returns {number}
         */
        sortDay(v1, v2) {
            if (v1 === v2) return 0;
            return ($A.dayjs(v1).valueOf() || 0) - ($A.dayjs(v2).valueOf() || 0);
        },

        /**
         * 计算排序值 （数字格式）
         * @param v1
         * @param v2
         * @returns {number}
         */
        sortFloat(v1, v2) {
            if (v1 === v2) return 0;
            return (parseFloat(v1) || 0) - (parseFloat(v2) || 0);
        }
    });

    window.$A = $;
})(window, window.$ = window.jQuery = require('jquery'));
