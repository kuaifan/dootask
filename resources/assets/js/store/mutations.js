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
        this.commit('getDialogMsgUnread');
        this.commit('getProjectList');
        this.commit('wsConnection');
    },

    /**
     * 更新会员在线
     * @param state
     * @param info {userid,online}
     */
    setUserOnlineStatus(state, info) {
        const {userid, online} = info;
        if (state.userOnline[userid] !== online) {
            state.userOnline = Object.assign({}, state.userOnline, {[userid]: online});
        }
    },

    /**
     * 获取项目列表
     * @param state
     * @param afterCallback
     */
    getProjectList(state, afterCallback) {
        if (state.userId === 0) {
            state.projectList = [];
            typeof afterCallback === "function" && afterCallback();
            return;
        }
        $A.apiAjax({
            url: 'project/lists',
            after: () => {
                typeof afterCallback === "function" && afterCallback();
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
                    state.method.setStorage("cacheProject", state.cacheProject);
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
        if (state.cacheUserBasic["::load"] === true) {
            setTimeout(() => {
                this.commit('getUserBasic', params);
            }, 20);
            return;
        }
        state.cacheUserBasic["::load"] = true;
        $A.apiAjax({
            url: 'users/basic',
            data: {
                userid: array
            },
            complete: () => {
                state.cacheUserBasic["::load"] = false;
                typeof complete === "function" && complete()
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    data.forEach((item) => {
                        state.cacheUserBasic[item.userid] = {
                            time,
                            data: item
                        };
                        state.method.setStorage("cacheUserBasic", state.cacheUserBasic);
                        this.commit('setUserOnlineStatus', item);
                        typeof success === "function" && success(item, true)
                    });
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 获取对话列表
     * @param state
     * @param afterCallback
     */
    getDialogList(state, afterCallback) {
        $A.apiAjax({
            url: 'dialog/lists',
            after: () => {
                typeof afterCallback === "function" && afterCallback();
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.dialogList = data.data;
                }
            }
        });
    },

    /**
     * 获取单个对话
     * @param state
     * @param dialog_id
     */
    getDialogOne(state, dialog_id) {
        $A.apiAjax({
            url: 'dialog/one',
            data: {
                dialog_id,
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    if (state.dialogId === data.id) data.unread = 0;
                    let index = state.dialogList.findIndex(({id}) => id == data.id);
                    if (index > -1) {
                        state.dialogList.splice(index, 1, data);
                    } else {
                        state.dialogList.unshift(data)
                    }
                }
            }
        });
    },

    /**
     * 打开个人对话
     * @param state
     * @param userid
     */
    openDialogUser(state, userid) {
        $A.apiAjax({
            url: 'dialog/open/user',
            data: {
                userid,
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    let index = state.dialogList.findIndex(({id}) => id == data.id);
                    if (index > -1) {
                        state.dialogList.splice(index, 1, data);
                    } else {
                        state.dialogList.unshift(data)
                    }
                    this.commit('getDialogMsgList', data.id);
                } else {
                    $A.modalError(msg);
                }
            }
        });
    },

    /**
     * 获取对话消息
     * @param state
     * @param dialog_id
     */
    getDialogMsgList(state, dialog_id) {
        if (state.method.runNum(dialog_id) === 0) {
            return;
        }
        if (state.dialogId === dialog_id) {
            return;
        }
        //
        state.dialogMsgList = [];
        if (state.method.isJson(state.cacheDialog[dialog_id])) {
            setTimeout(() => {
                let length = state.cacheDialog[dialog_id].data.length;
                if (length > 50) {
                    state.cacheDialog[dialog_id].data.splice(0, length - 50);
                }
                state.dialogDetail = state.cacheDialog[dialog_id].dialog
                state.dialogMsgList = state.cacheDialog[dialog_id].data
            });
        }
        state.dialogId = dialog_id;
        //
        let dialog = state.dialogList.find(({id}) => id == dialog_id);
        if (dialog && dialog.unread > 0) {
            state.dialogMsgUnread-= dialog.unread;
            dialog.unread = 0;
        }
        //
        if (state.cacheDialog[dialog_id + "::load"]) {
            return;
        }
        state.cacheDialog[dialog_id + "::load"] = true;
        //
        state.dialogMsgLoad++;
        $A.apiAjax({
            url: 'dialog/msg/lists',
            data: {
                dialog_id: dialog_id,
            },
            complete: () => {
                state.dialogMsgLoad--;
                state.cacheDialog[dialog_id + "::load"] = false;
            },
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    state.cacheDialog[dialog_id] = {
                        dialog: data.dialog,
                        data: data.data.reverse(),
                    };
                    state.method.setStorage("cacheDialog", state.cacheDialog);
                    if (state.dialogId === dialog_id) {
                        state.dialogDetail = state.cacheDialog[dialog_id].dialog;
                        state.cacheDialog[dialog_id].data.forEach((item) => {
                            let index = state.dialogMsgList.findIndex(({id}) => id === item.id);
                            if (index === -1) {
                                state.dialogMsgList.push(item);
                            } else {
                                state.dialogMsgList.splice(index, 1, item);
                            }
                        })
                    }
                }
            }
        });
    },

    /**
     * 获取未读信息
     * @param state
     */
    getDialogMsgUnread(state) {
        const unread = state.dialogMsgUnread;
        $A.apiAjax({
            url: 'dialog/msg/unread',
            success: ({ret, data, msg}) => {
                if (ret === 1) {
                    if (unread === state.dialogMsgUnread) {
                        state.dialogMsgUnread = data.unread;
                    } else {
                        setTimeout(() => {
                            this.commit('getDialogMsgUnread');
                        }, 200);
                    }
                }
            }
        });
    },

    /**
     * 根据消息ID 删除 或 替换 对话数据
     * @param state
     * @param params {id, data}
     */
    spliceDialogMsg(state, params) {
        let {id, data} = params;
        if (!id) {
            return;
        }
        if (state.method.isJson(data)) {
            if (data.id && state.dialogMsgList.find(m => m.id == data.id)) {
                data = null;
            }
        }
        let index = state.dialogMsgList.findIndex(m => m.id == id);
        if (index > -1) {
            if (data) {
                state.dialogMsgList.splice(index, 1, state.method.cloneJSON(data));
                // 是最后一条消息时更新对话 last_msg
                if (state.dialogMsgList.length - 1 == index) {
                    const dialog = state.dialogList.find(({id}) => id == data.dialog_id);
                    if (dialog) dialog.last_msg = data;
                }
            } else {
                state.dialogMsgList.splice(index, 1);
            }
        }
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
                    state.method.setStorage("userWsFd", msgDetail.data.fd)
                    break

                case "receipt":
                    typeof state.wsCall[msgId] === "function" && state.wsCall[msgId](msgDetail.body, true);
                    delete state.wsCall[msgId];
                    break

                case "line":
                    this.commit('setUserOnlineStatus', msgDetail.data);
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
                    });
                    if (type === "dialog") {
                        // 更新消息
                        (function (msg) {
                            const {data} = msg;
                            const {dialog_id} = data;
                            if (dialog_id === state.dialogId) {
                                let index = state.dialogMsgList.findIndex(({id}) => id === data.id);
                                if (index === -1) {
                                    if (state.dialogMsgList.length >= 200) {
                                        state.dialogMsgList.splice(0, 1);
                                    }
                                    state.dialogMsgList.push(data);
                                } else {
                                    state.dialogMsgList.splice(index, 1, data);
                                }
                            }
                        })(msgDetail);
                        // 更新对话
                        (function (msg, that) {
                            const {mode, data} = msg;
                            const {dialog_id} = data;
                            // 更新最后消息
                            let dialog = state.dialogList.find(({id}) => id == dialog_id);
                            if (dialog) {
                                dialog.last_msg = data;
                            } else {
                                that.commit('getDialogOne', dialog_id);
                            }
                            if (mode === "add") {
                                if (dialog) {
                                    // 新增未读数
                                    if (state.dialogId !== dialog_id) dialog.unread++;
                                    // 移动到首位
                                    const index = state.dialogList.findIndex(({id}) => id == dialog_id);
                                    if (index > -1) {
                                        const tmp = state.dialogList[index];
                                        state.dialogList.splice(index, 1);
                                        state.dialogList.unshift(tmp);
                                    }
                                }
                                // 新增总未读数
                                if (state.dialogId !== dialog_id) state.dialogMsgUnread++;
                            }
                        })(msgDetail, this);
                    }
                    break
            }
        }
    },

    /**
     * 发送 websocket 消息
     * @param state
     * @param params {type, data, callback, msgId}
     */
    wsSend(state, params) {
        if (!state.method.isJson(params)) {
            return;
        }
        const {type, data, callback} = params;
        let msgId = params.msgId;
        if (!state.ws) {
            typeof callback === "function" && callback(null, false)
            return;
        }
        if (typeof callback === "function") {
            msgId = state.method.randomString(16)
            state.wsCall[msgId] = callback;
        }
        try {
            state.ws.send(JSON.stringify({
                type,
                msgId,
                data
            }));
        } catch (e) {
            typeof callback === "function" && callback(null, false)
        }
    },

    /**
     * 发送已阅消息
     * @param state
     * @param msgId
     */
    wsMsgRead(state, msgId) {
        state.wsReadWaitList.push(msgId);
        clearTimeout(state.wsReadTimeout);
        state.wsReadTimeout = setTimeout(() => {
            this.commit('wsSend', {
                type: 'readMsg',
                data: {
                    id: $A.cloneJSON(state.wsReadWaitList)
                }
            });
            state.wsReadWaitList = [];
        }, 10);
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
    },
}
