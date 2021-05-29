/**
 * 页面专用
 */
(function (window) {

    let apiUrl = window.location.origin + '/api/';
    let $ = window.$A;

    $.extend({

        fillUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return window.location.origin + '/' + str;
        },

        webUrl(str) {
            return $A.fillUrl(str || '');
        },

        apiUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return apiUrl + str;
        },

        apiAjax(params) {
            if (typeof params !== 'object') return false;
            if (typeof params.success === 'undefined') params.success = () => { };
            if (typeof params.header !== 'object') params.header = {};
            params.url = this.apiUrl(params.url);
            //
            let beforeCall = params.beforeSend;
            params.beforeSend = () => {
                $A.aAjaxLoadNum++;
                $A(".w-spinner").show();
                //
                if (typeof beforeCall == "function") {
                    beforeCall();
                }
            };
            //
            let completeCall = params.complete;
            params.complete = () => {
                $A.aAjaxLoadNum--;
                if ($A.aAjaxLoadNum <= 0) {
                    $A(".w-spinner").hide();
                }
                //
                if (typeof completeCall == "function") {
                    completeCall();
                }
            };
            //
            let callback = params.success;
            params.success = (data, status, xhr) => {
                if (typeof data === 'object') {
                    if (data.ret === -1 && params.checkRole !== false) {
                        //身份丢失
                        $A.modalError({
                            content: data.msg,
                            onOk: () => {
                                $A.userLogout();
                            }
                        });
                        return;
                    }
                    if (data.ret === -2 && params.role !== false) {
                        //没有权限
                        $A.modalError({
                            content: data.msg ? data.msg : "你没有相关的权限查看或编辑！"
                        });
                    }
                }
                if (typeof callback === "function") {
                    callback(data, status, xhr);
                }
            };
            //
            if (params.websocket === true || params.ws === true) {
                const apiWebsocket = $A.randomString(16);
                const apiTimeout = setTimeout(() => {
                    const WListener = $A.aAjaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                    $A.aAjaxWsListener = $A.aAjaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                    if (WListener) {
                        WListener.complete();
                        WListener.error("timeout");
                        WListener.afterComplete();
                    }
                }, params.timeout || 30000);
                $A.aAjaxWsListener.push({
                    apiWebsocket: apiWebsocket,
                    complete: typeof params.complete === "function" ? params.complete : () => { },
                    afterComplete: typeof params.afterComplete === "function" ? params.afterComplete : () => { },
                    success: typeof params.success === "function" ? params.success : () => { },
                    error: typeof params.error === "function" ? params.error : () => { },
                });
                //
                params.complete = () => { };
                params.afterComplete = () => { };
                params.success = () => { };
                params.error = () => { };
                params.header['Api-Websocket'] = apiWebsocket;
                //
                if ($A.aAjaxWsReady === false) {
                    $A.aAjaxWsReady = true;
                    $A.WSOB.setOnMsgListener("apiWebsocket", [
                        'apiWebsocket',
                    ], (msgDetail) => {
                        switch (msgDetail.messageType) {
                            case 'apiWebsocket':
                                clearTimeout(apiTimeout);
                                const apiWebsocket = msgDetail.apiWebsocket;
                                const apiSuccess = msgDetail.apiSuccess;
                                const apiResult = msgDetail.body;
                                const WListener = $A.aAjaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                                $A.aAjaxWsListener = $A.aAjaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                                if (WListener) {
                                    WListener.complete();
                                    if (apiSuccess) {
                                        WListener.success(apiResult);
                                    } else {
                                        WListener.error(apiResult);
                                    }
                                    WListener.afterComplete();
                                }
                                break;
                        }
                    });
                }
            }
            //
            $A.ajaxc(params);
        },
        aAjaxLoadNum: 0,
        aAjaxWsReady: false,
        aAjaxWsListener: [],

        /**
         * 编辑器参数配置
         * @returns {{modules: {toolbar: *[]}}}
         */
        editorOption() {
            return {
                modules: {
                    toolbar: [
                        ['bold', 'italic'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'size': ['small', false, 'large', 'huge'] }],
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'align': [] }]
                    ]
                }
            };
        },

        /**
         * 获取token
         * @returns {boolean}
         */
        getToken() {
            let token = $A.token();
            return $A.count(token) < 10 ? false : token;
        },

        /**
         * 设置token
         * @param token
         */
        setToken(token) {
            $A.token(token);
        },

        /**
         * 获取会员ID
         * @returns string
         */
        getUserId() {
            if ($A.getToken() === false) {
                return "";
            }
            let userInfo = $A.getUserInfo();
            return $A.runNum(userInfo.userid);
        },

        /**
         * 获取会员账号
         * @returns string
         */
        getUserName() {
            if ($A.getToken() === false) {
                return "";
            }
            let userInfo = $A.getUserInfo();
            return $A.ishave(userInfo.username) ? userInfo.username : '';
        },

        /**
         * 获取会员昵称
         * @param nullName
         * @returns {string|*}
         */
        getNickName(nullName = true) {
            if ($A.getToken() === false) {
                return "";
            }
            let userInfo = $A.getUserInfo();
            return $A.ishave(userInfo.nickname) ? userInfo.nickname : '';
        },

        /**
         * 获取用户信息（并保存）
         * @param callback                  网络请求获取到用户信息回调（监听用户信息发生变化）
         * @returns Object
         */
        getUserInfo(callback) {
            if (typeof callback === 'function' || callback === true) {
                $A.apiAjax({
                    url: 'users/info',
                    error: () => {
                        $A.userLogout();
                    },
                    success: (res) => {
                        if (res.ret === 1) {
                            $A.storage("userInfo", res.data);
                            $A.setToken(res.data.token);
                            $A.triggerUserInfoListener(res.data);
                            $A.updateUserBasic({
                                username: res.data.username,
                                nickname: res.data.nickname,
                                userimg: res.data.userimg,
                            });
                            typeof callback === "function" && callback(res.data, $A.getToken() !== false);
                        }
                    },
                });
            }
            return $A.jsonParse($A.storage("userInfo"));
        },

        /**
         * 根据用户名获取用户基本信息
         * @param params Object{username,callback,listenerName,cacheTime}
         */
        getUserBasic(params) {
            if (typeof params !== 'object' || params === null) return;
            if (typeof params.listenerName === 'undefined') params.listenerName = $A.randomString(16);
            if (typeof params.cacheTime === 'undefined') params.cacheTime = 300;

            if (typeof params.callback !== "function") {
                return;
            }
            if (!params.username) {
                params.callback({}, false);
                return;
            }
            //
            $A.__userBasicFuncUpdate.push({
                listenerName: params.listenerName,
                username: params.username,
                callback: params.callback
            });
            //
            let keyName = '__userBasic:' + params.username.substring(0, 1) + '__';
            let localData = $A.jsonParse(window.localStorage[keyName]);
            if ($A.getObject(localData, params.username + '.success') === true) {
                params.callback(localData[params.username].data, true);
                if (localData[params.username].update + params.cacheTime > Math.round(new Date().getTime() / 1000)) {
                    return;
                }
            }
            //
            $A.__userBasicFuncAjax.push({
                username: params.username,
                callback: params.callback
            });
            //
            $A.__userBasicTimeout++;
            let timeout = $A.__userBasicTimeout;
            setTimeout(() => {
                timeout === $A.__userBasicTimeout && $A.__userBasicEvent();
            }, 100);
        },
        __userBasicEvent() {
            if ($A.__userBasicLoading === true) {
                return;
            }
            $A.__userBasicLoading = true;
            //
            let userArray = [];
            $A.__userBasicFuncAjax.some((item) => {
                userArray.push(item.username);
                if (userArray.length >= 30) {
                    return true;
                }
            });
            //
            $A.apiAjax({
                url: 'users/basic',
                data: {
                    username: $A.jsonStringify(userArray),
                },
                error: () => {
                    userArray.forEach((username) => {
                        let tmpLists = $A.__userBasicFuncAjax.filter((item) => item.username == username);
                        tmpLists.forEach((item) => {
                            if (typeof item.callback === "function") {
                                item.callback({}, false);
                                item.callback = null;
                            }
                        });
                    });
                    //
                    $A.__userBasicLoading = false;
                    $A.__userBasicFuncAjax = $A.__userBasicFuncAjax.filter((item) => typeof item.callback === "function");
                    if ($A.__userBasicFuncAjax.length > 0) {
                        $A.__userBasicEvent();
                    }
                },
                success: (res) => {
                    if (res.ret === 1) {
                        res.data.forEach((data) => {
                            let keyName = '__userBasic:' + data.username.substring(0, 1) + '__';
                            let localData = $A.jsonParse(window.localStorage[keyName]);
                            localData[data.username] = {
                                success: true,
                                update: Math.round(new Date().getTime() / 1000),
                                data: data
                            };
                            window.localStorage[keyName] = $A.jsonStringify(localData);
                        });
                    }
                    userArray.forEach((username) => {
                        let tmpLists = $A.__userBasicFuncAjax.filter((item) => item.username == username);
                        tmpLists.forEach((item) => {
                            if (typeof item.callback === "function") {
                                let info = res.data.filter((data) => data.username == username);
                                if (info.length === 0) {
                                    item.callback({}, false);
                                } else {
                                    item.callback(info[0], true);
                                }
                                item.callback = null;
                            }
                        });
                    });
                    //
                    $A.__userBasicLoading = false;
                    $A.__userBasicFuncAjax = $A.__userBasicFuncAjax.filter((item) => typeof item.callback === "function");
                    if ($A.__userBasicFuncAjax.length > 0) {
                        $A.__userBasicEvent();
                    }
                }
            });
        },
        __userBasicTimeout: 0,
        __userBasicLoading: false,
        __userBasicFuncAjax: [],
        __userBasicFuncUpdate: [],

        /**
         * 主动更新缓存
         * @param params Object{username,....}
         */
        updateUserBasic(params) {
            if (typeof params !== 'object' || params === null) return;

            if (!params.username) {
                return;
            }
            let keyName = '__userBasic:' + params.username.substring(0, 1) + '__';
            let localData = $A.jsonParse(window.localStorage[keyName]);
            if ($A.getObject(localData, params.username + '.success') === true) {
                localData[params.username].data = Object.assign(localData[params.username].data, params);
                window.localStorage[keyName] = $A.jsonStringify(localData);
                //
                let tmpLists = $A.__userBasicFuncUpdate.filter((item) => item.username == params.username);
                tmpLists.forEach((item) => {
                    if (typeof item.callback === "function") {
                        item.callback(localData[params.username].data, true);
                    }
                });
            }
        },

        /**
         * 销毁监听
         * @param listenerName
         */
        destroyUserBasicListener(listenerName) {
            $A.__userBasicFuncUpdate = $A.__userBasicFuncUpdate.filter((item) => item.listenerName != listenerName);
        },

        /**
         * 打开登录页面
         */
        userLogout() {
            $A.token("");
            $A.storage("userInfo", {});
            $A.triggerUserInfoListener({});
            let from = window.location.pathname == '/' ? '' : encodeURIComponent(window.location.href);
            if (typeof $A.app === "object") {
                $A.app.goForward({path: '/login', query: from ? {from: from} : {}}, true);
            } else {
                window.location.replace($A.webUrl('login') + (from ? ('?from=' + from) : ''));
            }
        },

        /**
         * 权限是否通过
         * @param role
         * @returns {boolean}
         */
        identityCheck(role) {
            let userInfo = $A.getUserInfo();
            return $A.identityRaw(role, userInfo.identity);
        },

        /**
         * 权限是否通过
         * @param role
         * @param identity
         * @returns {boolean}
         */
        identityRaw(role, identity) {
            let isRole = false;
            $A.each(identity, (index, res) => {
                if (res === role) {
                    isRole = true;
                }
            });
            return isRole;
        },

        /**
         * 监听用户信息发生变化
         * @param listenerName      监听标识
         * @param callback          监听回调
         */
        setOnUserInfoListener(listenerName, callback) {
            if (typeof listenerName != "string") {
                return;
            }
            if (typeof callback === "function") {
                $A.__userInfoListenerObject[listenerName] = {
                    callback: callback,
                }
            }
        },
        removeUserInfoListener(listenerName) {
            if (typeof listenerName != "string") {
                return;
            }
            if (typeof $A.__userInfoListenerObject[listenerName] != "undefined") {
                delete $A.__userInfoListenerObject[listenerName];
            }
        },
        triggerUserInfoListener(userInfo) {
            let key, item;
            for (key in $A.__userInfoListenerObject) {
                if (!$A.__userInfoListenerObject.hasOwnProperty(key)) continue;
                item = $A.__userInfoListenerObject[key];
                if (typeof item.callback === "function") {
                    item.callback(userInfo, $A.getToken() !== false);
                }
            }
        },
        __userInfoListenerObject: {},
    });

    /**
     * =============================================================================
     * ****************************   websocket assist   ***************************
     * =============================================================================
     */
    $.extend({
        /**
         * @param config {userid, url, token, logCallback}
         */
        WTWS: function (config) {
            this.__instance = null;
            this.__connected = false;
            this.__callbackid = {};
            this.__openNum = 0;
            this.__autoNum = 0;
            this.__lastSend = 0;

            this.__autoLine = function (timeout) {
                var tempNum = this.__autoNum;
                var thas = this;
                setTimeout(function () {
                    if (tempNum === thas.__autoNum) {
                        thas.__autoNum++
                        if (!thas.__config.token) {
                            thas.__log("[WS] No token");
                            thas.__autoLine(timeout);
                        } else {
                            // 发refresh之前判断10秒内没有使用过sendTo的再发送refresh
                            if (thas.__lastSend + 10 < Math.round(new Date().getTime() / 1000)) {
                                thas.sendTo('refresh', function (res) {
                                    thas.__log("[WS] Connection " + (res.status ? 'success' : 'error'));
                                    thas.__autoLine(timeout);
                                });
                            }
                        }
                    }
                }, Math.min(timeout, 30) * 1000);
            }
            this.__log = function (text, event) {
                typeof this.__config.logCallback === "function" && this.__config.logCallback(text, event);
            }
            this.__lExists = function (string, find, lower) {
                string += "";
                find += "";
                if (lower !== true) {
                    string = string.toLowerCase();
                    find = find.toLowerCase();
                }
                return (string.substring(0, find.length) === find);
            }
            this.__rNum = function (str, fixed) {
                var _s = Number(str);
                if (_s + "" === "NaN") {
                    _s = 0;
                }
                if (/^[0-9]*[1-9][0-9]*$/.test(fixed)) {
                    _s = _s.toFixed(fixed);
                    var rs = _s.indexOf('.');
                    if (rs < 0) {
                        _s += ".";
                        for (var i = 0; i < fixed; i++) {
                            _s += "0";
                        }
                    }
                }
                return _s;
            }
            this.__jParse = function (str, defaultVal) {
                if (str === null) {
                    return defaultVal ? defaultVal : {};
                }
                if (typeof str === "object") {
                    return str;
                }
                try {
                    return JSON.parse(str);
                } catch (e) {
                    return defaultVal ? defaultVal : {};
                }
            }
            this.__randString = function (len) {
                len = len || 32;
                var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
                var maxPos = $chars.length;
                var pwd = '';
                for (var i = 0; i < len; i++) {
                    pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
                }
                return pwd;
            }
            this.__urlParams = function(url, params) {
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
            }
            this.__isArr = function (obj){
                return Object.prototype.toString.call(obj)=='[object Array]';
            }

            /**
             * 设置参数
             * @param config
             */
            this.config = function (config) {
                if (typeof config !== "object" || config === null) {
                    config = {};
                }
                config.userid = config.userid || '';
                config.url = config.url || '';
                config.token = config.token || '';
                config.logCallback = config.logCallback || null;
                this.__config = config;
                return this;
            }

            /**
             * 连接
             * @param force
             */
            this.connection = function (force) {
                if (!this.__lExists(this.__config.url, "ws://") && !this.__lExists(this.__config.url, "wss://")) {
                    this.__log("[WS] No connection address");
                    return this;
                }

                if (!this.__config.token) {
                    this.__log("[WS] No connected token");
                    return this;
                }

                if (this.__instance !== null && force !== true) {
                    this.__log("[WS] Connection exists");
                    return this;
                }

                var thas = this;

                // 初始化客户端套接字并建立连接
                this.__instance = new WebSocket(this.__urlParams(this.__config.url, {
                    mode: 'console',
                    token: this.__config.token,
                }));

                // 连接建立时触发
                this.__instance.onopen = function (event) {
                    thas.__log("[WS] Connection opened", event);
                }

                // 接收到服务端推送时执行
                this.__instance.onmessage = function (event) {
                    thas.__log("[WS] Message", event);
                    var msgDetail = thas.__jParse(event.data);
                    if (msgDetail.messageType === 'open') {
                        thas.__log("[WS] Connection connected");
                        msgDetail.openNum = thas.__openNum;
                        msgDetail.config = thas.__config;
                        thas.__openNum++;
                        thas.__connected = true;
                        thas.__autoLine(30);
                    } else if (msgDetail.messageType === 'back') {
                        typeof thas.__callbackid[msgDetail.messageId] === "function" && thas.__callbackid[msgDetail.messageId](msgDetail.body);
                        delete thas.__callbackid[msgDetail.messageId];
                        return;
                    }
                    if (thas.__rNum(msgDetail.contentId) > 0) {
                        thas.sendTo('roger', msgDetail.contentId);
                    }
                    thas.triggerMsgListener(msgDetail);
                };

                // 连接关闭时触发
                this.__instance.onclose = function (event) {
                    thas.__log("[WS] Connection closed", event);
                    thas.__connected = false;
                    thas.__instance = null;
                    thas.__autoLine(3);
                }

                // 连接出错
                this.__instance.onerror = function (event) {
                    thas.__log("[WS] Connection error", event);
                    thas.__connected = false;
                    thas.__instance = null;
                    thas.__autoLine(3);
                }

                return this;
            }

            /**
             * 添加消息监听
             * @param listenerName
             * @param listenerType
             * @param callback
             */
            this.setOnMsgListener = function (listenerName, listenerType, callback) {
                if (typeof listenerName != "string") {
                    return this;
                }
                if (typeof listenerType === "function") {
                    callback = listenerType;
                    listenerType = [];
                }
                if (!this.__isArr(listenerType)) {
                    listenerType = [listenerType];
                }
                if (typeof callback === "function") {
                    window.webSocketConfig.LISTENER[listenerName] = {
                        callback: callback,
                        listenerType: listenerType,
                    }
                }
                return this;
            }
            this.triggerMsgListener = function (msgDetail) {
                var key, item;
                for (key in window.webSocketConfig.LISTENER) {
                    if (!window.webSocketConfig.LISTENER.hasOwnProperty(key)) {
                        continue;
                    }
                    item = window.webSocketConfig.LISTENER[key];
                    if (item.listenerType.length > 0 &&  item.listenerType.indexOf(msgDetail.messageType) === -1) {
                        continue;
                    }
                    if (typeof item.callback === "function") {
                        try {
                            item.callback(msgDetail);
                        } catch (e) { }
                    }
                }
            }

            /**
             * 发送消息
             * @param messageType       会话类型
             * - refresh: 刷新
             * @param target            发送目标
             * @param body              发送内容（对象或数组）
             * @param callback          发送回调
             * @param againNum
             */
            this.sendTo = function (messageType, target, body, callback, againNum = 0) {
                if (typeof target === "object" && typeof body === "undefined") {
                    body = target;
                    target = null;
                }
                if (typeof target === "function") {
                    body = target;
                    target = null;
                }
                if (typeof body === "function") {
                    callback = body;
                    body = null;
                }
                if (body === null || typeof body !== "object") {
                    body = {};
                }
                //
                var thas = this;
                if (this.__instance === null || this.__connected === false) {
                    if (againNum < 10 && messageType != 'team') {
                        setTimeout(function () {
                            thas.sendTo(messageType, target, body, callback, thas.__rNum(againNum) + 1)
                        }, 600);
                        if (againNum === 0) {
                            this.connection();
                        }
                    } else {
                        if (this.__instance === null) {
                            this.__log("[WS] Service not connected");
                            typeof callback === "function" && callback({status: 0, message: '服务未连接'});
                        } else {
                            this.__log("[WS] Failed connection");
                            typeof callback === "function" && callback({status: 0, message: '未连接成功'});
                        }
                    }
                    return this;
                }
                if (['refresh', 'notificationStatus'].indexOf(messageType) === -1) {
                    this.__log("[WS] Wrong message messageType: " + messageType);
                    typeof callback === "function" && callback({status: 0, message: '错误的消息类型: ' + messageType});
                    return this;
                }
                //
                var contentId = 0;
                if (messageType === 'roger') {
                    contentId = target;
                    target = null;
                }
                var messageId = '';
                if (typeof callback === "function") {
                    messageId = this.__randString(16);
                    this.__callbackid[messageId] = callback;
                }
                this.__lastSend = Math.round(new Date().getTime()/1000);
                this.__instance.send(JSON.stringify({
                    messageType: messageType,
                    messageId: messageId,
                    contentId: contentId,
                    userid: this.__config.userid,
                    target: target,
                    body: body,
                    time: this.__lastSend,
                }));
                return this;
            }

            /**
             * 关闭连接
             */
            this.close = function () {
                if (this.__instance === null) {
                    this.__log("[WS] Service not connected");
                    return this;
                }
                if (this.__connected === false) {
                    this.__log("[WS] Failed connection");
                    return this;
                }
                this.__instance.close();
                return this;
            }

            return this.config(config);
        },

        WSOB: {
            instance: null,
            isClose: false,

            /**
             * 初始化
             */
            initialize() {
                let url = window.webSocketConfig.URL;
                if (!url) {
                    url = window.location.origin;
                    url = url.replace("https://", "wss://");
                    url = url.replace("http://", "ws://");
                    url+= "/ws";
                }
                let config = {
                    userid: $A.getUserId(),
                    url: url,
                    token: $A.getToken(),
                };
                if (window.webSocketConfig.DEBUG) {
                    config.logCallback = function (msg) {
                        console.log(msg);
                    };
                }
                if (this.instance === null) {
                    this.instance = new $A.WTWS(config);
                    this.instance.connection()
                } else {
                    this.instance.config(config);
                    if (this.isClose) {
                        this.isClose = false
                        this.instance.connection();
                    }
                }
            },

            /**
             * 主动连接
             */
            connection() {
                this.initialize();
                this.instance.connection();
            },

            /**
             * 监听消息
             * @param listenerName
             * @param listenerType
             * @param callback
             */
            setOnMsgListener(listenerName, listenerType, callback) {
                this.initialize();
                this.instance.setOnMsgListener(listenerName, listenerType, callback);
            },

            /**
             * 发送消息
             * @param messageType
             * @param target
             * @param body
             * @param callback
             */
            sendTo(messageType, target, body, callback) {
                this.initialize();
                this.instance.sendTo(messageType, target, body, callback);
            },

            /**
             * 关闭连接
             */
            close() {
                if (this.instance === null) {
                    return;
                }
                this.isClose = true
                this.instance.config(null).close();
            },
        }
    });

    /**
     * =============================================================================
     * *****************************   iviewui assist   ****************************
     * =============================================================================
     */
    $.extend({
        // 弹窗
        modalConfig(config) {
            if (typeof config === "string") {
                config = {
                    content: config
                };
            }
            config.title = $A.app.$L(config.title || (typeof config.render === 'undefined' ? '温馨提示' : ''));
            config.content = $A.app.$L(config.content || '');
            config.okText = $A.app.$L(config.okText || '确定');
            config.cancelText = $A.app.$L(config.cancelText || '取消');
            return config;
        },

        modalConfirm(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalConfirm(config) }, millisecond);
                return;
            }
            $A.app.$Modal.confirm($A.modalConfig(config));
        },

        modalSuccess(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalSuccess(config) }, millisecond);
                return;
            }
            $A.app.$Modal.success($A.modalConfig(config));
        },

        modalInfo(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalInfo(config) }, millisecond);
                return;
            }
            $A.app.$Modal.info($A.modalConfig(config));
        },

        modalWarning(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalWarning(config) }, millisecond);
                return;
            }
            $A.app.$Modal.warning($A.modalConfig(config));
        },

        modalError(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalError(config) }, millisecond);
                return;
            }
            $A.app.$Modal.error($A.modalConfig(config));
        },

        modalInfoShow(title, data, addConfig) {
            let content = '';
            for (let i in data) {
                let item = data[i];
                content += `<div class="modal-info-show">`;
                content += `    <div class="column">${item.column}：</div>`;
                content += `    <div class="value">${item.value || item.value == '0' ? item.value : '-'}</div>`;
                content += `</div>`;
            }
            let config = {
                title: title,
                content: content,
                okText: $A.app.$L('关闭'),
                closable: true
            };
            if (typeof addConfig == 'object' && addConfig) {
                config = Object.assign(config, addConfig);
            }
            this.app.$Modal.info(config);
        },

        modalAlert(msg) {
            alert($A.app.$L(msg));
        },

        //提示
        messageSuccess(msg) {
            $A.app.$Message.success($A.app.$L(msg));
        },

        messageWarning(msg) {
            $A.app.$Message.warning($A.app.$L(msg));
        },

        messageError(msg) {
            $A.app.$Message.error($A.app.$L(msg));
        },

        //通知
        noticeConfig(config) {
            if (typeof config === "string") {
                config = {
                    desc: config
                };
            }
            config.title = $A.app.$L(config.title || (typeof config.render === 'undefined' ? '温馨提示' : ''));
            config.desc = $A.app.$L(config.desc || '');
            return config;
        },

        noticeSuccess(config) {
            $A.app.$Notice.success($A.noticeConfig(config));
        },

        noticeWarning(config) {
            $A.app.$Notice.warning($A.noticeConfig(config));
        },

        noticeError(config) {
            if (typeof config === "string") {
                config = {
                    desc: config,
                    duration: 6
                };
            }
            $A.app.$Notice.error($A.noticeConfig(config));
        },
    });

    window.$A = $;
})(window);
