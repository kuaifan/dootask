export default {
    /**
     * @param context
     * @param params // {url,data,method,timeout,header,spinner,websocket, before,complete,success,error,after}
     * @returns {Promise<unknown>}
     */
    call(context, params) {
        const {state, commit} = context;
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
                    resolve(result, status, xhr);
                    return;
                }
                const {ret, data, msg} = result;
                if (ret === -1 && params.checkRole !== false) {
                    //身份丢失
                    $A.modalError({
                        content: msg,
                        onOk: () => {
                            commit("logout")
                        }
                    });
                    return;
                }
                if (ret === 1) {
                    resolve(data, msg);
                } else {
                    reject(data, msg || "Unknown error")
                }
            };
            params.error = () => {
                reject({}, "System error")
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
                    commit("wsMsgListener", {
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
     * 获取任务信息
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    taskOne({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/one',
                data: {
                    task_id,
                },
            }).then((data, msg) => {
                state.projectDetail.project_column.some(({project_task}) => {
                    let index = project_task.findIndex(({id}) => id === task_id);
                    if (index > -1) {
                        project_task.splice(index, 1, Object.assign(project_task[index], data))
                        return true;
                    }
                });
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, data);
                }
                resolve(data, msg)
            }).catch((data, msg) => {
                reject(data, msg)
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
    taskContent({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/content',
                data: {
                    task_id,
                },
            }).then((data, msg) => {
                state.projectTaskContent[task_id] = data;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {content: data || {}});
                }
                resolve(data, msg)
            }).catch((data, msg) => {
                reject(data, msg)
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
    taskFiles({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/files',
                data: {
                    task_id,
                },
            }).then((data, msg) => {
                state.projectTaskFiles[task_id] = data;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {files: data});
                }
                resolve(data, msg)
            }).catch((data, msg) => {
                reject(data, msg)
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
    subTask({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/sublist',
                data: {
                    task_id,
                },
            }).then((data, msg) => {
                state.projectSubTask[task_id] = data;
                if (task_id == state.projectOpenTask.id) {
                    state.projectOpenTask = Object.assign({}, state.projectOpenTask, {sub_task: data});
                }
                resolve(data, msg)
            }).catch((data, msg) => {
                reject(data, msg)
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
        let data = {id: task_id};
        state.projectDetail.project_column.some(({project_task}) => {
            const task = project_task.find(({id}) => id === task_id);
            if (task) {
                data = Object.assign(data, task);
                return true
            }
        });
        //
        data.content = state.projectTaskContent[task_id] || {}
        data.files = state.projectTaskFiles[task_id] || []
        data.subtask = state.projectSubTask[task_id] || []
        state.projectOpenTask = Object.assign({}, data, {_show: true});
        //
        dispatch("taskOne", task_id);
        dispatch("taskContent", task_id);
        dispatch("taskFiles", task_id);
        dispatch("subTask", task_id);
    },
}
