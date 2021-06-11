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
        this.dispatch("call", {
            url: 'system/priority',
        }).then((data, msg) => {
            state.taskPriority = data;
            typeof callback === "function" && callback(data);
        });
    },

    /**
     * 获取/更新会员信息
     * @param state
     * @param callback
     */
    getUserInfo(state, callback) {
        this.dispatch("call", {
            url: 'users/info',
        }).then((data, msg) => {
            this.commit('setUserInfo', data);
            typeof callback === "function" && callback(data);
        }).catch((data, msg) => {
            this.commit("logout");
        });
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
     */
    getProjectList(state) {
        if (state.userId === 0) {
            state.projectList = [];
            return;
        }
        if (state.cacheProjectList.length > 0) {
            state.projectList = state.cacheProjectList;
        }
        this.dispatch("call", {
            url: 'project/lists',
        }).then((data, msg) => {
            this.commit('saveProjectData', data.data);
        }).catch((data, msg) => {
            $A.modalError(msg);
        });
    },

    /**
     * 获取项目信息
     * @param state
     * @param project_id
     */
    getProjectOne(state, project_id) {
        if (state.method.runNum(project_id) === 0) {
            return;
        }
        this.dispatch("call", {
            url: 'project/one',
            data: {
                project_id: project_id,
            },
        }).then((data, msg) => {
            this.commit('saveProjectData', data);
        });
    },

    /**
     * 获取项目详情
     * @param state
     * @param project_id
     */
    getProjectDetail(state, project_id) {
        if (state.method.runNum(project_id) === 0) {
            return;
        }
        const project = state.cacheProjectList.find(({id}) => id == project_id);
        if (project) {
            state.projectDetail = Object.assign({project_column: [], project_user: []}, project);
        }
        state.projectDetail.id = project_id;
        //
        state.projectLoad++;
        this.dispatch("call", {
            url: 'project/detail',
            data: {
                project_id: project_id,
            },
        }).then((data, msg) => {
            state.projectLoad--;
            this.commit('saveProjectData', data);
        }).catch((data, msg) => {
            state.projectLoad--;
            $A.modalError(msg);
        });
    },

    /**
     * 保存项目信息
     * @param state
     * @param data
     */
    saveProjectData(state, data) {
        if (state.method.isArray(data)) {
            if (state.projectDetail.id) {
                const project = data.find(({id}) => id == state.projectDetail.id);
                if (project) {
                    state.projectDetail = Object.assign({}, state.projectDetail, project)
                }
            }
            state.projectList = data;
        } else if (state.method.isJson(data)) {
            if (data.id == state.projectDetail.id) {
                state.projectDetail = Object.assign({}, state.projectDetail, data)
            }
            let index = state.projectList.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.projectList.splice(index, 1, Object.assign({}, state.projectList[index], data));
            } else {
                state.projectList.unshift(data);
            }
        }
        state.method.setStorage("cacheProjectList", state.projectList);
    },

    /**
     * 删除项目信息
     * @param state
     * @param project_id
     */
    removeProjectData(state, project_id) {
        let index = state.projectList.findIndex(({id}) => id == project_id);
        if (index > -1) {
            state.projectList.splice(index, 1);
            state.method.setStorage("cacheProjectList", state.projectList);
        }
    },

    /**
     * 打开任务详情页
     * @param state
     * @param task
     */
    openTask(state, task) {
        state.projectTask = Object.assign({_show:true}, task);
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
        if (userid === state.userId) {
            typeof success === "function" && success(state.userInfo, true);
            return;
        }
        const time = Math.round(new Date().getTime() / 1000);
        const array = [];
        (state.method.isArray(userid) ? userid : [userid]).some((uid) => {
            if (state.cacheUserBasic[uid]) {
                typeof success === "function" && success(state.cacheUserBasic[uid].data, false);
                if (time - state.cacheUserBasic[uid].time <= 30) {
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
        this.dispatch("call", {
            url: 'users/basic',
            data: {
                userid: array
            },
        }).then((data, msg) => {
            state.cacheUserBasic["::load"] = false;
            typeof complete === "function" && complete()
            data.forEach((item) => {
                state.cacheUserBasic[item.userid] = {
                    time,
                    data: item
                };
                state.method.setStorage("cacheUserBasic", state.cacheUserBasic);
                this.commit('setUserOnlineStatus', item);
                typeof success === "function" && success(item, true)
            });
        }).catch((data, msg) => {
            state.cacheUserBasic["::load"] = false;
            typeof complete === "function" && complete()
            $A.modalError(msg);
        });
    },

    /**
     * 获取会话列表
     * @param state
     * @param afterCallback
     */
    getDialogList(state, afterCallback) {
        this.dispatch("call", {
            url: 'dialog/lists',
        }).then((data, msg) => {
            state.dialogList = data.data;
            typeof afterCallback === "function" && afterCallback();
        }).catch((data, msg) => {
            typeof afterCallback === "function" && afterCallback();
        });
    },

    /**
     * 更新会话数据
     * @param state
     * @param data
     */
    getDialogUpdate(state, data) {
        let splice = false;
        state.dialogList.some(({id, unread}, index) => {
            if (id == data.id) {
                unread !== data.unread && this.commit('getDialogMsgUnread');
                state.dialogList.splice(index, 1, data);
                return splice = true;
            }
        });
        !splice && state.dialogList.unshift(data)
    },

    /**
     * 获取单个会话
     * @param state
     * @param dialog_id
     */
    getDialogOne(state, dialog_id) {
        this.dispatch("call", {
            url: 'dialog/one',
            data: {
                dialog_id,
            },
        }).then((data, msg) => {
            this.commit('getDialogUpdate', data);
        });
    },

    /**
     * 打开个人会话
     * @param state
     * @param userid
     */
    openDialogUser(state, userid) {
        if (userid === state.userId) {
            return;
        }
        this.dispatch("call", {
            url: 'dialog/open/user',
            data: {
                userid,
            },
        }).then((data, msg) => {
            state.method.setStorage('messengerDialogId', data.id)
            this.commit('getDialogMsgList', data.id);
            this.commit('getDialogUpdate', data);
        }).catch((data, msg) => {
            $A.modalError(msg);
        });
    },

    /**
     * 获取会话消息
     * @param state
     * @param dialog_id
     */
    getDialogMsgList(state, dialog_id) {
        if (state.method.runNum(dialog_id) === 0) {
            return;
        }
        if (state.dialogId == dialog_id) {
            return;
        }
        //
        state.dialogMsgList = [];
        if (state.method.isJson(state.cacheDialogList[dialog_id])) {
            let length = state.cacheDialogList[dialog_id].data.length;
            if (length > 50) {
                state.cacheDialogList[dialog_id].data.splice(0, length - 50);
            }
            state.dialogDetail = state.cacheDialogList[dialog_id].dialog
            state.dialogMsgList = state.cacheDialogList[dialog_id].data
        }
        state.dialogId = dialog_id;
        //
        if (state.cacheDialogList[dialog_id + "::load"]) {
            return;
        }
        state.cacheDialogList[dialog_id + "::load"] = true;
        //
        state.dialogMsgLoad++;
        this.dispatch("call", {
            url: 'dialog/msg/lists',
            data: {
                dialog_id: dialog_id,
            },
        }).then((data, msg) => {
            state.dialogMsgLoad--;
            state.cacheDialogList[dialog_id + "::load"] = false;
            const dialog = data.dialog;
            const reverse = data.data.reverse();
            // 更新缓存
            state.cacheDialogList[dialog_id] = {
                dialog,
                data: reverse,
            };
            state.method.setStorage("cacheDialogList", state.cacheDialogList);
            // 更新当前会话消息
            if (state.dialogId == dialog_id) {
                state.dialogDetail = dialog;
                reverse.forEach((item) => {
                    let index = state.dialogMsgList.findIndex(({id}) => id == item.id);
                    if (index === -1) {
                        state.dialogMsgList.push(item);
                    } else {
                        state.dialogMsgList.splice(index, 1, item);
                    }
                })
            }
            // 更新会话数据
            this.commit('getDialogUpdate', dialog);
        }).catch((data, msg) => {
            state.dialogMsgLoad--;
            state.cacheDialogList[dialog_id + "::load"] = false;
        });
    },

    /**
     * 获取未读信息
     * @param state
     */
    getDialogMsgUnread(state) {
        const unread = state.dialogMsgUnread;
        this.dispatch("call", {
            url: 'dialog/msg/unread',
        }).then((data, msg) => {
            if (unread == state.dialogMsgUnread) {
                state.dialogMsgUnread = data.unread;
            } else {
                setTimeout(() => {
                    this.commit('getDialogMsgUnread');
                }, 200);
            }
        });
    },

    /**
     * 根据消息ID 删除 或 替换 会话数据
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
                // 是最后一条消息时更新会话 last_msg
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
                            if (dialog_id == state.dialogId) {
                                let index = state.dialogMsgList.findIndex(({id}) => id == data.id);
                                if (index === -1) {
                                    state.dialogMsgList.push(data);
                                } else {
                                    state.dialogMsgList.splice(index, 1, data);
                                }
                            }
                        })(msgDetail);
                        // 更新会话
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
                                    if (data.userid !== state.userId) dialog.unread++;
                                    // 移动到首位
                                    const index = state.dialogList.findIndex(({id}) => id == dialog_id);
                                    if (index > -1) {
                                        const tmp = state.dialogList[index];
                                        state.dialogList.splice(index, 1);
                                        state.dialogList.unshift(tmp);
                                    }
                                }
                                // 新增总未读数
                                if (data.userid !== state.userId) state.dialogMsgUnread++;
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
     * @param msgData
     */
    wsMsgRead(state, msgData) {
        if (msgData.userid == state.userId) return;
        if (typeof msgData.r === "undefined") msgData.r = {};
        //
        const {id, dialog_id, r} = msgData;
        if (!r.read_at) {
            r.read_at = state.method.formatDate('Y-m-d H:i:s');
            let dialog = state.dialogList.find(({id}) => id == dialog_id);
            if (dialog && dialog.unread > 0) {
                dialog.unread--
                state.dialogMsgUnread--;
            }
        }
        //
        state.wsReadWaitList.push(id);
        clearTimeout(state.wsReadTimeout);
        state.wsReadTimeout = setTimeout(() => {
            this.commit('wsSend', {
                type: 'readMsg',
                data: {
                    id: state.method.cloneJSON(state.wsReadWaitList)
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

    /**
     * 登出（打开登录页面）
     */
    logout() {
        const from = window.location.pathname == '/' ? '' : encodeURIComponent(window.location.href);
        this.commit('setUserInfo', {});
        $A.goForward({path: '/login', query: from ? {from: from} : {}}, true);
    }
}
