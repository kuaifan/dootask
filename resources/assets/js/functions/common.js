/**
 * 基础函数
 */
(function (window, $, undefined) {

    /**
     * =============================================================================
     * **************************   基础函数类   **************************
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
        last: function (array) {
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
        strExists: function (string, find, lower = false) {
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
        leftExists: function (string, find, lower = false) {
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
        leftDelete: function (string, find, lower = false) {
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
        rightExists: function (string, find, lower = false) {
            string += "";
            find += "";
            if (lower !== true) {
                string = string.toLowerCase();
                find = find.toLowerCase();
            }
            return (string.substring(string.length - find.length) === find);
        },

        /**
         * 取字符串中间
         * @param string
         * @param start
         * @param end
         * @returns {*}
         */
        getMiddle: function (string, start, end) {
            string = string.toString();
            if (this.ishave(start) && this.strExists(string, start)) {
                string = string.substring(string.indexOf(start) + start.length);
            }
            if (this.ishave(end) && this.strExists(string, end)) {
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
        subString: function(string, start, end) {
            string += "";
            if (!this.ishave(end)) {
                end = string.length;
            }
            return string.substring(start, end);
        },

        /**
         * 随机字符
         * @param len
         * @returns {string}
         */
        randomString: function (len) {
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
         * @param set
         * @returns {boolean}
         */
        ishave: function (set) {
            return !!(set !== null && set !== "null" && set !== undefined && set !== "undefined" && set);
        },

        /**
         * 相当于 intval
         * @param str
         * @param fixed
         * @returns {number}
         */
        runNum: function (str, fixed) {
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

        /**
         * 服务器地址
         * @param str
         * @returns {string}
         */
        originUrl: function (str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            if (window.systemInformation && typeof window.systemInformation.origin === "string") {
                return window.systemInformation.origin + str;
            } else {
                return window.location.origin + "/" + str;
            }
        },

        /**
         * 新增&&获取缓存数据
         * @param key
         * @param value
         * @returns {*}
         */
        storage: function(key, value) {
            let keyName = 'app';
            switch (window.location.pathname) {
                case "/admin":
                    keyName+= ":" + window.location.pathname.substr(1);
                    break;
            }
            if (typeof value === 'undefined') {
                return this.loadFromlLocal('__::', key, '', '__' + keyName + '__');
            }else{
                this.savaToLocal('__::', key, value, '__' + keyName + '__');
            }
        },

        /**
         *  新增&&修改本地缓存
         *  @param {string} id 唯一id
         *  @param {string} key 标示
         *  @param value 新增&修改的值
         *  @param keyName 主键名称
         */
        savaToLocal: function(id, key, value, keyName) {
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
            } catch(e) { }
        },

        /**
         *  查询本地缓存
         *  @param {string} id 唯一id
         *  @param {string} key 标示
         *  @param def 如果查询不到显示的值
         *  @param keyName 主键名称
         */
        loadFromlLocal: function(id, key, def, keyName) {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                return def;
            }
            seller = JSON.parse(seller)[id];
            if (!seller) {
                return def;
            }
            let ret = seller[key];
            return ret || def;
        },

        /**
         * 补零
         * @param str
         * @param length
         * @param after
         * @returns {*}
         */
        zeroFill: function(str, length, after) {
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

        /**
         * 时间戳转时间格式
         * @param format
         * @param v
         * @returns {string}
         */
        formatDate: function(format, v) {
            if (typeof format === 'undefined' || format === '') {
                format = 'Y-m-d H:i:s';
            }
            let dateObj;
            if (v instanceof Date) {
                dateObj = v;
            }else {
                if (typeof v === 'undefined') {
                    v = new Date().getTime();
                }else if (/^(-)?\d{1,10}$/.test(v)) {
                    v = v * 1000;
                } else if (/^(-)?\d{1,13}$/.test(v)) {
                    v = v * 1000;
                } else if (/^(-)?\d{1,14}$/.test(v)) {
                    v = v * 100;
                } else if (/^(-)?\d{1,15}$/.test(v)) {
                    v = v * 10;
                } else if (/^(-)?\d{1,16}$/.test(v)) {
                    v = v * 1;
                } else {
                    return v;
                }
                dateObj = new Date(v);
            }
            //
            format = format.replace(/Y/g, dateObj.getFullYear());
            format = format.replace(/m/g, this.zeroFill(dateObj.getMonth() + 1, 2));
            format = format.replace(/d/g, this.zeroFill(dateObj.getDate(), 2));
            format = format.replace(/H/g, this.zeroFill(dateObj.getHours(), 2));
            format = format.replace(/i/g, this.zeroFill(dateObj.getMinutes(), 2));
            format = format.replace(/s/g, this.zeroFill(dateObj.getSeconds(), 2));
            return format;
        },

        /**
         * 租用时间差(不够1个小时算一个小时)
         * @param s
         * @param e
         * @returns {*}
         */
        timeDiff: function(s, e) {
            if (typeof e === 'undefined') {
                e = Math.round(new Date().getTime()/1000);
            }
            let d = e - s;
            if (d > 86400) {
                let day = Math.floor(d / 86400);
                let hour = Math.ceil((d - (day * 86400)) / 3600);
                if (hour > 0) {
                    return day + '天' + hour + '小时';
                } else {
                    return day + '天';
                }
            } else if (d > 3600) {
                return Math.ceil(d / 3600) + '小时';
            } else if (d > 60) {
                return Math.ceil(d / 60) + '分钟';
            } else if (d > 10) {
                return d + '秒';
            } else {
                return '刚刚';
            }
        },

        /**
         * 检测手机号码格式
         * @param str
         * @returns {boolean}
         */
        isMobile: function(str) {
            return /^1([3456789])\d{9}$/.test(str);
        },

        /**
         * 是否手机号码
         * @param phone
         * @returns {boolean}
         */
        isPhone: function (phone) {
            return this.isMobile(phone);
        },

        /**
         * 根据两点间的经纬度计算距离
         * @param lng1
         * @param lat1
         * @param lng2
         * @param lat2
         * @returns {string|*}
         */
        getDistance: function (lng1, lat1, lng2, lat2) {
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
         * @param myObj
         * @returns {*}
         */
        cloneData(myObj) {
            if(typeof(myObj) !== 'object') return myObj;
            if(myObj === null) return myObj;
            //
            if (typeof myObj.length === 'number') {
                let [ ...myNewObj ] = myObj;
                return myNewObj;
            }else{
                let { ...myNewObj } = myObj;
                return myNewObj;
            }
        },

        /**
         * 克隆对象
         * @param myObj
         * @returns {*}
         */
        cloneJSON(myObj) {
            if(typeof(myObj) !== 'object') return myObj;
            if(myObj === null) return myObj;
            //
            return $A.jsonParse($A.jsonStringify(myObj))
        },

        /**
         * 将一个 JSON 字符串转换为对象（已try）
         * @param str
         * @param defaultVal
         * @returns {*}
         */
        jsonParse(str, defaultVal) {
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
        jsonStringify(json, defaultVal) {
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
                    console.log(width, height);
                    if (typeof callback === 'function') callback();
                }
            }, 250);
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
         * 获取对象
         * @param obj
         * @param keys
         * @returns {string|*}
         */
        getObject(obj, keys) {
            let object = obj;
            if (this.count(obj) === 0 || this.count(keys) === 0) {
                return "";
            }
            let arr = keys.replace(/,/g, "|").replace(/\./g, "|").split("|");
            $A.each(arr, (index, key) => {
                object = typeof object[key] === "undefined" ? "" : object[key];
            });
            return object;
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
                    obj+= "";
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
            }catch (e) {
                return 0;
            }
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
            let params = this.urlParameterAll();
            return typeof key === "undefined" ? params : params[key];
        },

        urlParameterAll() {
            let search = window.location.search || "";
            let arr = [];
            if (this.strExists(search, "?")) {
                arr = this.getMiddle(search, "?").split("&");
            }
            let params = {};
            for (let i = 0; i < arr.length; i++) {
                let data = arr[i].split("=");
                if (data.length === 2) {
                    params[data[0]] = data[1];
                }
            }
            return params;
        },

        /**
         * 删除地址中的参数
         * @param url
         * @param parameter
         * @returns {string|*}
         */
        removeURLParameter(url, parameter) {
            if (parameter instanceof Array) {
                parameter.forEach((key) => {
                    url = $A.removeURLParameter(url, key)
                });
                return url;
            }
            var urlparts = url.split('?');
            if (urlparts.length >= 2) {
                //参数名前缀
                var prefix = encodeURIComponent(parameter) + '=';
                var pars = urlparts[1].split(/[&;]/g);

                //循环查找匹配参数
                for (var i = pars.length; i-- > 0;) {
                    if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                        //存在则删除
                        pars.splice(i, 1);
                    }
                }

                return urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
            }
            return url;
        },

        /**
         * 连接加上参数
         * @param url
         * @param params
         * @returns {*}
         */
        urlAddParams(url, params) {
            if (typeof params === "object" && params !== null) {
                url+= "";
                url+= url.indexOf("?") === -1 ? '?' : '';
                for (var key in params) {
                    if (!params.hasOwnProperty(key)) {
                        continue;
                    }
                    url+= '&' + key + '=' + params[key];
                }
            }
            return url.replace("?&", "?");
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
        insert2Input (object, content) {
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
         * iOS上虚拟键盘引起的触控错位
         */
        iOSKeyboardFixer() {
            if (!this.isIos()) {
                return;
            }
            document.body.scrollTop = document.body.scrollTop + 1;
            document.body.scrollTop = document.body.scrollTop - 1;
        },

        autoDevwid(width) {
            let _width = width || 640;
            new function () {
                let _self = this;
                _self.width = _width;           //设置默认最大宽度
                _self.fontSize = 30;            //默认字体大小
                _self.widthProportion = function () {
                    let p = (document.body && document.body.clientWidth || document.getElementsByTagName("html")[0].offsetWidth) / _self.width;
                    return p > 1 ? 1 : p < 0.38 ? 0.38 : p;
                };
                _self.changePage = function () {
                    document.getElementsByTagName("html")[0].setAttribute("style", "font-size:" + _self.widthProportion() * _self.fontSize + "px !important");
                };
                _self.changePage();
                window.addEventListener('resize', function () {
                    _self.changePage();
                }, false);
            };
            //
            let scale = $A(window).width() / _width;
            $A(".__auto").each(function () {
                if ($A(this).attr("data-original") !== "1") {
                    $A(this).attr("data-original-top", parseInt($A(this).css("top")));
                    $A(this).attr("data-original-right", parseInt($A(this).css("right")));
                    $A(this).attr("data-original-bottom", parseInt($A(this).css("bottom")));
                    $A(this).attr("data-original-left", parseInt($A(this).css("left")));
                    $A(this).attr("data-original-width", parseInt($A(this).css("width")));
                    $A(this).attr("data-original-height", parseInt($A(this).css("height")));
                    $A(this).attr("data-original-line-height", parseInt($A(this).css("line-height")));
                    $A(this).attr("data-original", "1");
                }
                let _t = parseInt($A(this).attr("data-original-top"));
                let _r = parseInt($A(this).attr("data-original-right"));
                let _b = parseInt($A(this).attr("data-original-bottom"));
                let _l = parseInt($A(this).attr("data-original-left"));
                let _w = parseInt($A(this).attr("data-original-width"));
                let _h = parseInt($A(this).attr("data-original-height"));
                let _lh = parseInt($A(this).attr("data-original-line-height"));
                //
                let _css = {};
                if (_t > 0) _css['top'] = _t * scale;
                if (_r > 0) _css['right'] = _r * scale;
                if (_b > 0) _css['bottom'] = _b * scale;
                if (_l > 0) _css['left'] = _l * scale;
                if (_w > 0) _css['width'] = _w * scale;
                if (_h > 0) _css['height'] = _h * scale;
                if (_lh > 0) _css['line-height'] = (_lh * scale) + 'px';
                $A(this).css(_css);
            });
            return scale;
        },

        /**
         * 动态加载js文件
         * @param url
         * @param callback
         */
        loadScript(url, callback) {
            url = $A.originUrl(url);
            if (this.rightExists(url, '.css')) {
                this.loadCss(url, callback)
                return;
            }
            if (this.__loadScript[url] === true) {
                typeof callback === "function" && callback();
                return;
            }
            let script = document.createElement("script");
            script.type = "text/javascript";
            if (script.readyState) {
                script.onreadystatechange = () => {
                    if (script.readyState === "loaded" || script.readyState === "complete") {
                        script.onreadystatechange = null;
                        this.__loadScript[url] = true;
                        typeof callback === "function" && callback();
                    }
                };
            } else {
                script.onload = () => {
                    this.__loadScript[url] = true;
                    typeof callback === "function" && callback();
                };
            }
            if (this.rightExists(url, '.js')) {
                script.src = url + "?hash=" + window.systemInformation.version;
            } else {
                script.src = url;
            }
            document.body.appendChild(script);
        },
        loadScriptS(urls, callback) {
            let i = 0;
            let recursiveCallback = () => {
                if (++i < urls.length) {
                    this.loadScript(urls[i], recursiveCallback)
                } else {
                    typeof callback === "function" && callback();
                }
            }
            this.loadScript(urls[0], recursiveCallback);
        },
        __loadScript: {},

        /**
         * 动态加载css
         * @param url
         * @param callback
         */
        loadCss(url, callback) {
            url = $A.originUrl(url);
            if (this.rightExists(url, '.js')) {
                this.loadScript(url, callback)
                return;
            }
            if (this.__loadCss[url] === true) {
                typeof callback === "function" && callback();
                return;
            }
            let script = document.createElement('link');
            if (script.readyState) {
                script.onreadystatechange = () => {
                    if (script.readyState == 'loaded' || script.readyState == 'complete') {
                        script.onreadystatechange = null;
                        this.__loadCss[url] = true;
                        typeof callback === "function" && callback();

                    }
                };
            } else {
                script.onload = () => {
                    this.__loadCss[url] = true;
                    typeof callback === "function" && callback();

                };
            }
            script.rel = 'stylesheet';
            if (this.rightExists(url, '.css')) {
                script.href = url + "?hash=" + window.systemInformation.version;
            } else {
                script.href = url;
            }
            document.getElementsByTagName('head').item(0).appendChild(script);
        },
        loadCssS(urls, callback) {
            let i = 0;
            let recursiveCallback = () => {
                if (++i < urls.length) {
                    this.loadCss(urls[i], recursiveCallback)
                } else {
                    typeof callback === "function" && callback();
                }
            }
            this.loadCss(urls[0], recursiveCallback);
        },
        __loadCss: {},
    });

    /**
     * =============================================================================
     * ****************************   ihttp   ****************************
     * =============================================================================
     */
    $.extend({

        serializeObject (obj, parents) {
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
            let callbacks = ['beforeSend', 'error', 'complete', 'success', 'statusCode'];


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

                let callbackName = 'f7jsonp_' + Date.now() + ($._jsonpRequests++);
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
            let xhr = new XMLHttpRequest();

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
     * *****************************   ajaxc   ****************************
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
            //
            params.before();
            $A.ihttp({
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
                    params.complete();
                    params.success(data, status, xhr);
                    params.after(true);
                },
                error: function (xhr, status) {
                    params.complete();
                    params.error(xhr, status);
                    params.after(false);
                }
            });
        }
    });


    /**
     * =============================================================================
     * *****************************   manage assist   ****************************
     * =============================================================================
     */
    $.extend({

        /**
         *  对象中有Date格式的转成指定格式
         * @param params
         * @param format  默认格式：Y-m-d H:i:s
         * @returns {*}
         */
        date2string(params, format) {
            if (params === null) {
                return params;
            }
            if (typeof format === "undefined") {
                format = "Y-m-d H:i:s";
            }
            if (params instanceof Date) {
                params = $A.formatDate(format, params);
            } else if ($A.isJson(params)) {
                for (let key in params) {
                    if (!params.hasOwnProperty(key)) continue;
                    params[key] = $A.date2string(params[key], format);
                }
            } else if ($A.isArray(params)) {
                params.forEach((val, index) => {
                    params[index] = $A.date2string(val, format);
                });
            }
            return params;
        },

        /**
         * 获取一些指定时间
         * @param str
         * @param retInt
         * @returns {*|string}
         */
        getData(str, retInt = false) {
            let now = new Date();                   //当前日期
            let nowDayOfWeek = now.getDay();        //今天本周的第几天
            let nowDay = now.getDate();             //当前日
            let nowMonth = now.getMonth();          //当前月
            let nowYear = now.getYear();            //当前年
            nowYear += (nowYear < 2000) ? 1900 : 0;
            let lastMonthDate = new Date();         //上月日期
            lastMonthDate.setDate(1);
            lastMonthDate.setMonth(lastMonthDate.getMonth()-1);
            let lastMonth = lastMonthDate.getMonth();
            let getQuarterStartMonth = () => {
                let quarterStartMonth = 0;
                if(nowMonth < 3) {
                    quarterStartMonth = 0;
                }
                if (2 < nowMonth && nowMonth < 6) {
                    quarterStartMonth = 3;
                }
                if (5 < nowMonth && nowMonth < 9) {
                    quarterStartMonth = 6;
                }
                if (nowMonth > 8) {
                    quarterStartMonth = 9;
                }
                return quarterStartMonth;
            };
            let getMonthDays = (myMonth) => {
                let monthStartDate = new Date(nowYear, myMonth, 1);
                let monthEndDate = new Date(nowYear, myMonth + 1, 1);
                return (monthEndDate - monthStartDate)/(1000 * 60 * 60 * 24);
            };
            //
            let time = now.getTime();
            switch (str) {
                case '今天':
                    time = now;
                    break;
                case '昨天':
                    time = now - 86400000;
                    break;
                case '前天':
                    time = now - 86400000 * 2;
                    break;
                case '本周':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek);
                    break;
                case '本周结束':
                    time = new Date(nowYear, nowMonth, nowDay + (6 - nowDayOfWeek));
                    break;
                case '上周':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 7);
                    break;
                case '上周结束':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 1);
                    break;
                case '本周2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek + 1);
                    break;
                case '本周结束2':
                    time = new Date(nowYear, nowMonth, nowDay + (6 - nowDayOfWeek) + 1);
                    break;
                case '上周2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 7 + 1);
                    break;
                case '上周结束2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 1 + 1);
                    break;
                case '本月':
                    time = new Date(nowYear, nowMonth, 1);
                    break;
                case '本月结束':
                    time = new Date(nowYear, nowMonth, getMonthDays(nowMonth));
                    break;
                case '上个月':
                    time = new Date(nowYear, lastMonth, 1);
                    break;
                case '上个月结束':
                    time = new Date(nowYear, lastMonth, getMonthDays(lastMonth));
                    break;
                case '本季度':
                    time = new Date(nowYear, getQuarterStartMonth(), 1);
                    break;
                case '本季度结束':
                    let quarterEndMonth = getQuarterStartMonth() + 2;
                    time = new Date(nowYear, quarterEndMonth, getMonthDays(quarterEndMonth));
                    break;
            }
            if (retInt === true) {
                return time;
            }
            return $A.formatDate("Y-m-d", parseInt(time / 1000))
        },

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
         * 搜索高亮
         * @param string
         * @param key
         * @returns {string|*}
         */
        sreachHighlight(string, key) {
            if (!string || string == '') {
                return '';
            }
            if (!key || key == '') {
                return $A.html2Escape(string);
            }
            string = $A.html2Escape(string.replace(new RegExp(key, "g"), "[highlight]" + key + "[/highlight]"));
            string = string.replace(/\[highlight\]/g, '<span class="sreach-highlight">');
            string = string.replace(/\[\/highlight\]/g, '</span>');
            return string;
        },
    });

    window.$A = $;
})(window, require('jquery'));
