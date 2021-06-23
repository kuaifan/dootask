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
     * 切换面板变量
     * @param state
     * @param key
     */
    toggleTablePanel({state}, key) {
        if (state.projectId) {
            let index = state.cacheTablePanel.findIndex(({project_id}) => project_id == state.projectId)
            if (index === -1) {
                state.cacheTablePanel.push({
                    project_id: state.projectId,
                });
                index = state.cacheTablePanel.findIndex(({project_id}) => project_id == state.projectId)
            }
            const cache = state.cacheTablePanel[index];
            state.cacheTablePanel.splice(index, 1, Object.assign(cache, {
                [key]: !cache[key]
            }))
            state.method.setStorage("cacheTablePanel", state.cacheTablePanel);
        }
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
            }).catch(e => {
                console.error(e);
                reject(e)
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
            dispatch("getProjects");
            dispatch("getDialogs");
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
     * 获取用户基础信息
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
        }).catch(e => {
            console.error(e);
            state.cacheUserBasic["::load"] = false;
            typeof complete === "function" && complete()
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

    /** *****************************************************************************************/
    /** ************************************** 项目 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存项目数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveProject({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((project) => {
                dispatch("saveProject", project)
            });
        } else if (state.method.isJson(data)) {
            let index = state.projects.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.projects.splice(index, 1, Object.assign(state.projects[index], data));
            } else {
                state.projects.push(data);
            }
            setTimeout(() => {
                state.method.setStorage("cacheProjects", state.cacheProjects = state.projects);
            })
        }
    },

    /**
     * 忘记项目数据
     * @param state
     * @param project_id
     */
    forgetProject({state}, project_id) {
        let index = state.projects.findIndex(({id}) => id == project_id);
        if (index > -1) {
            state.projects.splice(index, 1);
        }
        if (state.projectId == project_id) {
            const project = state.projects.find(({id}) => id && id != project_id);
            if (project) {
                $A.goForward({path: '/manage/project/' + project.id});
            } else {
                $A.goForward({path: '/manage/dashboard'});
            }
        }
        setTimeout(() => {
            state.method.setStorage("cacheProjects", state.cacheProjects = state.projects);
        })
    },

    /**
     * 获取项目
     * @param state
     * @param dispatch
     */
    getProjects({state, dispatch}) {
        if (state.userId === 0) {
            state.projects = [];
            return;
        }
        if (state.cacheProjects.length > 0) {
            state.projects = state.cacheProjects;
        }
        dispatch("call", {
            url: 'project/lists',
        }).then(result => {
            state.projects = [];
            dispatch("saveProject", result.data.data);
        }).catch(e => {
            console.error(e);
        });
    },

    /**
     * 获取单个项目
     * @param state
     * @param dispatch
     * @param project_id
     * @returns {Promise<unknown>}
     */
    getProjectOne({state, dispatch}, project_id) {
        state.projectLoad++;
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/one',
                data: {
                    project_id,
                },
            }).then(result => {
                state.projectLoad--;
                dispatch("saveProject", result.data);
                resolve(result)
            }).catch(e => {
                console.error(e);
                state.projectLoad--;
                reject(e)
            });
        });
    },

    /**
     * 归档项目
     * @param dispatch
     * @param project_id
     */
    archivedProject({dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/archived',
                data: {
                    project_id,
                },
            }).then(result => {
                dispatch("forgetProject", project_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getProjectOne", project_id);
                reject(e)
            });
        });
    },

    /**
     * 删除项目
     * @param dispatch
     * @param project_id
     */
    removeProject({dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/remove',
                data: {
                    project_id,
                },
            }).then(result => {
                dispatch("forgetProject", project_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getProjectOne", project_id);
                reject(e)
            });
        });
    },

    /**
     * 退出项目
     * @param dispatch
     * @param project_id
     */
    exitProject({dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/exit',
                data: {
                    project_id,
                },
            }).then(result => {
                dispatch("forgetProject", project_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getProjectOne", project_id);
                reject(e)
            });
        });
    },

    /**
     * 获取项目统计
     * @param state
     * @param dispatch
     */
    getProjectStatistics({state, dispatch}) {
        dispatch("call", {
            url: 'project/statistics',
        }).then(({data}) => {
            state.projectStatistics = data;
        });
    },

    /** *****************************************************************************************/
    /** ************************************** 列表 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存列表数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveColumn({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((column) => {
                dispatch("saveColumn", column)
            });
        } else if (state.method.isJson(data)) {
            let index = state.columns.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.columns.splice(index, 1, Object.assign(state.columns[index], data));
            } else {
                state.columns.push(data);
            }
            setTimeout(() => {
                state.method.setStorage("cacheColumns", state.cacheColumns = state.columns);
            })
        }
    },

    /**
     * 忘记列表数据
     * @param state
     * @param dispatch
     * @param column_id
     */
    forgetColumn({state, dispatch}, column_id) {
        let index = state.columns.findIndex(({id}) => id == column_id);
        if (index > -1) {
            dispatch('getProjectOne', state.columns[index].project_id)
            state.columns.splice(index, 1);
        }
        setTimeout(() => {
            state.method.setStorage("cacheColumns", state.cacheColumns = state.columns);
        })
    },

    /**
     * 获取列表
     * @param state
     * @param dispatch
     * @param project_id
     */
    getColumns({state, dispatch}, project_id) {
        if (state.userId === 0) {
            state.columns = [];
            return;
        }
        if (state.cacheColumns.length > 0) {
            state.columns = state.cacheColumns;
        }
        state.projectLoad++;
        dispatch("call", {
            url: 'project/column/lists',
            data: {
                project_id
            }
        }).then(result => {
            state.projectLoad--;
            const ids = result.data.data.map(({id}) => id)
            if (ids.length == 0) {
                return;
            }
            state.columns = state.columns.filter((item) => item.project_id != project_id || ids.includes(item.id));
            dispatch("saveColumn", result.data.data);
        }).catch(e => {
            console.error(e);
            state.projectLoad--;
        });
    },

    /**
     * 删除列表
     * @param dispatch
     * @param column_id
     */
    removeColumn({dispatch}, column_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/column/remove',
                data: {
                    column_id,
                },
            }).then(result => {
                dispatch("forgetColumn", column_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /** *****************************************************************************************/
    /** ************************************** 任务 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存任务数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveTask({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((task) => {
                dispatch("saveTask", task)
            });
        } else if (state.method.isJson(data)) {
            let key = data.parent_id > 0 ? 'taskSubs' : 'tasks';
            let index = state[key].findIndex(({id}) => id == data.id);
            if (index > -1) {
                state[key].splice(index, 1, Object.assign(state[key][index], data));
            } else {
                state[key].push(data);
            }
            //
            if (data.is_subtask) {
                dispatch("getTaskOne", data.parent_id);
            }
            if (data.is_update_complete) {
                dispatch("getProjectOne", data.project_id);
            }
            if (data.is_update_content) {
                dispatch("getTaskContent", data.id);
            }
            //
            setTimeout(() => {
                if (data.parent_id > 0) {
                    state.method.setStorage("cacheTaskSubs", state.cacheTaskSubs = state[key]);
                } else {
                    state.method.setStorage("cacheTasks", state.cacheTasks = state[key]);
                }
            })
        }
    },

    /**
     * 忘记任务数据
     * @param state
     * @param dispatch
     * @param task_id
     */
    forgetTask({state, dispatch}, task_id) {
        let index = state.tasks.findIndex(({id}) => id == task_id);
        if (index > -1) {
            dispatch("getTaskOne", state.tasks[index].parent_id)
            dispatch('getProjectOne', state.tasks[index].project_id)
            state.tasks.splice(index, 1);
        }
        if (state.taskId == task_id) {
            state.taskId = 0;
        }
        setTimeout(() => {
            state.method.setStorage("cacheTasks", state.cacheTasks = state.tasks);
        })
    },

    /**
     * 增加任务消息数量
     * @param state
     * @param dialog_id
     */
    increaseTaskMsgNum({state}, dialog_id) {
        const task = state.tasks.find((task) => task.dialog_id === dialog_id);
        if (task) task.msg_num++;
    },

    /**
     * 获取任务
     * @param state
     * @param dispatch
     * @param data
     */
    getTasks({state, dispatch}, data) {
        if (state.userId === 0) {
            state.tasks = [];
            return;
        }
        if (state.cacheTasks.length > 0) {
            state.tasks = state.cacheTasks;
        }
        if (data.project_id) {
            state.projectLoad++;
        }
        dispatch("call", {
            url: 'project/task/lists',
            data: data
        }).then(result => {
            if (data.project_id) {
                state.projectLoad--;
            }
            const resData = result.data;
            const ids = resData.data.map(({id}) => id)
            if (ids.length == 0) {
                return;
            }
            if (data.project_id) {
                state.tasks = state.tasks.filter((item) => item.project_id != data.project_id || ids.includes(item.id));
            }
            if (data.parent_id) {
                state.taskSubs = state.taskSubs.filter((item) => item.parent_id != data.parent_id || ids.includes(item.id));
            }
            dispatch("saveTask", resData.data);
            //
            if (resData.next_page_url) {
                const nextData = Object.assign(data, {
                    page: resData.current_page + 1,
                });
                if (resData.current_page % 5 === 0) {
                    $A.modalWarning({
                        content: "数据已超过" + resData.to + "条，是否继续加载？",
                        onOk: () => {
                            dispatch("getTasks", nextData)
                        }
                    });
                } else {
                    dispatch("getTasks", nextData)
                }
            }
        }).catch(e => {
            console.error(e);
            if (data.project_id) {
                state.projectLoad--;
            }
        });
    },

    /**
     * 获取单个任务
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getTaskOne({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            if (state.method.runNum(task_id) === 0) {
                return;
            }
            dispatch("call", {
                url: 'project/task/one',
                data: {
                    task_id,
                },
            }).then(result => {
                dispatch("saveTask", result.data);
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e)
            });
        });
    },

    /**
     * 删除任务
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    removeTask({dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/remove',
                data: {
                    task_id: task_id,
                },
            }).then(result => {
                dispatch("forgetTask", task_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getTaskOne", task_id);
                reject(e)
            });
        });
    },

    /**
     * 归档任务
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    archivedTask({dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/archived',
                data: {
                    task_id: task_id,
                },
            }).then(result => {
                dispatch("forgetTask", task_id)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getTaskOne", task_id);
                reject(e)
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
                let index = state.taskContents.findIndex(({id}) => id == result.data.id)
                if (index > -1) {
                    state.taskContents.splice(index, 1, result.data)
                } else {
                    state.taskContents.push(result.data)
                }
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
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
                result.data.forEach((data) => {
                    let index = state.taskFiles.findIndex(({id}) => id == data.id)
                    if (index > -1) {
                        state.taskFiles.splice(index, 1, data)
                    } else {
                        state.taskFiles.push(data)
                    }
                })
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
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
        state.taskId = task_id;
        if (task_id > 0) {
            dispatch("getTaskOne", task_id).then(() => {
                dispatch("getTaskContent", task_id);
                dispatch("getTaskFiles", task_id);
                dispatch("getTasks", {parent_id: task_id});
            }).catch(({msg}) => {
                $A.modalWarning({
                    content: msg,
                    onOk: () => {
                        state.taskId = 0;
                    }
                });
            });
        }
    },

    /**
     * 添加任务
     * @param state
     * @param commit
     * @param data
     * @returns {Promise<unknown>}
     */
    taskAdd({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            const post = state.method.cloneJSON(state.method.date2string(data));
            if (state.method.isArray(post.column_id)) post.column_id = post.column_id.find((val) => val)
            if (state.method.isArray(post.owner)) post.owner = post.owner.find((val) => val)
            //
            dispatch("call", {
                url: 'project/task/add',
                data: post,
                method: 'post',
            }).then(result => {
                dispatch("addTaskSuccess", result.data)
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /**
     * 添加子任务
     * @param dispatch
     * @param data {task_id, name}
     * @returns {Promise<unknown>}
     */
    taskAddSub({dispatch}, data) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/addsub',
                data: data,
            }).then(result => {
                dispatch("addTaskSuccess", result.data)
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /**
     * 添加任务成功
     * @param dispatch
     * @param data
     */
    addTaskSuccess({dispatch}, data) {
        const {new_column, task} = data;
        if (new_column) {
            dispatch("saveColumn", new_column)
        }
        dispatch("saveTask", task)
        if (task.parent_id) {
            dispatch("getTaskOne", task.parent_id);
        } else {
            dispatch("getProjectOne", task.project_id);
        }
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
            if (state.method.isArray(post.owner)) post.owner = post.owner.find((id) => id)
            //
            dispatch("call", {
                url: 'project/task/update',
                data: post,
                method: 'post',
            }).then(result => {
                dispatch("saveTask", result.data)
                resolve(result)
            }).catch(e => {
                console.error(e);
                dispatch("getTaskOne", post.task_id);
                reject(e)
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
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /** *****************************************************************************************/
    /** ************************************** 会话 **********************************************/
    /** *****************************************************************************************/

    /**
     * 更新会话数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialog({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((dialog) => {
                dispatch("saveDialog", dialog)
            });
        } else if (state.method.isJson(data)) {
            let index = state.dialogs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.dialogs.splice(index, 1, Object.assign(state.dialogs[index], data));
            } else {
                state.dialogs.push(data);
            }
            setTimeout(() => {
                state.method.setStorage("cacheDialogs", state.cacheDialogs = state.dialogs);
            })
        }
    },

    /**
     * 更新会话最后消息
     * @param state
     * @param dispatch
     * @param data
     */
    updateDialogLastMsg({state, dispatch}, data) {
        let dialog = state.dialogs.find(({id}) => id == data.dialog_id);
        if (dialog) {
            dispatch("saveDialog", {
                id: data.dialog_id,
                last_msg: data,
                last_at: state.method.formatDate("Y-m-d H:i:s")
            });
        } else {
            dispatch("getDialogOne", data.dialog_id);
        }
    },

    /**
     * 获取会话列表
     * @param state
     * @param dispatch
     */
    getDialogs({state, dispatch}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'dialog/lists',
            }).then(result => {
                dispatch("saveDialog", result.data.data);
                resolve(result);
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /**
     * 获取会话基础信息
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
                return;
            }
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
            }).then(result => {
                state.method.setStorage("messengerDialogId", result.data.id)
                dispatch("saveDialog", result.data);
                resolve(result);
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /**
     * 将会话移动到首位
     * @param state
     * @param dialog_id
     */
    moveDialogTop({state}, dialog_id) {
        const index = state.dialogs.findIndex(({id}) => id == dialog_id);
        if (index > -1) {
            const tmp = state.method.cloneJSON(state.dialogs[index]);
            state.dialogs.splice(index, 1);
            state.dialogs.unshift(tmp);
        }
    },

    /** *****************************************************************************************/
    /** ************************************** 消息 **********************************************/
    /** *****************************************************************************************/

    /**
     * 更新消息数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogMsg({state, dispatch}, data) {
        if (state.method.isArray(data)) {
            data.forEach((msg) => {
                dispatch("saveDialogMsg", msg)
            });
        } else if (state.method.isJson(data)) {
            let index = state.dialogMsgs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.dialogMsgs.splice(index, 1, Object.assign(state.dialogMsgs[index], data));
            } else {
                state.dialogMsgs.push(data);
            }
            setTimeout(() => {
                state.method.setStorage("cacheDialogMsgs", state.cacheDialogMsgs = state.dialogMsgs);
            })
        }
    },

    /**
     * 获取会话消息
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMsgs({state, dispatch}, dialog_id) {
        const dialog = state.dialogs.find(({id}) => id == dialog_id);
        if (!dialog) {
            return;
        }
        if (dialog.loading) {
            return;
        }
        dialog.loading = true;
        dialog.currentPage = 1;
        dialog.hasMorePages = false;
        //
        dispatch("call", {
            url: 'dialog/msg/lists',
            data: {
                dialog_id: dialog_id,
                page: dialog.currentPage
            },
        }).then(result => {
            dialog.loading = false;
            dialog.currentPage = result.data.current_page;
            dialog.hasMorePages = !!result.data.next_page_url;
            const ids = result.data.data.map(({id}) => id)
            if (ids.length == 0) {
                return;
            }
            state.dialogMsgs = state.dialogMsgs.filter((item) => item.dialog_id != dialog_id || ids.includes(item.id));
            dispatch("saveDialog", result.data.dialog);
            dispatch("saveDialogMsg", result.data.data);
        }).catch(e => {
            console.error(e);
            dialog.loading = false;
        });
    },

    /**
     * 获取下一页会话消息
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMsgNextPage({state, dispatch}, dialog_id) {
        return new Promise(function (resolve, reject) {
            const dialog = state.dialogs.find(({id}) => id == dialog_id);
            if (!dialog) {
                return;
            }
            if (!dialog.hasMorePages) {
                return;
            }
            if (dialog.loading) {
                return;
            }
            dialog.loading = true;
            dialog.currentPage++;
            //
            dispatch("call", {
                url: 'dialog/msg/lists',
                data: {
                    dialog_id: dialog_id,
                    page: dialog.currentPage
                },
            }).then(result => {
                dialog.loading = false;
                dialog.currentPage = result.data.current_page;
                dialog.hasMorePages = !!result.data.next_page_url;
                dispatch("saveDialogMsg", result.data.data);
                resolve(result)
            }).catch(e => {
                console.error(e);
                dialog.loading = false;
                reject(e)
            });
        });
    },

    /**
     * 发送已阅消息
     * @param state
     * @param dispatch
     * @param data
     */
    dialogMsgRead({state, dispatch}, data) {
        if (data.userid == state.userId) return;
        if (data.is_read === true) return;
        data.is_read = true;
        //
        let dialog = state.dialogs.find(({id}) => id == data.dialog_id);
        if (dialog && dialog.unread > 0) {
            dialog.unread--
        }
        //
        state.wsReadWaitList.push(data.id);
        clearTimeout(state.wsReadTimeout);
        state.wsReadTimeout = setTimeout(() => {
            dispatch("websocketSend", {
                type: 'readMsg',
                data: {
                    id: state.method.cloneJSON(state.wsReadWaitList)
                }
            });
            state.wsReadWaitList = [];
        }, 20);
    },

    /**
     * 初始化 websocket
     * @param state
     * @param dispatch
     * @param commit
     */
    websocketConnection({state, dispatch, commit}) {
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
                    switch (type) {
                        /**
                         * 聊天会话消息
                         */
                        case "dialog": // 更新会话
                            (function (msg) {
                                const {mode, data} = msg;
                                const {dialog_id} = data;
                                // 更新消息列表
                                state.dialogMsgPush = data;
                                dispatch("saveDialogMsg", data)
                                if (mode === "chat") {
                                    return;
                                }
                                // 更新最后消息
                                dispatch("updateDialogLastMsg", data);
                                if (mode === "add") {
                                    // 更新对话列表
                                    if (dialog) {
                                        // 新增未读数
                                        if (data.userid !== state.userId) dialog.unread++;
                                        // 移动到首位
                                        dispatch("moveDialogTop", dialog_id);
                                    }
                                    // 新增任务消息数量
                                    dispatch("increaseTaskMsgNum", dialog_id);
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 项目消息
                         */
                        case "project":
                            (function (msg) {
                                const {action, data} = msg;
                                switch (action) {
                                    case 'add':
                                    case 'update':
                                        dispatch("saveProject", data)
                                        break;
                                    case 'detail':
                                        dispatch("getProjectOne", data);
                                        break;
                                    case 'archived':
                                    case 'delete':
                                        dispatch("forgetProject", data.id);
                                        break;
                                    case 'sort':
                                        dispatch("getTasks", {project_id: data.id})
                                        break;
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 任务列表消息
                         */
                        case "projectColumn":
                            (function (msg) {
                                const {action, data} = msg;
                                switch (action) {
                                    case 'add':
                                    case 'update':
                                        dispatch("saveColumn", data)
                                        break;
                                    case 'delete':
                                        dispatch("forgetColumn", data.id)
                                        break;
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 任务消息
                         */
                        case "projectTask":
                            (function (msg) {
                                const {action, data} = msg;
                                switch (action) {
                                    case 'add':
                                        dispatch("addTaskSuccess", data)
                                        break;
                                    case 'update':
                                    case 'dialog':
                                        dispatch("saveTask", data)
                                        break;
                                    case 'upload':
                                        dispatch("getTaskFiles", data.id)
                                        break;
                                    case 'archived':
                                    case 'delete':
                                        dispatch("forgetTask", data.id)
                                        break;
                                }
                            })(msgDetail);
                            break;
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
