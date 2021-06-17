export default {
    /**
     * 访问接口
     * @param state
     * @param dispatch
     * @param params // {url,data,method,timeout,header,spinner,websocket, before,complete,success,error,after}
     * @returns {Promise<unknown>}
     */
    call({state, dispatch}, params) {
        if (!state.method.isJson(params)) params = {url: params}
        if (!state.method.isJson(params.header)) params.header = {}
        params.url = state.method.apiUrl(params.url);
        params.data = state.method.date2string(params.data);
        params.header['Content-Type'] = 'application/json';
        params.header['language'] = $A.getLanguage();
        params.header['token'] = state.userToken;
        params.header['fd'] = state.method.getStorageString("userWsFd");
        //
        return new Promise(function (resolve, reject) {
            if (params.spinner === true) {
                const spinner = document.getElementById("common-spinner");
                if (spinner) {
                    const beforeCall = params.before;
                    params.before = () => {
                        state.ajaxLoadNum++;
                        spinner.style.display = "block"
                        typeof beforeCall == "function" && beforeCall();
                    };
                    //
                    const completeCall = params.complete;
                    params.complete = () => {
                        state.ajaxLoadNum--;
                        if (state.ajaxLoadNum <= 0) {
                            spinner.style.display = "none"
                        }
                        typeof completeCall == "function" && completeCall();
                    };
                }
            }
            //
            params.success = (result, status, xhr) => {
                if (!state.method.isJson(result)) {
                    console.log(result, status, xhr);
                    reject({data: {}, msg: "Return error"})
                    return;
                }
                const {ret, data, msg} = result;
                if (ret === -1 && params.checkRole !== false) {
                    //身份丢失
                    $A.modalError({
                        content: msg,
                        onOk: () => {
                            dispatch("logout")
                        }
                    });
                    return;
                }
                if (ret === 1) {
                    resolve({data, msg});
                } else {
                    reject({data, msg: msg || "Unknown error"})
                }
            };
            params.error = () => {
                reject({data: {}, msg: "System error"})
            };
            //
            if (params.websocket === true || params.ws === true) {
                const apiWebsocket = state.method.randomString(16);
                const apiTimeout = setTimeout(() => {
                    const WListener = state.ajaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                    if (WListener) {
                        WListener.complete();
                        WListener.error("timeout");
                        WListener.after();
                    }
                    state.ajaxWsListener = state.ajaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                }, params.timeout || 30000);
                state.ajaxWsListener.push({
                    apiWebsocket: apiWebsocket,
                    complete: typeof params.complete === "function" ? params.complete : () => { },
                    success: typeof params.success === "function" ? params.success : () => { },
                    error: typeof params.error === "function" ? params.error : () => { },
                    after: typeof params.after === "function" ? params.after : () => { },
                });
                //
                params.complete = () => { };
                params.success = () => { };
                params.error = () => { };
                params.after = () => { };
                params.header['Api-Websocket'] = apiWebsocket;
                //
                if (state.ajaxWsReady === false) {
                    state.ajaxWsReady = true;
                    dispatch("websocketMsgListener", {
                        name: "apiWebsocket",
                        callback: (msg) => {
                            switch (msg.type) {
                                case 'apiWebsocket':
                                    clearTimeout(apiTimeout);
                                    const apiWebsocket = msg.apiWebsocket;
                                    const apiSuccess = msg.apiSuccess;
                                    const apiResult = msg.data;
                                    const WListener = state.ajaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                                    if (WListener) {
                                        WListener.complete();
                                        if (apiSuccess) {
                                            WListener.success(apiResult);
                                        } else {
                                            WListener.error(apiResult);
                                        }
                                        WListener.after();
                                    }
                                    state.ajaxWsListener = state.ajaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                                    break;
                            }
                        }
                    });
                }
            }
            $A.ajaxc(params);
        })
    },

    /**
     * 切换Boolean变量
     * @param state
     * @param key
     */
    toggleBoolean({state}, key) {
        state[key] = !state[key]
        state.method.setStorage("boolean:" + key, state[key]);
    },

    /**
     * 获取/更新会员信息
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    getUserInfo({dispatch}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'users/info',
            }).then(result => {
                dispatch("saveUserInfo", result.data);
                resolve(result)
            }).catch(result => {
                dispatch("logout");
                reject(result)
            });
        });
    },

    /**
     * 更新会员信息
     * @param state
     * @param dispatch
     * @param info
     * @returns {Promise<unknown>}
     */
    saveUserInfo({state, dispatch}, info) {
        return new Promise(function (resolve) {
            const userInfo = state.method.cloneJSON(info);
            userInfo.userid = state.method.runNum(userInfo.userid);
            userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
            state.userInfo = userInfo;
            state.userId = userInfo.userid;
            state.userToken = userInfo.token;
            state.userIsAdmin = state.method.inArray('admin', userInfo.identity);
            state.method.setStorage("userInfo", state.userInfo);
            dispatch("getProjectList");
            dispatch("getDialogMsgUnread");
            dispatch("websocketConnection");
            resolve()
        });
    },

    /**
     * 更新会员在线
     * @param state
     * @param info {userid,online}
     */
    saveUserOnlineStatus({state}, info) {
        const {userid, online} = info;
        if (state.userOnline[userid] !== online) {
            state.userOnline = Object.assign({}, state.userOnline, {[userid]: online});
        }
    },

    /**
     * 获取用户基本信息
     * @param state
     * @param dispatch
     * @param params {userid, success, complete}
     */
    getUserBasic({state, dispatch}, params) {
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
                dispatch("getUserBasic", params);
            }, 20);
            return;
        }
        state.cacheUserBasic["::load"] = true;
        dispatch("call", {
            url: 'users/basic',
            data: {
                userid: array
            },
        }).then(result => {
            state.cacheUserBasic["::load"] = false;
            typeof complete === "function" && complete()
            result.data.forEach((item) => {
                state.cacheUserBasic[item.userid] = {
                    time,
                    data: item
                };
                state.method.setStorage("cacheUserBasic", state.cacheUserBasic);
                dispatch("saveUserOnlineStatus", item);
                typeof success === "function" && success(item, true)
            });
        }).catch(result => {
            state.cacheUserBasic["::load"] = false;
            typeof complete === "function" && complete()
            $A.modalError(result.msg);
        });
    },

    /**
     * 登出（打开登录页面）
     * @param dispatch
     */
    logout({dispatch}) {
        dispatch("saveUserInfo", {}).then(() => {
            const from = window.location.pathname == '/' ? '' : encodeURIComponent(window.location.href);
            $A.goForward({path: '/login', query: from ? {from: from} : {}}, true);
        });
    },

    /**
     * 保存项目信息
     * @param state
     * @param data
     */
    saveProject({state}, data) {
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
        state.method.setStorage("cacheProjectList", state.cacheProjectList = state.projectList);
    },

    /**
     * 获取项目列表
     * @param state
     * @param dispatch
     */
    getProjectList({state, dispatch}) {
        if (state.userId === 0) {
            state.projectList = [];
            return;
        }
        if (state.cacheProjectList.length > 0) {
            state.projectList = state.cacheProjectList;
        }
        dispatch("call", {
            url: 'project/lists',
        }).then(result => {
            dispatch("saveProject", result.data.data);
        }).catch(result => {
            $A.modalError(result.msg);
        });
    },

    /**
     * 获取项目信息
     * @param state
     * @param dispatch
     * @param project_id
     */
    getProjectOne({state, dispatch}, project_id) {
        if (state.method.runNum(project_id) === 0) {
            return;
        }
        dispatch("call", {
            url: 'project/one',
            data: {
                project_id: project_id,
            },
        }).then(result => {
            dispatch("saveProject", result.data);
        });
    },

    /**
     * 获取项目详情
     * @param state
     * @param dispatch
     * @param project_id
     */
    getProjectDetail({state, dispatch}, project_id) {
        if (state.method.runNum(project_id) === 0) {
            return;
        }
        const project = state.cacheProjectList.find(({id}) => id == project_id);
        if (project) {
            state.projectDetail = Object.assign({project_column: [], project_user: []}, project);
        } else {
            state.projectDetail.id = project_id;
        }
        //
        state.projectLoad++;
        dispatch("call", {
            url: 'project/detail',
            data: {
                project_id: project_id,
            },
        }).then(result => {
            state.projectLoad--;
            dispatch("saveProject", result.data);
        }).catch(result => {
            state.projectLoad--;
            $A.modalError(result.msg);
        });
    },

    /**
     * 删除项目信息
     * @param state
     * @param project_id
     */
    removeProject({state}, project_id) {
        let index = state.projectList.findIndex(({id}) => id == project_id);
        if (index > -1) {
            state.projectList.splice(index, 1);
            state.method.setStorage("cacheProjectList", state.cacheProjectList = state.projectList);
        }
    },

    /**
     * 保存任务信息
     * @param state
     * @param dispatch
     * @param data
     */
    saveTask({state, dispatch}, data) {
        state.projectDetail.project_column.some(({project_task}) => {
            let index = project_task.findIndex(({id}) => id === data.id);
            if (index > -1) {
                project_task.splice(index, 1, Object.assign(project_task[index], data))
                return true;
            }
        });
        if (data.id == state.projectOpenTask.id) {
            state.projectOpenTask = Object.assign({}, state.projectOpenTask, data);
        } else if (data.parent_id == state.projectOpenTask.id && state.projectOpenTask.sub_task) {
            let index = state.projectOpenTask.sub_task.findIndex(({id}) => id === data.id);
            if (index > -1) {
                state.projectOpenTask.sub_task.splice(index, 1, Object.assign(state.projectOpenTask.sub_task[index], data))
            }
        }
        dispatch("saveCalendarTask", data)
    },

    /**
     * 保存任务信息（日历任务）
     * @param state
     * @param dispatch
     * @param data
     */
    saveCalendarTask({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((task) => {
                dispatch("saveCalendarTask", task)
            });
            return;
        }
        let task = {
            id: data.id,
            calendarId: String(data.project_id),
            title: data.name,
            body: data.desc,
            category: 'allday',
            start: new Date(data.start_at).toISOString(),
            end: new Date(data.end_at).toISOString(),
            color: "#515a6e",
            bgColor: data.color || '#E3EAFD',
            borderColor: data.p_color,
            complete_at: data.complete_at,
            priority: '',
            preventClick: true,
            isChecked: false,
        };
        if (data.p_name) {
            task.priority = '<span class="priority" style="background-color:' + data.p_color + '">' + data.p_name + '</span>';
        }
        if (data.overdue) {
            task.title = '[' + $A.L('超期') + '] ' + task.title
            task.color = "#f56c6c"
            task.bgColor = "#fef0f0"
            task.priority+= '<span class="overdue">' + $A.L('超期未完成') + '</span>';
        }
        if (!task.borderColor) {
            task.borderColor = task.bgColor;
        }
        let index = state.calendarTask.findIndex(({id}) => id === data.id);
        if (index > -1) {
            state.calendarTask.splice(index, 1, Object.assign(state.calendarTask[index], task))
        } else {
            state.calendarTask.push(task)
        }
    },

    /**
     * 获取任务列表
     * @param state
     * @param dispatch
     * @param whereData
     * @returns {Promise<unknown>}
     */
    getTaskList({state, dispatch}, whereData) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                reject()
                return;
            }
            dispatch("call", {
                url: 'project/task/lists',
                data: whereData,
            }).then(result => {
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 获取任务信息
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getTaskOne({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/one',
                data: {
                    task_id,
                },
            }).then(result => {
                dispatch("saveTask", result.data);
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 获取任务详细描述
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getTaskContent({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/content',
                data: {
                    task_id,
                },
            }).then(result => {
                const {content} = result.data;
                state.projectTaskContent[task_id] = content;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {content: content || ''});
                }
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 获取任务文件
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getTaskFiles({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/files',
                data: {
                    task_id,
                },
            }).then(result => {
                state.projectTaskFiles[task_id] = result.data;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {files: result.data});
                }
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 获取子任务
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getSubTask({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/sublist',
                data: {
                    task_id,
                },
            }).then(result => {
                state.projectSubTask[task_id] = result.data;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {sub_task: result.data});
                }
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 打开任务详情页
     * @param state
     * @param dispatch
     * @param task_id
     */
    openTask({state, dispatch}, task_id) {
        let data = state.method.isJson(task_id) ? task_id : {id: task_id};
        state.projectDetail.project_column.some(({project_task}) => {
            const task = project_task.find(({id}) => id === data.id);
            if (task) {
                data = Object.assign(data, task);
                return true
            }
        });
        //
        data.content = state.projectTaskContent[data.id] || ""
        data.files = state.projectTaskFiles[data.id] || []
        data.sub_task = state.projectSubTask[data.id] || []
        //
        state.projectOpenTask = Object.assign({}, data, {_show: true});
        dispatch("getTaskOne", data.id);
        dispatch("getTaskContent", data.id);
        dispatch("getTaskFiles", data.id);
        dispatch("getSubTask", data.id);
    },

    /**
     * 添加任务
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    taskAdd({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            const post = state.method.cloneJSON(state.method.date2string(data));
            if (state.method.isArray(post.column_id)) {
                post.column_id = post.column_id.find((val) => val)
            }
            if (state.method.isArray(post.owner)) {
                post.owner = post.owner.find((val) => val)
            }
            //
            dispatch("call", {
                url: 'project/task/add',
                data: post,
                method: 'post',
            }).then(result => {
                const {task, in_top, new_column} = result.data;
                if (state.projectDetail.id == task.project_id) {
                    if (new_column) {
                        state.projectDetail.project_column.push(new_column);
                    }
                    const column = state.projectDetail.project_column.find(({id}) => id === task.column_id);
                    if (column) {
                        if (in_top) {
                            column.project_task.unshift(task);
                        } else {
                            column.project_task.push(task);
                        }
                    }
                }
                dispatch("saveTask", task);
                dispatch("getProjectOne", task.project_id);
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 添加子任务
     * @param state
     * @param dispatch
     * @param data {task_id, name}
     * @returns {Promise<unknown>}
     */
    taskAddSub({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/addsub',
                data: data,
            }).then(result => {
                const {task} = result.data;
                if (state.projectDetail.id == task.project_id) {
                    const column = state.projectDetail.project_column.find(({id}) => id === task.column_id);
                    if (column) {
                        const project_task = column.project_task.find(({id}) => id === task.parent_id)
                        if (project_task) {
                            let index = project_task.sub_task.findIndex(({id}) => id === task.id)
                            if (index === -1) {
                                project_task.sub_task.push(task);
                            }
                        }
                    }
                }
                if (data.task_id == state.projectOpenTask.id) {
                    let index = state.projectOpenTask.sub_task.findIndex(({id}) => id === task.id)
                    if (index === -1) {
                        state.projectOpenTask.sub_task.push(task);
                    }
                }
                dispatch("getTaskOne", task.parent_id);
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 更新任务
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    taskUpdate({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            const post = state.method.cloneJSON(state.method.date2string(data));
            if (state.method.isArray(post.owner)) {
                post.owner = post.owner.find((id) => id)
            }
            dispatch("call", {
                url: 'project/task/update',
                data: post,
                method: 'post',
            }).then(result => {
                if (result.data.parent_id) {
                    dispatch("getTaskOne", result.data.parent_id);
                }
                if (typeof post.complete_at !== "undefined") {
                    dispatch("getProjectOne", result.data.project_id);
                }
                dispatch("saveTask", result.data);
                resolve(result)
            }).catch(result => {
                dispatch("getTaskOne", post.task_id);
                reject(result)
            });
        });
    },

    /**
     * 删除或归档任务
     * @param state
     * @param dispatch
     * @param data {task_id, type}
     * @returns {Promise<unknown>}
     */
    taskArchivedOrRemove({state, dispatch}, data) {
        let {task_id, type} = data;
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/' + type,
                data: {
                    task_id,
                },
            }).then(result => {
                const {data} = result;
                const column = state.projectDetail.project_column.find(({id}) => id === data.column_id);
                if (column) {
                    let index = column.project_task.findIndex(({id}) => id === data.id);
                    if (index > -1) {
                        column.project_task.splice(index, 1);
                    }
                }
                if (data.id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {_show: false});
                } else if (data.parent_id == state.projectOpenTask.id && state.projectOpenTask.sub_task) {
                    let index = state.projectOpenTask.sub_task.findIndex(({id}) => id === data.id);
                    if (index > -1) {
                        state.projectOpenTask.sub_task.splice(index, 1)
                    }
                }
                let index = state.calendarTask.findIndex(({id}) => id === data.id);
                if (index > -1) {
                    state.calendarTask.splice(index, 1)
                }
                dispatch("getProjectOne", data.project_id);
                resolve(result);
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 获取任务优先级预设数据
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    getTaskPriority({state, dispatch}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'system/priority',
            }).then(result => {
                state.taskPriority = result.data;
                resolve(result)
            }).catch(result => {
                reject(result)
            });
        });
    },

    /**
     * 更新会话数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialog({state, dispatch}, data) {
        let splice = false;
        state.dialogList.some(({id, unread}, index) => {
            if (id == data.id) {
                unread !== data.unread && dispatch('getDialogMsgUnread');
                state.dialogList.splice(index, 1, data);
                return splice = true;
            }
        });
        !splice && state.dialogList.unshift(data)
    },

    /**
     * 获取会话列表
     * @param state
     * @param dispatch
     */
    getDialogList({state, dispatch}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'dialog/lists',
            }).then(result => {
                if (state.dialogList.length === 0) {
                    state.dialogList = result.data.data;
                } else {
                    result.data.data.forEach((dialog) => {
                        let index = state.dialogList.findIndex(({id}) => id == dialog.id);
                        if (index > -1) {
                            state.dialogList.splice(index, 1, Object.assign(state.dialogList[index], dialog))
                        } else {
                            state.dialogList.unshift(dialog);
                        }
                    });
                }
                resolve(result);
            }).catch(result => {
                reject(result);
            });
        });
    },

    /**
     * 获取单个会话
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogOne({state, dispatch}, dialog_id) {
        dispatch("call", {
            url: 'dialog/one',
            data: {
                dialog_id,
            },
        }).then(result => {
            dispatch("saveDialog", result.data);
        });
    },

    /**
     * 打开个人会话
     * @param state
     * @param dispatch
     * @param userid
     */
    openDialogUserid({state, dispatch}, userid) {
        return new Promise(function (resolve, reject) {
            if (userid === state.userId) {
                reject();
                return;
            }
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
            }).then(result => {
                state.method.setStorage("messengerDialogId", result.data.id)
                dispatch("getDialogMsgList", result.data.id);
                dispatch("saveDialog", result.data);
                resolve(result);
            }).catch(result => {
                $A.modalError(result.msg);
                reject(result);
            });
        });
    },

    /**
     * 获取会话消息
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMsgList({state, dispatch}, dialog_id) {
        if (state.method.runNum(dialog_id) === 0) {
            return;
        }
        if (state.dialogId == dialog_id) {
            return;
        }
        //
        state.dialogMsgList = [];
        if (state.method.isJson(state.cacheDialogMsg[dialog_id])) {
            let length = state.cacheDialogMsg[dialog_id].data.length;
            if (length > 50) {
                state.cacheDialogMsg[dialog_id].data.splice(0, length - 50);
            }
            state.dialogDetail = state.cacheDialogMsg[dialog_id].dialog
            state.dialogMsgList = state.cacheDialogMsg[dialog_id].data
        }
        state.dialogId = dialog_id;
        //
        if (state.cacheDialogMsg[dialog_id + "::load"]) {
            return;
        }
        state.cacheDialogMsg[dialog_id + "::load"] = true;
        //
        state.dialogMsgLoad++;
        dispatch("call", {
            url: 'dialog/msg/lists',
            data: {
                dialog_id: dialog_id,
            },
        }).then(result => {
            state.dialogMsgLoad--;
            state.cacheDialogMsg[dialog_id + "::load"] = false;
            const dialog = result.data.dialog;
            const reverse = result.data.data.reverse();
            // 更新缓存
            state.cacheDialogMsg[dialog_id] = {
                dialog,
                data: reverse,
            };
            state.method.setStorage("cacheDialogMsg", state.cacheDialogMsg);
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
            dispatch("saveDialog", dialog);
        }).catch(() => {
            state.dialogMsgLoad--;
            state.cacheDialogMsg[dialog_id + "::load"] = false;
        });
    },

    /**
     * 获取未读信息
     * @param state
     * @param dispatch
     */
    getDialogMsgUnread({state, dispatch}) {
        if (state.userId === 0) {
            state.dialogMsgUnread = 0;
            return;
        }
        const unread = state.dialogMsgUnread;
        dispatch("call", {
            url: 'dialog/msg/unread',
        }).then(result => {
            if (unread == state.dialogMsgUnread) {
                state.dialogMsgUnread = result.data.unread;
            } else {
                setTimeout(() => {
                    dispatch('getDialogMsgUnread');
                }, 200);
            }
        });
    },

    /**
     * 根据消息ID 删除 或 替换 会话数据
     * @param state
     * @param params {id, data}
     */
    dialogMsgUpdate({state}, params) {
        let {id, data} = params;
        if (!id) {
            return;
        }
        if (state.method.isJson(data)) {
            state.projectDetail.project_column.some(({project_task}) => {
                const task = project_task.find(({dialog_id}) => dialog_id === data.dialog_id);
                if (task) task.msg_num++;
            });
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
     * 发送已阅消息
     * @param state
     * @param dispatch
     * @param msgData
     */
    dialogMsgRead({state, dispatch}, msgData) {
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
            dispatch("websocketSend", {
                type: 'readMsg',
                data: {
                    id: state.method.cloneJSON(state.wsReadWaitList)
                }
            });
            state.wsReadWaitList = [];
        }, 10);
    },

    /**
     * 初始化 websocket
     * @param state
     * @param dispatch
     */
    websocketConnection({state, dispatch}) {
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
                dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onerror = (e) => {
            console.log("[WS] Error", e);
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                dispatch('websocketConnection');
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
                    dispatch("saveUserOnlineStatus", msgDetail.data);
                    break

                default:
                    msgId && dispatch("websocketSend", {type: 'receipt', msgId});
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
                        // 更新会话
                        (function (msg) {
                            const {mode, data} = msg;
                            const {dialog_id} = data;
                            // 更新消息列表
                            if (dialog_id == state.dialogId) {
                                let index = state.dialogMsgList.findIndex(({id}) => id == data.id);
                                if (index === -1) {
                                    state.dialogMsgList.push(data);
                                } else {
                                    state.dialogMsgList.splice(index, 1, data);
                                }
                            }
                            // 更新最后消息
                            let dialog = state.dialogList.find(({id}) => id == dialog_id);
                            if (dialog) {
                                dialog.last_msg = data;
                            } else {
                                dispatch("getDialogOne", dialog_id);
                            }
                            if (mode === "add") {
                                // 更新对话列表
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
                                // 新增任务消息数量
                                state.projectDetail.project_column.some(({project_task}) => {
                                    const task = project_task.find(({dialog_id}) => dialog_id === data.dialog_id);
                                    if (task) task.msg_num++;
                                });
                                // 新增总未读数
                                if (data.userid !== state.userId) state.dialogMsgUnread++;
                            }
                        })(msgDetail);
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
    websocketSend({state}, params) {
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
     * 监听消息
     * @param state
     * @param params {name, callback}
     */
    websocketMsgListener({state}, params) {
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
    websocketClose({state}) {
        state.ws && state.ws.close();
    },
}
