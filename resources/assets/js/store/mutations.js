export default {
    /**
     * 切换Boolean变量
     * @param state
     * @param key
     */
    toggleBoolean(state, key) {
        state[key] = !state[key]
        state.method.setStorage('boolean:' + key, state[key]);
    },

    /**
     * 获取任务优先级预设数据
     * @param state
     * @param callback
     */
    getTaskPriority(state, callback) {
        $A.apiAjax({
            url: 'system/priority',
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.taskPriority = data;
                    typeof callback === "function" && callback(data);
                }
            },
        });
        return state.userInfo;
    },

    /**
     * 获取/更新会员信息
     * @param state
     * @param callback
     */
    getUserInfo(state, callback) {
        $A.apiAjax({
            url: 'users/info',
            error: () => {
                $A.logout();
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    this.commit('setUserInfo', data);
                    typeof callback === "function" && callback(data);
                }
            },
        });
        return state.userInfo;
    },

    /**
     * 更新会员信息
     * @param state
     * @param info
     */
    setUserInfo(state, info) {
        const userInfo = state.method.cloneJSON(info);
        userInfo.userid = state.method.runNum(userInfo.userid);
        userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
        state.userInfo = userInfo;
        state.userId = userInfo.userid;
        state.userToken = userInfo.token;
        state.userIsAdmin = state.method.inArray('admin', userInfo.identity);
        state.method.setStorage('userInfo', state.userInfo);
        this.commit('getProjectList');
        this.commit('wsConnection');
    },

    /**
     * 获取项目列表
     * @param state
     * @param completeCallback
     */
    getProjectList(state, completeCallback) {
        if (state.userId === 0) {
            state.projectList = [];
            typeof completeCallback === "function" && completeCallback();
            return;
        }
        $A.apiAjax({
            url: 'project/lists',
            complete: () => {
                typeof completeCallback === "function" && completeCallback();
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.projectList = data.data;
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 获取项目信息
     * @param state
     * @param project_id
     */
    getProjectDetail(state, project_id) {
        if (state.method.runNum(project_id) === 0) {
            return;
        }
        if (state.method.isJson(state.cacheProject[project_id])) {
            state.projectDetail = state.cacheProject[project_id];
        }
        state.projectDetail.id = project_id;
        //
        if (state.cacheProject[project_id + "::load"]) {
            return;
        }
        state.cacheProject[project_id + "::load"] = true;
        //
        state.projectLoad++;
        $A.apiAjax({
            url: 'project/detail',
            data: {
                project_id: project_id,
            },
            complete: () => {
                state.projectLoad--;
                state.cacheProject[project_id + "::load"] = false;
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.cacheProject[project_id] = data;
                    if (state.projectDetail.id === project_id) {
                        state.projectDetail = data;
                    }
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 获取用户基本信息
     * @param state
     * @param params {userid, success, complete}
     */
    getUserBasic(state, params) {
        if (!state.method.isJson(params)) {
            return;
        }
        const {userid, success, complete} = params;
        const time = Math.round(new Date().getTime() / 1000);
        const array = [];
        (state.method.isArray(userid) ? userid : [userid]).some((uid) => {
            if (state.cacheUserBasic[uid]) {
                typeof success === "function" && success(state.cacheUserBasic[uid].data, false);
                if (time - state.cacheUserBasic[uid].time <= 10) {
                    return false;
                }
            }
            array.push(uid);
        });
        if (array.length === 0) {
            typeof complete === "function" && complete()
            return;
        }
        //
        if (state.cacheUserBasic["::loading"] === true) {
            setTimeout(() => {
                this.commit('getUserBasic', params);
            }, 20);
            return;
        }
        state.cacheUserBasic["::loading"] = true;
        $A.apiAjax({
            url: 'users/basic',
            data: {
                userid: array
            },
            complete: () => {
                state.cacheUserBasic["::loading"] = false;
                typeof complete === "function" && complete()
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    data.forEach((item) => {
                        state.cacheUserBasic[item.userid] = {
                            time,
                            data: item
                        };
                        typeof success === "function" && success(item, true)
                    });
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 初始化 websocket
     * @param state
     */
    wsConnection(state) {
        clearTimeout(state.wsTimeout);
        if (state.userId === 0) {
            if (state.ws) {
                state.ws.close();
                state.ws = null;
            }
            return;
        }
        let url = window.webSocketConfig.URL;
        if (!url) {
            url = window.location.origin;
            url = url.replace("https://", "wss://");
            url = url.replace("http://", "ws://");
            url += "/ws";
        }
        url += "?action=web&token=" + state.userToken;
        //
        state.ws = new WebSocket(url);
        state.ws.onopen = (e) => {
            console.log("[WS] Open", e)
        };
        state.ws.onclose = (e) => {
            console.log("[WS] Close", e);
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                this.commit('wsConnection');
            }, 3000);
        };
        state.ws.onerror = (e) => {
            console.log("[WS] Error", e);
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                this.commit('wsConnection');
            }, 3000);
        };
        state.ws.onmessage = (e) => {
            console.log("[WS] Message", e);
            const msgDetail = state.method.jsonParse(event.data);
            const {type, msgId} = msgDetail;
            switch (type) {
                case "open":
                    break

                case "receipt":
                    typeof state.wsCall[msgId] === "function" && state.wsCall[msgId](msgDetail.body, true);
                    delete state.wsCall[msgId];
                    break

                default:
                    msgId && this.commit('wsSend', {type: 'receipt', msgId});
                    state.wsMsg = msgDetail;
                    Object.values(state.wsListener).forEach((call) => {
                        if (typeof call === "function") {
                            try {
                                call(msgDetail);
                            } catch (err) {
                                console.log("[WS] Callerr", err);
                            }
                        }
                    })
                    break
            }
        }
    },

    /**
     * 发送 websocket 消息
     * @param state
     * @param params {type, to, data, callback}
     */
    wsSend(state, params) {
        if (!state.method.isJson(params)) {
            return;
        }
        const {type, to, data, callback} = params;
        if (!state.ws) {
            typeof callback === "function" && callback(null, false)
            return;
        }
        if (typeof callback === "function") {
            params.msgId = state.method.randomString(16)
            state.wsCall[params.msgId] = callback;
        }
        try {
            state.ws.send(JSON.stringify({
                type,
                to,
                data
            }));
        } catch (e) {
            typeof callback === "function" && callback(null, false)
        }
    },

    /**
     * 监听消息
     * @param state
     * @param params {name, callback}
     */
    wsMsgListener(state, params) {
        const {name, callback} = params;
        if (typeof callback === "function") {
            state.wsListener[name] = callback;
        } else {
            state.wsListener[name] && delete state.wsListener[name];
        }
    },

    /**
     * 关闭 websocket
     * @param state
     */
    wsClose(state) {
        state.ws && state.ws.close();
    }
}
