import {Store} from 'le5le-store';

export default {
    /**
     * 访问接口
     * @param state
     * @param dispatch
     * @param params // {url,data,method,timeout,header,spinner,websocket, before,complete,success,error,after}
     * @returns {Promise<unknown>}
     */
    call({state, dispatch}, params) {
        if (!$A.isJson(params)) params = {url: params}
        if (!$A.isJson(params.header)) params.header = {}
        params.url = $A.apiUrl(params.url);
        params.data = $A.date2string(params.data);
        params.header['Content-Type'] = 'application/json';
        params.header['language'] = $A.getLanguage();
        params.header['token'] = state.userToken;
        params.header['fd'] = $A.getStorageString("userWsFd");
        //
        const cloneParams = $A.cloneJSON(params);
        return new Promise(function (resolve, reject) {
            if (params.spinner === true) {
                params.before = () => {
                    $A.spinnerShow();
                };
                //
                params.complete = () => {
                    $A.spinnerHide();
                };
            }
            //
            params.success = (result, status, xhr) => {
                if (!$A.isJson(result)) {
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
                if (ret === -2 && params.checkNick !== false) {
                    // 需要昵称
                    dispatch("userNickNameInput").then(() => {
                        dispatch("call", Object.assign(cloneParams, {
                            checkNick: false
                        })).then(resolve).catch(reject);
                    }).catch(() => {
                        reject({data, msg: $A.L('请设置昵称！')})
                    });
                    return;
                }
                if (ret === 1) {
                    resolve({data, msg});
                } else {
                    reject({data, msg: msg || "Unknown error"})
                    //
                    if (ret === -4001) {
                        dispatch("forgetProject", data.project_id);
                    } else if (ret === -4002) {
                        dispatch("forgetTask", data.task_id);
                    } else if (ret === -4003) {
                        dispatch("forgetDialog", data.dialog_id);
                    }
                }
            };
            params.error = () => {
                reject({data: {}, msg: "System error"})
            };
            //
            if (params.websocket === true || params.ws === true) {
                const apiWebsocket = $A.randomString(16);
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
     * @param data|{key, project_id}
     */
    toggleProjectParameter({state}, data) {
        $A.execMainDispatch("toggleProjectParameter", data)
        //
        let key = data;
        let project_id = state.projectId;
        if ($A.isJson(data)) {
            key = data.key;
            project_id = data.project_id;
        }
        if (project_id) {
            let index = state.cacheProjectParameter.findIndex(item => item.project_id == project_id)
            if (index === -1) {
                state.cacheProjectParameter.push($A.projectParameterTemplate(project_id));
                index = state.cacheProjectParameter.findIndex(item => item.project_id == project_id)
            }
            const cache = state.cacheProjectParameter[index];
            if (!$A.isJson(key)) {
                key = {[key]: !cache[key]};
            }
            state.cacheProjectParameter.splice(index, 1, Object.assign(cache, key))
            setTimeout(() => {
                $A.setStorage("cacheProjectParameter", state.cacheProjectParameter);
            });
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
            const userInfo = $A.cloneJSON(info);
            userInfo.userid = $A.runNum(userInfo.userid);
            userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
            state.userInfo = userInfo;
            state.userId = userInfo.userid;
            state.userToken = userInfo.token;
            state.userIsAdmin = $A.inArray('admin', userInfo.identity);
            $A.setStorage("userInfo", state.userInfo);
            dispatch("getProjects");
            dispatch("getDialogs");
            dispatch("getTaskForDashboard");
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
     * @param data {userid}
     */
    getUserBasic({state, dispatch}, data) {
        if (state.cacheLoading["loadUserBasic"] === true) {
            data && state.cacheUserWait.push(data);
            return;
        }
        //
        let time = $A.Time();
        let list = $A.cloneJSON(state.cacheUserWait);
        if (data && data.userid) {
            list.push(data)
        }
        state.cacheUserWait = [];
        //
        let array = [];
        let timeout = 0;
        list.some((item) => {
            let temp = state.cacheUserBasic.find(({userid}) => userid == item.userid);
            if (temp && time - temp._time <= 30) {
                setTimeout(() => {
                    state.cacheUserActive = Object.assign(temp, {__:Math.random()});
                    Store.set('cacheUserActive', temp);
                }, timeout += 5);
                return false;
            }
            array.push(item);
        });
        if (array.length === 0) {
            return;
        } else if (array.length > 30) {
            state.cacheUserWait = array.slice(30)
            array = array.slice(0, 30)
        }
        //
        state.cacheLoading["loadUserBasic"] = true;
        dispatch("call", {
            url: 'users/basic',
            data: {
                userid: array.map(({userid}) => userid)
            },
        }).then(result => {
            time = $A.Time();
            array.forEach((value) => {
                let data = result.data.find(({userid}) => userid == value.userid) || Object.assign(value, {email: ""});
                data._time = time;
                dispatch("saveUserBasic", data);
            });
            state.cacheLoading["loadUserBasic"] = false;
            dispatch("getUserBasic");
        }).catch(e => {
            console.error(e);
            state.cacheLoading["loadUserBasic"] = false;
            dispatch("getUserBasic");
        });
    },

    /**
     * 保存用户基础信息
     * @param state
     * @param data
     */
    saveUserBasic({state}, data) {
        $A.execMainDispatch("saveUserBasic", data)
        //
        let index = state.cacheUserBasic.findIndex(({userid}) => userid == data.userid);
        if (index > -1) {
            data = Object.assign({}, state.cacheUserBasic[index], data)
            state.cacheUserBasic.splice(index, 1, data);
        } else {
            state.cacheUserBasic.push(data)
        }
        state.cacheUserActive = Object.assign(data, {__:Math.random()});
        Store.set('cacheUserActive', data);
        setTimeout(() => {
            $A.setStorage("cacheUserBasic", state.cacheUserBasic);
        })
    },

    /**
     * 设置用户昵称
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    userNickNameInput({dispatch}) {
        return new Promise(function (resolve, reject) {
            let callback = (cb, success) => {
                if (typeof cb === "function") {
                    cb();
                }
                if (success === true) {
                    setTimeout(resolve, 301)
                } else {
                    setTimeout(reject, 301)
                }
            }
            $A.modalInput({
                title: "设置昵称",
                placeholder: "请输入昵称",
                okText: "保存",
                onOk: (value, cb) => {
                    if (value) {
                        dispatch("call", {
                            url: 'users/editdata',
                            data: {
                                nickname: value,
                            },
                            checkNick: false,
                        }).then(() => {
                            dispatch('getUserInfo').then(() => {
                                callback(cb, true);
                            }).catch(() => {
                                callback(cb, false);
                            });
                        }).catch(({msg}) => {
                            $A.modalError(msg, 301);
                            callback(cb, false);
                        });
                    } else {
                        callback(cb, false);
                    }
                },
                onCancel: () => {
                    callback(null, false);
                }
            });
        });
    },

    /**
     * 登出（打开登录页面）
     * @param state
     * @param dispatch
     */
    logout({state, dispatch}) {
        dispatch("handleClearCache", {}).then(() => {
            const from = ["/", "/login"].includes(window.location.pathname) ? "" : encodeURIComponent(window.location.href);
            $A.goForward({name: 'login', query: from ? {from: from} : {}}, true);
        });
    },

    /**
     * 清除缓存
     * @param state
     * @param dispatch
     * @param userInfo
     * @returns {Promise<unknown>}
     */
    handleClearCache({state, dispatch}, userInfo) {
        return new Promise(function (resolve, reject) {
            try {
                const cacheLoginEmail = $A.getStorageString("cacheLoginEmail");
                //
                window.localStorage.clear();
                //
                state.cacheUserBasic = [];
                state.cacheDialogs = state.dialogs = [];
                state.cacheProjects = state.projects = [];
                state.cacheColumns = state.columns = [];
                state.cacheTasks = state.tasks = [];
                //
                $A.setStorage("cacheProjectParameter", state.cacheProjectParameter);
                $A.setStorage("cacheServerUrl", state.cacheServerUrl);
                $A.setStorage("cacheLoginEmail", cacheLoginEmail);
                dispatch("saveUserInfo", $A.isJson(userInfo) ? userInfo : state.userInfo);
                //
                resolve()
            } catch (e) {
                reject(e)
            }
        });
    },

    /** *****************************************************************************************/
    /** ************************************** 文件 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存文件数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveFile({state, dispatch}, data) {
        $A.execMainDispatch("saveFile", data)
        //
        if ($A.isArray(data)) {
            data.forEach((file) => {
                dispatch("saveFile", file);
            });
        } else if ($A.isJson(data)) {
            let index = state.files.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.files.splice(index, 1, Object.assign({}, state.files[index], data));
            } else {
                state.files.push(data)
            }
        }
    },

    /**
     * 忘记文件数据
     * @param state
     * @param dispatch
     * @param file_id
     */
    forgetFile({state, dispatch}, file_id) {
        $A.execMainDispatch("forgetFile", file_id)
        //
        let ids = $A.isArray(file_id) ? file_id : [file_id];
        ids.some(id => {
            state.files = state.files.filter(file => file.id != id);
            state.files.some(file => {
                if (file.pid == id) {
                    dispatch("forgetFile", file.id);
                }
            });
        })
    },

    /**
     * 获取文件
     * @param state
     * @param dispatch
     * @param pid
     * @returns {Promise<unknown>}
     */
    getFiles({state, dispatch}, pid) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'file/lists',
                data: {
                    pid
                },
            }).then((result) => {
                const ids = result.data.map(({id}) => id)
                state.files = state.files.filter((item) => item.pid != pid || ids.includes(item.id));
                //
                dispatch("saveFile", result.data);
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e)
            });
        });
    },

    /**
     * 搜索文件
     * @param state
     * @param dispatch
     * @param key
     * @returns {Promise<unknown>}
     */
    searchFiles({state, dispatch}, key) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'file/search',
                data: {
                    key,
                },
            }).then((result) => {
                dispatch("saveFile", result.data);
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e)
            });
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
        $A.execMainDispatch("saveProject", data)
        //
        if ($A.isArray(data)) {
            data.forEach((project) => {
                dispatch("saveProject", project)
            });
        } else if ($A.isJson(data)) {
            if (typeof data.project_column !== "undefined") {
                dispatch("saveColumn", data.project_column)
                delete data.project_column;
            }
            let index = state.projects.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.projects.splice(index, 1, Object.assign({}, state.projects[index], data));
            } else {
                if (typeof data.project_user === "undefined") {
                    data.project_user = []
                }
                state.projects.push(data);
            }
            setTimeout(() => {
                $A.setStorage("cacheProjects", state.cacheProjects = state.projects);
            })
        }
    },

    /**
     * 忘记项目数据
     * @param state
     * @param project_id
     */
    forgetProject({state}, project_id) {
        $A.execMainDispatch("forgetProject", project_id)
        //
        let ids = $A.isArray(project_id) ? project_id : [project_id];
        ids.some(id => {
            let index = state.projects.findIndex(project => project.id == id);
            if (index > -1) {
                state.projects.splice(index, 1);
            }
        })
        if (ids.includes(state.projectId)) {
            const project = state.projects.find(({id}) => id && id != project_id);
            if (project) {
                $A.goForward({path: '/manage/project/' + project.id});
            } else {
                $A.goForward({path: '/manage/dashboard'});
            }
        }
        setTimeout(() => {
            $A.setStorage("cacheProjects", state.cacheProjects = state.projects);
        })
    },

    /**
     * 获取项目
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    getProjects({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.projects = [];
                reject({msg: 'Parameter error'});
                return;
            }
            if (state.projects.length === 0 && state.cacheProjects.length > 0) {
                state.projects = state.cacheProjects;
            }
            dispatch("call", {
                url: 'project/lists',
                data: data || {}
            }).then(({data}) => {
                state.projectTotal = data.total_all;
                dispatch("saveProject", data.data);
                resolve(data)
            }).catch(e => {
                console.error(e);
                reject(e)
            });
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
        return new Promise(function (resolve, reject) {
            if ($A.runNum(project_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            state.projectLoad++;
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
     * @param state
     * @param dispatch
     * @param project_id
     */
    archivedProject({state, dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(project_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
     * @param state
     * @param dispatch
     * @param project_id
     */
    removeProject({state, dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(project_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
     * @param state
     * @param dispatch
     * @param project_id
     */
    exitProject({state, dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(project_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
        $A.execMainDispatch("saveColumn", data)
        //
        if ($A.isArray(data)) {
            data.forEach((column) => {
                dispatch("saveColumn", column)
            });
        } else if ($A.isJson(data)) {
            let index = state.columns.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.columns.splice(index, 1, Object.assign({}, state.columns[index], data));
            } else {
                state.columns.push(data);
            }
            setTimeout(() => {
                $A.setStorage("cacheColumns", state.cacheColumns = state.columns);
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
        $A.execMainDispatch("forgetColumn", column_id)
        //
        let ids = $A.isArray(column_id) ? column_id : [column_id];
        let project_ids = [];
        ids.some(id => {
            let index = state.columns.findIndex(column => column.id == id);
            if (index > -1) {
                project_ids.push(state.columns[index].project_id)
                dispatch('getProjectOne', state.columns[index].project_id)
                state.columns.splice(index, 1);
            }
        })
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id))
        //
        setTimeout(() => {
            $A.setStorage("cacheColumns", state.cacheColumns = state.columns);
        })
    },

    /**
     * 获取列表
     * @param state
     * @param dispatch
     * @param project_id
     * @returns {Promise<unknown>}
     */
    getColumns({state, dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.columns = [];
                reject({msg: 'Parameter error'})
                return;
            }
            if (state.columns.length === 0 && state.cacheColumns.length > 0) {
                state.columns = state.cacheColumns;
            }
            state.projectLoad++;
            dispatch("call", {
                url: 'project/column/lists',
                data: {
                    project_id
                }
            }).then(({data}) => {
                state.projectLoad--;
                //
                const ids = data.data.map(({id}) => id)
                state.columns = state.columns.filter((item) => item.project_id != project_id || ids.includes(item.id));
                //
                dispatch("saveColumn", data.data);
                resolve(data.data)
                // 判断只有1列的时候默认版面为表格模式
                if (state.columns.filter(item => item.project_id == project_id).length === 1) {
                    const cache = state.cacheProjectParameter.find(item => item.project_id == project_id) || {};
                    if (typeof cache.cardInit === "undefined" || cache.cardInit === false) {
                        dispatch("toggleProjectParameter", {
                            project_id,
                            key: {
                                card: false,
                                cardInit: true,
                            }
                        });
                    }
                }
            }).catch(e => {
                console.error(e);
                state.projectLoad--;
                reject(e);
            });
        })
    },

    /**
     * 删除列表
     * @param state
     * @param dispatch
     * @param column_id
     */
    removeColumn({state, dispatch}, column_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(column_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
        $A.execMainDispatch("saveTask", data)
        //
        if ($A.isArray(data)) {
            data.forEach((task) => {
                dispatch("saveTask", task)
            });
        } else if ($A.isJson(data)) {
            data._time = $A.Time();
            let index = state.tasks.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.tasks.splice(index, 1, Object.assign({}, state.tasks[index], data));
            } else {
                state.tasks.push(data);
            }
            //
            if (data.parent_id > 0 && state.tasks.findIndex(({id}) => id == data.parent_id) === -1) {
                dispatch("getTaskOne", data.parent_id);
            }
            if (data.is_update_project) {
                data.is_update_project = false;
                dispatch("getProjectOne", data.project_id);
            }
            if (data.is_update_content) {
                data.is_update_content = false;
                dispatch("getTaskContent", data.id);
            }
            if (data.is_update_subtask) {
                data.is_update_subtask = false;
                dispatch("getTaskForParent", data.id);
            }
            //
            setTimeout(() => {
                $A.setStorage("cacheTasks", state.cacheTasks = state.tasks);
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
        $A.execMainDispatch("forgetTask", task_id)
        //
        let ids = $A.isArray(task_id) ? task_id : [task_id];
        let parent_ids = [];
        let project_ids = [];
        ids.some(id => {
            let index = state.tasks.findIndex(task => task.id == id);
            if (index > -1) {
                if (state.tasks[index].parent_id) {
                    parent_ids.push(state.tasks[index].parent_id)
                }
                project_ids.push(state.tasks[index].project_id)
                state.tasks.splice(index, 1);
            }
        })
        Array.from(new Set(parent_ids)).some(id => dispatch("getTaskOne", id))
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id))
        //
        if (ids.includes(state.taskId)) {
            state.taskId = 0;
        }
        setTimeout(() => {
            $A.setStorage("cacheTasks", state.cacheTasks = state.tasks);
        })
    },

    /**
     * 增加任务消息数量
     * @param state
     * @param dialog_id
     */
    increaseTaskMsgNum({state}, dialog_id) {
        $A.execMainDispatch("increaseTaskMsgNum", dialog_id)
        //
        const task = state.tasks.find(task => task.dialog_id === dialog_id);
        if (task) task.msg_num++;
    },

    /**
     * 获取任务
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    getTasks({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.tasks = [];
                reject({msg: 'Parameter error'});
                return;
            }
            if (state.tasks.length == 0 && state.cacheTasks.length > 0) {
                state.tasks = state.cacheTasks;
            }
            if (data.project_id) {
                state.projectLoad++;
            }
            //
            dispatch("call", {
                url: 'project/task/lists',
                data: data
            }).then(result => {
                if (data.project_id) {
                    state.projectLoad--;
                }
                //
                const resData = result.data;
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
                                dispatch("getTasks", nextData).then(resolve).catch(reject)
                            },
                            onCancel: () => {
                                resolve()
                            }
                        });
                    } else {
                        dispatch("getTasks", nextData).then(resolve).catch(reject)
                    }
                } else {
                    resolve()
                }
            }).catch(e => {
                console.error(e);
                reject(e)
                if (data.project_id) {
                    state.projectLoad--;
                }
            });
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
            if ($A.runNum(task_id) === 0) {
                reject({msg: 'Parameter error'});
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
     * 获取Dashboard相关任务
     * @param state
     * @param dispatch
     * @param getters
     */
    getTaskForDashboard({state, dispatch, getters}) {
        if (state.cacheLoading["loadDashboardTasks"] === true) {
            return;
        }
        state.cacheLoading["loadDashboardTasks"] = true;
        //
        const time = $A.Time()
        const {today, overdue} = getters.dashboardTask;
        const currentIds = today.map(({id}) => id)
        currentIds.push(...overdue.map(({id}) => id))
        //
        let loadIng = 2;
        let call = () => {
            if (loadIng <= 0) {
                state.cacheLoading["loadDashboardTasks"] = false;
                //
                const {today, overdue} = getters.dashboardTask;
                const newIds = today.filter(task => task._time >= time).map(({id}) => id)
                newIds.push(...overdue.filter(task => task._time >= time).map(({id}) => id))
                dispatch("forgetTask", currentIds.filter(v => newIds.indexOf(v) == -1))
                return;
            }
            loadIng--;
            if (loadIng == 1) {
                // 获取今日任务
                dispatch("getTasks", {
                    complete: "no",
                    time: [
                        $A.formatDate("Y-m-d 00:00:00"),
                        $A.formatDate("Y-m-d 23:59:59")
                    ],
                }).then(call).catch(call)
            } else if (loadIng == 0) {
                // 获取过期任务
                dispatch("getTasks", {
                    complete: "no",
                    time_before: $A.formatDate("Y-m-d H:i:s"),
                }).then(call).catch(call)
            }
        }
        call();
    },

    /**
     * 获取项目任务
     * @param state
     * @param dispatch
     * @param project_id
     * @returns {Promise<unknown>}
     */
    getTaskForProject({state, dispatch}, project_id) {
        return new Promise(function (resolve, reject) {
            const time = $A.Time()
            const currentIds = state.tasks.filter(task => task.project_id == project_id).map(({id}) => id)
            //
            const call = () => {
                const newIds = state.tasks.filter(task => task.project_id == project_id && task._time >= time).map(({id}) => id)
                dispatch("forgetTask", currentIds.filter(v => newIds.indexOf(v) == -1))
            }
            dispatch("getTasks", {project_id}).then(() => {
                call()
                resolve()
            }).catch(() => {
                call()
                reject()
            })
        })
    },

    /**
     * 获取子任务
     * @param state
     * @param dispatch
     * @param parent_id
     * @returns {Promise<unknown>}
     */
    getTaskForParent({state, dispatch}, parent_id) {
        return new Promise(function (resolve, reject) {
            const time = $A.Time()
            const currentIds = state.tasks.filter(task => task.parent_id == parent_id).map(({id}) => id)
            //
            let call = () => {
                const newIds = state.tasks.filter(task => task.parent_id == parent_id && task._time >= time).map(({id}) => id)
                dispatch("forgetTask", currentIds.filter(v => newIds.indexOf(v) == -1))
            }
            dispatch("getTasks", {parent_id}).then(() => {
                call()
                resolve()
            }).catch(() => {
                call()
                reject()
            })
        })
    },

    /**
     * 删除任务
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    removeTask({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    archivedTask({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
            if ($A.runNum(task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
            if ($A.runNum(task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
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
                dispatch("saveTask", {
                    id: task_id,
                    file_num: result.data.length
                });
                resolve(result)
            }).catch(e => {
                console.error(e);
                reject(e);
            });
        });
    },

    /**
     * 忘记任务文件
     * @param state
     * @param dispatch
     * @param file_id
     */
    forgetTaskFile({state, dispatch}, file_id) {
        let ids = $A.isArray(file_id) ? file_id : [file_id];
        ids.some(id => {
            let index = state.taskFiles.findIndex(file => file.id == id)
            if (index > -1) {
                state.taskFiles.splice(index, 1)
            }
        })
    },

    /**
     * 打开任务详情页
     * @param state
     * @param dispatch
     * @param task
     */
    openTask({state, dispatch}, task) {
        let task_id = task;
        if ($A.isJson(task)) {
            if (task.parent_id > 0) {
                task_id = task.parent_id;
            } else {
                task_id = task.id;
            }
        }
        state.taskId = task_id;
        if (task_id > 0) {
            dispatch("getTaskOne", task_id).then(() => {
                dispatch("getTaskContent", task_id);
                dispatch("getTaskFiles", task_id);
                dispatch("getTaskForParent", task_id);
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
            const post = $A.cloneJSON($A.date2string(data));
            if ($A.isArray(post.column_id)) post.column_id = post.column_id.find((val) => val)
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
     * @param task
     */
    addTaskSuccess({dispatch}, task) {
        if (typeof task.new_column !== "undefined") {
            dispatch("saveColumn", task.new_column)
            delete task.new_column
        }
        dispatch("saveTask", task)
        dispatch("getProjectOne", task.project_id);
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
            const post = $A.cloneJSON($A.date2string(data));
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
        $A.execMainDispatch("saveDialog", data)
        //
        if ($A.isArray(data)) {
            data.forEach((dialog) => {
                dispatch("saveDialog", dialog)
            });
        } else if ($A.isJson(data)) {
            let index = state.dialogs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.dialogs.splice(index, 1, Object.assign({}, state.dialogs[index], data));
            } else {
                state.dialogs.push(data);
            }
            setTimeout(() => {
                $A.setStorage("cacheDialogs", state.cacheDialogs = state.dialogs);
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
        $A.execMainDispatch("updateDialogLastMsg", data)
        //
        let dialog = state.dialogs.find(({id}) => id == data.dialog_id);
        if (dialog) {
            dispatch("saveDialog", {
                id: data.dialog_id,
                last_msg: data,
                last_at: $A.formatDate("Y-m-d H:i:s")
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
        if (state.userId === 0) {
            state.dialogs = [];
            return;
        }
        dispatch("call", {
            url: 'dialog/lists',
        }).then(result => {
            dispatch("saveDialog", result.data.data.reverse());
        }).catch(e => {
            console.error(e);
        });
    },

    /**
     * 获取单个会话信息
     * @param state
     * @param dispatch
     * @param dialog_id
     * @returns {Promise<unknown>}
     */
    getDialogOne({state, dispatch}, dialog_id) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(dialog_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("call", {
                url: 'dialog/one',
                data: {
                    dialog_id,
                },
            }).then(result => {
                dispatch("saveDialog", result.data);
                resolve(result);
            }).catch(e => {
                console.error(e);
                reject(e);
            });
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
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
            }).then(result => {
                dispatch("saveDialog", result.data);
                $A.setStorage("messenger::dialogId", result.data.id);
                state.dialogOpenId = result.data.id;
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
        $A.execMainDispatch("moveDialogTop", dialog_id)
        //
        const index = state.dialogs.findIndex(({id}) => id == dialog_id);
        if (index > -1) {
            const tmp = $A.cloneJSON(state.dialogs[index]);
            state.dialogs.splice(index, 1);
            state.dialogs.unshift(tmp);
        }
    },

    /**
     * 忘记对话数据
     * @param state
     * @param dialog_id
     */
    forgetDialog({state}, dialog_id) {
        $A.execMainDispatch("forgetDialog", dialog_id)
        //
        let ids = $A.isArray(dialog_id) ? dialog_id : [dialog_id];
        ids.some(id => {
            let index = state.dialogs.findIndex(dialog => dialog.id == id);
            if (index > -1) {
                state.dialogs.splice(index, 1);
            }
        })
        if (ids.includes($A.getStorageInt("messenger::dialogId"))) {
            $A.setStorage("messenger::dialogId", 0)
        }
        //
        setTimeout(() => {
            $A.setStorage("cacheDialogs", state.cacheDialogs = state.dialogs);
        })
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
        $A.execMainDispatch("saveDialogMsg", data)
        //
        if ($A.isArray(data)) {
            data.forEach((msg) => {
                dispatch("saveDialogMsg", msg)
            });
        } else if ($A.isJson(data)) {
            let index = state.dialogMsgs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.dialogMsgs.splice(index, 1, Object.assign({}, state.dialogMsgs[index], data));
            } else {
                state.dialogMsgs.push(data);
            }
        }
    },

    /**
     * 获取会话消息
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMsgs({state, dispatch}, dialog_id) {
        let dialog = state.dialogs.find(({id}) => id == dialog_id);
        if (!dialog) {
            dialog = {
                id: dialog_id,
            };
            state.dialogs.push(dialog);
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
            dispatch("saveDialog", dialog);
            //
            const ids = result.data.data.map(({id}) => id)
            state.dialogMsgs = state.dialogMsgs.filter((item) => item.dialog_id != dialog_id || ids.includes(item.id));
            //
            dispatch("saveDialog", result.data.dialog);
            dispatch("saveDialogMsg", result.data.data);
        }).catch(e => {
            console.error(e);
            dialog.loading = false;
        });
    },

    /**
     * 获取更多(下一页)会话消息
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMoreMsgs({state, dispatch}, dialog_id) {
        return new Promise(function (resolve, reject) {
            const dialog = state.dialogs.find(({id}) => id == dialog_id);
            if (!dialog) {
                reject({msg: 'Parameter error'});
                return;
            }
            if (!dialog.hasMorePages) {
                reject({msg: 'No more page'});
                return;
            }
            if (dialog.loading) {
                reject({msg: 'Loading'});
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
                    id: $A.cloneJSON(state.wsReadWaitList)
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
        //
        let url = $A.apiUrl('../ws');
        url = url.replace("https://", "wss://");
        url = url.replace("http://", "ws://");
        url += "?action=web&token=" + state.userToken;
        //
        state.ws = new WebSocket(url);
        state.ws.onopen = (e) => {
            // console.log("[WS] Open", e)
        };
        state.ws.onclose = (e) => {
            // console.log("[WS] Close", e);
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onerror = (e) => {
            // console.log("[WS] Error", e);
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onmessage = (e) => {
            // console.log("[WS] Message", e);
            const msgDetail = $A.jsonParse(event.data);
            const {type, msgId} = msgDetail;
            switch (type) {
                case "open":
                    $A.setStorage("userWsFd", msgDetail.data.fd)
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
                                // console.log("[WS] Callerr", err);
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
                                if (mode === "add" || mode === "chat") {
                                    // 新增任务消息数量
                                    dispatch("increaseTaskMsgNum", dialog_id);
                                    if (mode === "chat") {
                                        return;
                                    }
                                    let dialog = state.dialogs.find(({id}) => id == data.dialog_id);
                                    // 更新对话列表
                                    if (dialog) {
                                        // 新增未读数
                                        if (data.userid !== state.userId && state.dialogMsgs.findIndex(({id}) => id == data.id) === -1) {
                                            dialog.unread++;
                                        }
                                        // 移动到首位
                                        dispatch("moveDialogTop", dialog_id);
                                    }
                                    state.dialogMsgPush = data;
                                }
                                // 更新消息列表
                                dispatch("saveDialogMsg", data)
                                // 更新最后消息
                                dispatch("updateDialogLastMsg", data);
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
                                        dispatch("getProjectOne", data.id);
                                        dispatch("getTaskForProject", data.id)
                                        break;
                                    case 'archived':
                                    case 'delete':
                                        dispatch("forgetProject", data.id);
                                        break;
                                    case 'sort':
                                        dispatch("getTaskForProject", data.id)
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
                                        dispatch("saveTask", data)
                                        break;
                                    case 'dialog':
                                        dispatch("saveTask", data)
                                        dispatch("getDialogOne", data.dialog_id)
                                        break;
                                    case 'upload':
                                        dispatch("getTaskFiles", data.task_id)
                                        break;
                                    case 'filedelete':
                                        dispatch("forgetTaskFile", data.id)
                                        break;
                                    case 'archived':
                                    case 'delete':
                                        dispatch("forgetTask", data.id)
                                        break;
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 文件消息
                         */
                        case "file":
                            (function (msg) {
                                const {action, data} = msg;
                                switch (action) {
                                    case 'add':
                                    case 'update':
                                        dispatch("saveFile", data);
                                        break;
                                    case 'delete':
                                        dispatch("forgetFile", data.id);
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
     * @param params {type, data, callback}
     */
    websocketSend({state}, params) {
        if (!$A.isJson(params)) {
            typeof callback === "function" && callback(null, false)
            return;
        }
        const {type, data, callback} = params;
        let msgId = undefined;
        if (!state.ws) {
            typeof callback === "function" && callback(null, false)
            return;
        }
        if (typeof callback === "function") {
            msgId = $A.randomString(16)
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
     * 记录 websocket 访问状态
     * @param state
     * @param dispatch
     * @param path
     */
    websocketPath({state, dispatch}, path) {
        clearTimeout(state.wsPathTimeout);
        state.wsPathValue = path;
        state.wsPathTimeout = setTimeout(() => {
            if (state.wsPathValue == path) {
                dispatch("websocketSend", {type: 'path', data: {path}});
            }
        }, 1000);
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
