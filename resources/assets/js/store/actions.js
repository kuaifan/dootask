import {Store} from 'le5le-store';
import * as openpgp from 'openpgp/lightweight';
import {languageType} from "../language";
import {$callData, $urlSafe} from './utils'

export default {
    /**
     * 初始化
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    init({state, dispatch}) {
        return new Promise(async resolve => {
            let action = null

            // 迁移缓存
            const initTag = await $A.IDBBoolean("initTag")
            if (!initTag) {
                await $A.IDBSet("initTag", true)
                const userInfo = $A.getStorageJson("userInfo")
                if (userInfo.userid > 0) {
                    await $A.IDBSet("userInfo", userInfo)
                    await $A.IDBSet("cacheServerUrl", $A.getStorageString("cacheServerUrl"))
                    window.localStorage.clear()
                }
            }

            // 读取缓存
            state.clientId = await $A.IDBString("clientId")
            state.cacheServerUrl = await $A.IDBString("cacheServerUrl")
            state.cacheUserBasic = await $A.IDBArray("cacheUserBasic")
            state.cacheDialogs = (await $A.IDBArray("cacheDialogs")).map(item => Object.assign(item, {loading: false, extra_draft_has: item.extra_draft_content ? 1 : 0}))
            state.cacheProjects = await $A.IDBArray("cacheProjects")
            state.cacheColumns = await $A.IDBArray("cacheColumns")
            state.cacheTasks = await $A.IDBArray("cacheTasks")
            state.cacheProjectParameter = await $A.IDBArray("cacheProjectParameter")
            state.cacheTaskBrowse = await $A.IDBArray("cacheTaskBrowse")
            state.dialogMsgs = await $A.IDBArray("dialogMsgs")
            state.fileLists = await $A.IDBArray("fileLists")
            state.userInfo = await $A.IDBJson("userInfo")
            state.callAt = await $A.IDBArray("callAt")
            state.cacheEmojis = await $A.IDBArray("cacheEmojis")

            // 会员信息
            if (state.userInfo.userid) {
                state.userId = state.userInfo.userid = $A.runNum(state.userInfo.userid)
                state.userToken = state.userInfo.token
                state.userIsAdmin = $A.inArray("admin", state.userInfo.identity)
            }

            // ServerUrl
            if (state.cacheServerUrl) {
                window.systemInfo.apiUrl = state.cacheServerUrl
            }

            // 主题皮肤
            switch (state.themeMode) {
                case 'dark':
                    $A.dark.enableDarkMode()
                    break;
                case 'light':
                    $A.dark.disableDarkMode()
                    break;
                default:
                    state.themeMode = "auto"
                    $A.dark.autoDarkMode()
                    break;
            }
            state.themeIsDark = $A.dark.isDarkEnabled()

            // 客户端ID
            if (!state.clientId) {
                state.clientId = $A.randomString(6)
                await $A.IDBSet("clientId", state.clientId)
            }

            // 清理缓存
            const clearCache = await $A.IDBString("clearCache")
            if (clearCache) {
                await $A.IDBRemove("clearCache")
                await $A.IDBSet("callAt", state.callAt = [])
                if (clearCache === "handle") {
                    await dispatch(action = "handleClearCache")
                }
            }

            // 获取apiKey
            dispatch("call", {
                url: "users/key/client",
                data: {client_id: state.clientId},
                encrypt: false,
            }).then(({data}) => {
                state.apiKeyData = data;
            })

            // 加载语言包
            await $A.loadScriptS([
                `language/web/key.js`,
                `language/web/${languageType}.js`,
            ])

            resolve(action)
        })
    },

    /**
     * 访问接口
     * @param state
     * @param dispatch
     * @param params // {url,data,method,timeout,header,spinner,websocket,encrypt, before,complete,success,error,after}
     * @returns {Promise<unknown>}
     */
    call({state, dispatch}, params) {
        if (!$A.isJson(params)) params = {url: params}
        const header = {
            'Content-Type': 'application/json',
            'language': languageType,
            'token': state.userToken,
            'fd': $A.getSessionStorageString("userWsFd"),
            'version': window.systemInfo.version || "0.0.1",
            'platform': $A.Platform,
        }
        if ($A.isJson(params.header)) {
            params.header = Object.assign(header, params.header)
        } else {
            params.header = header
        }
        if (params.encrypt === undefined && $A.inArray(params.url, [
            'users/login',
            'users/editpass',
            'users/operation',
            'users/delete/account',
            'system/license',
            'users/bot/*',
            'dialog/msg/*',
        ], true)) {
            params.encrypt = true
        }
        if (params.encrypt) {
            const userAgent = window.navigator.userAgent;
            if (/Windows NT 5.1|Windows XP/.test(userAgent)
                || userAgent.indexOf("Windows NT 6.0") !== -1
                || userAgent.indexOf("Windows NT 6.1") !== -1
                || userAgent.indexOf("Windows NT 6.2") !== -1) {
                params.encrypt = false  // 是 Windows Xp, Vista, 7, 8 系统，不支持加密
            }
        }
        params.url = $A.apiUrl(params.url)
        params.data = $A.date2string(params.data)
        //
        const cloneParams = $A.cloneJSON(params)
        return new Promise(async (resolve, reject) => {
            // 加密传输
            const encrypt = []
            if (params.encrypt === true) {
                // 有数据才加密
                if (params.data) {
                    // PGP加密
                    if (state.apiKeyData.type === 'pgp') {
                        encrypt.push(`encrypt_type=${state.apiKeyData.type};encrypt_id=${state.apiKeyData.id}`)
                        params.method = "post"  // 加密传输时强制使用post
                        params.data = {encrypted: await dispatch("pgpEncryptApi", params.data)}
                    }
                }
                encrypt.push("client_type=pgp;client_key=" + (await dispatch("pgpGetLocalKey")).publicKeyB64)
            }
            if (encrypt.length > 0) {
                params.header.encrypt = encrypt.join(";")
            }
            // 数据转换
            if (params.method === "post") {
                params.data = JSON.stringify(params.data)
            }
            // Spinner
            if (params.spinner === true || (typeof params.spinner === "number" && params.spinner > 0)) {
                const {before, complete} = params
                params.before = () => {
                    dispatch("showSpinner", typeof params.spinner === "number" ? params.spinner : 0)
                    typeof before === "function" && before()
                }
                //
                params.complete = () => {
                    dispatch("hiddenSpinner")
                    typeof complete === "function" && complete()
                }
            }
            // 请求回调
            params.success = async (result, status, xhr) => {
                state.ajaxNetworkException = false
                if (!$A.isJson(result)) {
                    console.log(result, status, xhr)
                    reject({ret: -1, data: {}, msg: "Return error"})
                    return
                }
                if (params.encrypt === true && result.encrypted) {
                    result = await dispatch("pgpDecryptApi", result.encrypted)
                }
                const {ret, data, msg} = result
                if (ret === -1) {
                    state.userId = 0
                    if (params.skipAuthError !== true) {
                        //身份丢失
                        $A.modalError({
                            content: msg,
                            onOk: () => {
                                dispatch("logout")
                            }
                        })
                        reject(result)
                        return
                    }
                }
                if (ret === -2 && params.checkNick !== false) {
                    // 需要昵称
                    dispatch("userEditInput", 'nickname').then(() => {
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }).catch(err => {
                        reject({ret: -1, data, msg: err || $A.L('请设置昵称！')})
                    })
                    return
                }
                if (ret === -3 && params.checkTel !== false) {
                    // 需要联系电话
                    dispatch("userEditInput", 'tel').then(() => {
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }).catch(err => {
                        reject({ret: -1, data, msg: err || $A.L('请设置联系电话！')})
                    })
                    return
                }
                if (ret === 1) {
                    resolve({data, msg})
                } else {
                    reject({ret, data, msg: msg || "Unknown error"})
                    //
                    if (ret === -4001) {
                        dispatch("forgetProject", data.project_id)
                    } else if (ret === -4002) {
                        dispatch("forgetTask", data.task_id)
                    } else if (ret === -4003) {
                        dispatch("forgetDialog", data.dialog_id)
                    }
                }
            }
            params.error = (xhr, status) => {
                const networkException = window.navigator.onLine === false || (status === 0 && xhr.readyState === 4)
                if (networkException && cloneParams.__networkFailureRetry !== true) {
                    // 网络异常，重试一次
                    setTimeout(_ => {
                        cloneParams.__networkFailureRetry = true
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }, 300)
                    return
                }
                if (params.checkNetwork !== false) {
                    state.ajaxNetworkException = networkException
                }
                if (networkException) {
                    reject({ret: -1001, data: {}, msg: "Network exception"})
                } else {
                    reject({ret: -1, data: {}, msg: "System error"})
                }
            }
            // WebSocket
            if (params.websocket === true) {
                const apiWebsocket = $A.randomString(16)
                const apiTimeout = setTimeout(() => {
                    const WListener = state.ajaxWsListener.find((item) => item.apiWebsocket == apiWebsocket)
                    if (WListener) {
                        WListener.complete()
                        WListener.error("timeout")
                        WListener.after()
                    }
                    state.ajaxWsListener = state.ajaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket)
                }, params.timeout || 30000)
                state.ajaxWsListener.push({
                    apiWebsocket: apiWebsocket,
                    complete: typeof params.complete === "function" ? params.complete : () => { },
                    success: typeof params.success === "function" ? params.success : () => { },
                    error: typeof params.error === "function" ? params.error : () => { },
                    after: typeof params.after === "function" ? params.after : () => { },
                })
                //
                params.complete = () => { }
                params.success = () => { }
                params.error = () => { }
                params.after = () => { }
                params.header['Api-Websocket'] = apiWebsocket
                //
                if (state.ajaxWsReady === false) {
                    state.ajaxWsReady = true
                    dispatch("websocketMsgListener", {
                        name: "apiWebsocket",
                        callback: (msg) => {
                            switch (msg.type) {
                                case 'apiWebsocket':
                                    clearTimeout(apiTimeout)
                                    const apiWebsocket = msg.apiWebsocket
                                    const apiSuccess = msg.apiSuccess
                                    const apiResult = msg.data
                                    const WListener = state.ajaxWsListener.find((item) => item.apiWebsocket == apiWebsocket)
                                    if (WListener) {
                                        WListener.complete()
                                        if (apiSuccess) {
                                            WListener.success(apiResult)
                                        } else {
                                            WListener.error(apiResult)
                                        }
                                        WListener.after()
                                    }
                                    state.ajaxWsListener = state.ajaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket)
                                    break
                            }
                        }
                    })
                }
            }
            //
            $A.ajaxc(params)
        })
    },

    /**
     * 是否启用首页
     * @param dispatch
     * @param state
     * @returns {Promise<unknown>}
     */
    needHome({dispatch, state}) {
        return new Promise((resolve, reject) => {
            dispatch("call", {
                url: "system/get/starthome",
            }).then(({data}) => {
                if (!!data.need_start) {
                    resolve(data)
                } else {
                    reject()
                }
            }).catch(reject);
        })
    },

    /**
     * 下载文件
     * @param state
     * @param data
     */
    downUrl({state}, data) {
        if (!data) {
            return
        }
        let url = data;
        let addToken = true
        if ($A.isJson(data)) {
            url = data.url
            addToken = !!data.token
        }
        if (addToken) {
            let params = {
                token: state.userToken
            };
            if ($A.isJson(data)) {
                url = data.url;
                params = data.params || {};
            }
            url = $A.urlAddParams(url, params);
        }
        if ($A.Electron) {
            $A.Electron.request({action: 'openExternal', url}, () => {
                // 成功
            }, () => {
                // 失败
            });
        } else if ($A.isEEUiApp) {
            $A.eeuiAppOpenWeb(url);
        } else {
            window.open(url)
        }
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
        let value = null;
        let project_id = state.projectId;
        if ($A.isJson(data)) {
            key = data.key;
            value = data.value;
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
                key = {[key]: value || !cache[key]};
            }
            state.cacheProjectParameter.splice(index, 1, Object.assign(cache, key))
            //
            $A.IDBSave("cacheProjectParameter", state.cacheProjectParameter);
        }
    },

    /**
     * 设置主题
     * @param state
     * @param mode
     * @returns {Promise<unknown>}
     */
    setTheme({state}, mode) {
        return new Promise(function (resolve) {
            if (mode === undefined) {
                resolve(false)
                return;
            }
            if (!$A.isChrome()) {
                if ($A.isEEUiApp) {
                    $A.modalWarning("仅Android设置支持主题功能");
                } else {
                    $A.modalWarning("仅客户端或Chrome浏览器支持主题功能");
                }
                resolve(false)
                return;
            }
            switch (mode) {
                case 'dark':
                    $A.dark.enableDarkMode()
                    break;
                case 'light':
                    $A.dark.disableDarkMode()
                    break;
                default:
                    $A.dark.autoDarkMode()
                    break;
            }
            state.themeMode = mode;
            state.themeIsDark = $A.dark.isDarkEnabled();
            window.localStorage.setItem("__theme:mode__", mode);
            resolve(true)
        });
    },

    /**
     * 获取基本数据（项目、对话、仪表盘任务、会员基本信息）
     * @param state
     * @param dispatch
     * @param timeout
     */
    getBasicData({state, dispatch}, timeout) {
        if (typeof timeout === "number") {
            window.__getBasicDataTimer && clearTimeout(window.__getBasicDataTimer)
            if (timeout > -1) {
                window.__getBasicDataTimer = setTimeout(_ => dispatch("getBasicData", null), timeout)
            }
            return
        }
        //
        const tmpKey = state.userId + $A.Time()
        if (window.__getBasicDataKey === tmpKey) {
            return
        }
        window.__getBasicDataKey = tmpKey
        //
        dispatch("getProjects").catch(() => {});
        dispatch("getDialogs").catch(() => {});
        dispatch("getReportUnread", 1000);
        dispatch("getApproveUnread", 1000);
        dispatch("getTaskForDashboard");
        dispatch("dialogMsgRead");
        //
        const allIds = Object.values(state.userAvatar).map(({userid}) => userid);
        [...new Set(allIds)].some(userid => dispatch("getUserBasic", {userid}))
    },

    /**
     * 获取未读工作报告数量
     * @param state
     * @param dispatch
     * @param timeout
     */
    getReportUnread({state, dispatch}, timeout) {
        window.__getReportUnread && clearTimeout(window.__getReportUnread)
        window.__getReportUnread = setTimeout(() => {
            if (state.userId === 0) {
                state.reportUnreadNumber = 0;
            } else {
                dispatch("call", {
                    url: 'report/unread',
                }).then(({data}) => {
                    state.reportUnreadNumber = data.total || 0;
                }).catch(_ => {});
            }
        }, typeof timeout === "number" ? timeout : 1000)
    },


     /**
     * 获取审批待办未读数量
     * @param state
     * @param dispatch
     * @param timeout
     */
     getApproveUnread({state, dispatch}, timeout) {
        window.__getApproveUnread && clearTimeout(window.__getApproveUnread)
        window.__getApproveUnread = setTimeout(() => {
            if (state.userId === 0) {
                state.approveUnreadNumber = 0;
            } else {
                dispatch("call", {
                    url: 'approve/process/findTask',
                    data: {
                        page:1,
                        page_size: 500,
                    }
                }).then(({data}) => {
                    state.approveUnreadNumber = data.total || 0;
                }).catch(_ => {});
            }
        }, typeof timeout === "number" ? timeout : 1000)
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
                console.warn(e);
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
        return new Promise(async resolve => {
            const userInfo = $A.cloneJSON(info);
            userInfo.userid = $A.runNum(userInfo.userid);
            userInfo.token = userInfo.userid > 0 ? (userInfo.token || state.userToken) : '';
            state.userInfo = userInfo;
            state.userId = userInfo.userid;
            state.userToken = userInfo.token;
            state.userIsAdmin = $A.inArray('admin', userInfo.identity);
            await $A.IDBSet("userInfo", state.userInfo);
            //
            $A.eeuiAppSendMessage({
                action: 'userChatList',
                token: state.userToken,
                url: $A.apiUrl('../api/users/share/list') + `?token=${state.userToken}`
            });
            $A.eeuiAppSendMessage({
                action:"userUploadUrl",
                token: state.userToken,
                dirUrl: $A.apiUrl('../api/file/content/upload') + `?token=${state.userToken}`,
                chatUrl: $A.apiUrl('../api/dialog/msg/sendfiles') + `?token=${state.userToken}`,
            });
            //
            dispatch("getBasicData", null);
            if (state.userId > 0) {
                state.cacheUserBasic = state.cacheUserBasic.filter(({userid}) => userid !== state.userId)
                dispatch("saveUserBasic", state.userInfo);
            }
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
        if (state.loadUserBasic === true) {
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
        state.loadUserBasic = true;
        dispatch("call", {
            url: 'users/basic',
            data: {
                userid: [...new Set(array.map(({userid}) => userid))]
            },
            skipAuthError: true
        }).then(result => {
            time = $A.Time();
            array.forEach(value => {
                let data = result.data.find(({userid}) => userid == value.userid) || Object.assign(value, {email: ""});
                data._time = time;
                dispatch("saveUserBasic", data);
            });
            state.loadUserBasic = false;
            dispatch("getUserBasic");
        }).catch(e => {
            console.warn(e);
            state.loadUserBasic = false;
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
        const index = state.cacheUserBasic.findIndex(({userid}) => userid == data.userid);
        if (index > -1) {
            data = Object.assign({}, state.cacheUserBasic[index], data)
            state.cacheUserBasic.splice(index, 1, data);
        } else {
            state.cacheUserBasic.push(data)
        }
        state.cacheUserActive = Object.assign(data, {__:Math.random()});
        Store.set('cacheUserActive', data);
        //
        $A.IDBSave("cacheUserBasic", state.cacheUserBasic)
    },

    /**
     * 设置用户信息
     * @param dispatch
     * @param type
     * @returns {Promise<unknown>}
     */
    userEditInput({dispatch}, type) {
        return new Promise(function (userResolve, userReject) {
            let desc = '';
            if (type === 'nickname') {
                desc = '昵称';
            } else if (type === 'tel') {
                desc = '联系电话';
            } else {
                userReject('参数错误')
                return
            }
            setTimeout(_ => {
                $A.modalInput({
                    title: `设置${desc}`,
                    placeholder: `请输入您的${desc}`,
                    okText: "保存",
                    onOk: (value) => {
                        if (!value) {
                            return `请输入${desc}`
                        }
                        return new Promise((inResolve, inReject) => {
                            dispatch("call", {
                                url: 'users/editdata',
                                data: {
                                    [type]: value,
                                },
                                checkNick: false,
                                checkTel: false,
                            }).then(() => {
                                dispatch('getUserInfo').finally(_ => {
                                    inResolve()
                                    userResolve()
                                });
                            }).catch(({msg}) => {
                                inReject(msg)
                            });
                        })
                    },
                    onCancel: _ => userReject
                });
            }, 100)
        });
    },

    /**
     * 登出（打开登录页面）
     * @param state
     * @param dispatch
     * @param appendFrom
     */
    logout({state, dispatch}, appendFrom = true) {
        dispatch("handleClearCache", {}).then(() => {
            let from = ["/", "/login"].includes(window.location.pathname) ? "" : encodeURIComponent(window.location.href);
            if (appendFrom === false) {
                from = null;
            }
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
        return new Promise(async resolve => {
            try {
                // state
                state.cacheUserBasic = [];
                state.cacheDialogs = [];
                state.cacheProjects = [];
                state.cacheColumns = [];
                state.cacheTasks = [];
                state.callAt = [];

                // localStorage
                const languageType = window.localStorage.getItem("__language:type__");
                const keyboardData = window.localStorage.getItem("__keyboard:data__");
                const themeMode = window.localStorage.getItem("__theme:mode__");
                window.localStorage.clear();
                window.localStorage.setItem("__language:type__", languageType)
                window.localStorage.setItem("__keyboard:data__", keyboardData)
                window.localStorage.setItem("__theme:mode__", themeMode)

                // localForage
                const cacheLoginEmail = await $A.IDBString("cacheLoginEmail");
                const cacheFileSort = await $A.IDBJson("cacheFileSort");
                await $A.IDBClear();
                await $A.IDBSet("clientId", state.clientId);
                await $A.IDBSet("cacheServerUrl", state.cacheServerUrl);
                await $A.IDBSet("cacheProjectParameter", state.cacheProjectParameter);
                await $A.IDBSet("cacheLoginEmail", cacheLoginEmail);
                await $A.IDBSet("cacheFileSort", cacheFileSort);
                await $A.IDBSet("cacheTaskBrowse", state.cacheTaskBrowse);
                await $A.IDBSet("cacheEmojis", state.cacheEmojis);

                // userInfo
                dispatch("saveUserInfo", $A.isJson(userInfo) ? userInfo : state.userInfo).then(resolve);
            } catch (e) {
                resolve()
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
            let base = {_load: false, _edit: false};
            const index = state.fileLists.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.fileLists.splice(index, 1, Object.assign(base, state.fileLists[index], data));
            } else {
                state.fileLists.push(Object.assign(base, data))
            }
            $A.IDBSave("fileLists", state.fileLists, 600)
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
        const ids = $A.isArray(file_id) ? file_id : [file_id];
        ids.some(id => {
            state.fileLists = state.fileLists.filter(file => file.id != id);
            state.fileLists.some(file => {
                if (file.pid == id) {
                    dispatch("forgetFile", file.id);
                }
            });
            $A.IDBSave("fileLists", state.fileLists, 600)
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
                state.fileLists = state.fileLists.filter((item) => item.pid != pid || ids.includes(item.id));
                $A.IDBSave("fileLists", state.fileLists, 600)
                //
                dispatch("saveFile", result.data);
                resolve(result)
            }).catch(e => {
                console.warn(e);
                reject(e)
            });
        });
    },

    /**
     * 搜索文件
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    searchFiles({state, dispatch}, data) {
        if (!$A.isJson(data)) {
            data = {key: data}
        }
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'file/search',
                data,
            }).then((result) => {
                dispatch("saveFile", result.data);
                resolve(result)
            }).catch(e => {
                console.warn(e);
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
            const index = state.cacheProjects.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.cacheProjects.splice(index, 1, Object.assign({}, state.cacheProjects[index], data));
            } else {
                if (typeof data.project_user === "undefined") {
                    data.project_user = []
                }
                state.cacheProjects.push(data);
                state.projectTotal++
            }
            //
            state.cacheDialogs.some(dialog => {
                if (dialog.type == 'group' && dialog.group_type == 'project' && dialog.group_info.id == data.id) {
                    if (data.name !== undefined) {
                        dialog.name = data.name
                    }
                    for (let key in dialog.group_info) {
                        if (!dialog.group_info.hasOwnProperty(key) || data[key] === undefined) continue;
                        dialog.group_info[key] = data[key];
                    }
                }
            })
            //
            $A.IDBSave("cacheProjects", state.cacheProjects);
        }
    },

    /**
     * 忘记项目数据
     * @param state
     * @param dispatch
     * @param project_id
     */
    forgetProject({state, dispatch}, project_id) {
        $A.execMainDispatch("forgetProject", project_id)
        //
        const ids = $A.isArray(project_id) ? project_id : [project_id];
        ids.some(id => {
            const index = state.cacheProjects.findIndex(project => project.id == id);
            if (index > -1) {
                dispatch("forgetTask", state.cacheTasks.filter(item => item.project_id == project_id).map(item => item.id))
                dispatch("forgetColumn", state.cacheColumns.filter(item => item.project_id == project_id).map(item => item.id))
                state.cacheProjects.splice(index, 1);
                state.projectTotal = Math.max(0, state.projectTotal - 1)
            }
        })
        if (ids.includes(state.projectId)) {
            const project = $A.cloneJSON(state.cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            }).find(({id}) => id && id != project_id);
            if (project) {
                $A.goForward({name: 'manage-project', params: {projectId: project.id}});
            } else {
                $A.goForward({name: 'manage-dashboard'});
            }
        }
        //
        $A.IDBSave("cacheProjects", state.cacheProjects);
    },

    /**
     * 获取项目
     * @param state
     * @param dispatch
     * @param getters
     * @param requestData
     * @returns {Promise<unknown>}
     */
    getProjects({state, dispatch, getters}, requestData) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.cacheProjects = [];
                reject({msg: 'Parameter error'});
                return;
            }
            const callData = $callData('projects', requestData, state)
            //
            callData.showLoad() && state.loadProjects++;
            dispatch("call", {
                url: 'project/lists',
                data: callData.get()
            }).then(({data}) => {
                state.projectTotal = data.total_all;
                dispatch("saveProject", data.data);
                callData.save(data).then(ids => dispatch("forgetProject", ids))
                //
                resolve(data)
            }).catch(e => {
                console.warn(e);
                reject(e)
            }).finally(_ => {
                callData.showLoad() && state.loadProjects--;
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
                setTimeout(() => {
                    state.projectLoad--;
                }, 10)
                dispatch("saveProject", result.data);
                resolve(result)
            }).catch(e => {
                console.warn(e);
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
                console.warn(e);
                dispatch("getProjectOne", project_id).catch(() => {})
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
                console.warn(e);
                dispatch("getProjectOne", project_id).catch(() => {})
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
                console.warn(e);
                dispatch("getProjectOne", project_id).catch(() => {})
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
            const index = state.cacheColumns.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.cacheColumns.splice(index, 1, Object.assign({}, state.cacheColumns[index], data));
            } else {
                state.cacheColumns.push(data);
            }
            //
            $A.IDBSave("cacheColumns", state.cacheColumns);
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
        const ids = $A.isArray(column_id) ? column_id : [column_id];
        const project_ids = [];
        ids.some(id => {
            const index = state.cacheColumns.findIndex(column => column.id == id);
            if (index > -1) {
                dispatch("forgetTask", state.cacheTasks.filter(item => item.column_id == column_id).map(item => item.id))
                project_ids.push(state.cacheColumns[index].project_id)
                state.cacheColumns.splice(index, 1);
            }
        })
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id).catch(() => {}))
        //
        $A.IDBSave("cacheColumns", state.cacheColumns);
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
                state.cacheColumns = [];
                reject({msg: 'Parameter error'})
                return;
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
                state.cacheColumns = state.cacheColumns.filter((item) => item.project_id != project_id || ids.includes(item.id));
                //
                dispatch("saveColumn", data.data);
                resolve(data.data)
                // 判断只有1列的时候默认版面为表格模式
                if (state.cacheColumns.filter(item => item.project_id == project_id).length === 1) {
                    const cache = state.cacheProjectParameter.find(item => item.project_id == project_id) || {};
                    if (typeof cache.menuInit === "undefined" || cache.menuInit === false) {
                        dispatch("toggleProjectParameter", {
                            project_id,
                            key: {
                                menuInit: true,
                                menuType: 'table',
                            }
                        });
                    }
                }
            }).catch(e => {
                console.warn(e);
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
                console.warn(e);
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
            if (data.flow_item_name && data.flow_item_name.indexOf("|") !== -1) {
                [data.flow_item_status, data.flow_item_name] = data.flow_item_name.split("|")
            }
            //
            if (typeof data.archived_at !== "undefined") {
                state.cacheTasks.filter(task => task.parent_id == data.id).some(task => {
                    dispatch("saveTask", Object.assign(task, {
                        archived_at: data.archived_at,
                        archived_userid: data.archived_userid
                    }))
                })
            }
            //
            let updateMarking = {};
            if (typeof data.update_marking !== "undefined") {
                updateMarking = $A.isJson(data.update_marking) ? data.update_marking : {};
                delete data.update_marking;
            }
            //
            const index = state.cacheTasks.findIndex(({id}) => id == data.id);
            if (index > -1) {
                state.cacheTasks.splice(index, 1, Object.assign({}, state.cacheTasks[index], data));
            } else {
                state.cacheTasks.push(data);
            }
            //
            if (updateMarking.is_update_maintask === true || (data.parent_id > 0 && state.cacheTasks.findIndex(({id}) => id == data.parent_id) === -1)) {
                dispatch("getTaskOne", data.parent_id).catch(() => {})
            }
            if (updateMarking.is_update_project === true) {
                dispatch("getProjectOne", data.project_id).catch(() => {})
            }
            if (updateMarking.is_update_content === true) {
                dispatch("getTaskContent", data.id);
            }
            if (updateMarking.is_update_subtask === true) {
                dispatch("getTaskForParent", data.id).catch(() => {})
            }
            //
            state.cacheDialogs.some(dialog => {
                if (dialog.name === undefined || dialog.dialog_delete === 1) {
                    return false;
                }
                if (dialog.type == 'group' && dialog.group_type == 'task' && dialog.group_info.id == data.id) {
                    if (data.name !== undefined) {
                        dialog.name = data.name
                    }
                    for (let key in dialog.group_info) {
                        if (!dialog.group_info.hasOwnProperty(key) || data[key] === undefined) continue;
                        dialog.group_info[key] = data[key];
                    }
                }
            })
            //
            $A.IDBSave("cacheTasks", state.cacheTasks);
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
        const ids = ($A.isArray(task_id) ? task_id : [task_id]).filter(id => id != state.taskArchiveView);
        const parent_ids = [];
        const project_ids = [];
        ids.some(id => {
            const index = state.cacheTasks.findIndex(task => task.id == id);
            if (index > -1) {
                if (state.cacheTasks[index].parent_id) {
                    parent_ids.push(state.cacheTasks[index].parent_id)
                }
                project_ids.push(state.cacheTasks[index].project_id)
                state.cacheTasks.splice(index, 1);
            }
            state.cacheTasks.filter(task => task.parent_id == id).some(childTask => {
                let cIndex = state.cacheTasks.findIndex(task => task.id == childTask.id);
                if (cIndex > -1) {
                    project_ids.push(childTask.project_id)
                    state.cacheTasks.splice(cIndex, 1);
                }
            })
        })
        Array.from(new Set(parent_ids)).some(id => dispatch("getTaskOne", id).catch(() => {}))
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id).catch(() => {}))
        //
        if (ids.includes(state.taskId)) {
            state.taskId = 0;
        }
        //
        $A.IDBSave("cacheTasks", state.cacheTasks);
    },

    /**
     * 增加任务消息数量
     * @param state
     * @param data {id, dialog_id}
     */
    increaseTaskMsgNum({state}, data) {
        $A.execMainDispatch("increaseTaskMsgNum", data)
        //
        if ($A.execMainCacheJudge(`increaseTaskMsgNum:${data.id}`)) {
            return
        }
        //
        if (data.dialog_id) {
            const task = state.cacheTasks.find(({dialog_id}) => dialog_id === data.dialog_id);
            if (task) task.msg_num++;
        }
    },

    /**
     * 新增回复数量
     * @param state
     * @param dispatch
     * @param data {id, reply_id}
     */
    increaseMsgReplyNum({state, dispatch}, data) {
        $A.execMainDispatch("increaseMsgReplyNum", data)
        //
        if ($A.execMainCacheJudge(`increaseMsgReplyNum:${data.id}`)) {
            return
        }
        //
        if (data.reply_id > 0) {
            const msg = state.dialogMsgs.find(({id}) => id == data.reply_id)
            if (msg) msg.reply_num++;
        }
    },

    /**
     * 减少回复数量
     * @param state
     * @param dispatch
     * @param data {id, reply_id}
     */
    decrementMsgReplyNum({state, dispatch}, data) {
        $A.execMainDispatch("decrementMsgReplyNum", data)
        //
        if ($A.execMainCacheJudge(`decrementMsgReplyNum:${data.id}`)) {
            return
        }
        //
        if (data.reply_id > 0) {
            const msg = state.dialogMsgs.find(({id}) => id == data.reply_id)
            if (msg) msg.reply_num--;
        }
    },

    /**
     * 获取任务
     * @param state
     * @param dispatch
     * @param requestData
     * @returns {Promise<unknown>}
     */
    getTasks({state, dispatch}, requestData) {
        const taskData = [];
        if (requestData === null) {
            requestData = {}
        }
        if ($A.isArray(requestData.taskData)) {
            taskData.push(...requestData.taskData)
            delete requestData.taskData;
        }
        const callData = $callData('tasks', requestData, state)
        //
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.cacheTasks = [];
                reject({msg: 'Parameter error'});
                return;
            }
            if (requestData.project_id) {
                state.projectLoad++;
            }
            //
            dispatch("call", {
                url: 'project/task/lists',
                data: callData.get()
            }).then(({data}) => {
                if (requestData.project_id) {
                    state.projectLoad--;
                }
                taskData.push(...data.data);
                callData.save(data).then(ids => dispatch("forgetTask", ids))
                //
                if (data.next_page_url) {
                    requestData.page = data.current_page + 1
                    requestData.taskData = taskData
                    if (data.current_page % 10 === 0) {
                        $A.modalWarning({
                            content: "数据已超过" + data.to + "条，是否继续加载？",
                            onOk: () => {
                                dispatch("getTasks", requestData).then(resolve).catch(reject)
                            },
                            onCancel: () => {
                                dispatch("saveTask", taskData);
                                resolve()
                            }
                        });
                    } else {
                        dispatch("getTasks", requestData).then(resolve).catch(reject)
                    }
                } else {
                    dispatch("saveTask", taskData);
                    resolve()
                }
            }).catch(e => {
                console.warn(e);
                reject(e)
                if (requestData.project_id) {
                    state.projectLoad--;
                }
            });
        });
    },

    /**
     * 获取单个任务
     * @param state
     * @param dispatch
     * @param data Number|JSONObject{task_id, ?archived_at}
     * @returns {Promise<unknown>}
     */
    getTaskOne({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            if (/^\d+$/.test(data)) {
                data = {task_id: data}
            }
            if ($A.runNum(data.task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            //
            if ($A.isArray(state.taskOneLoad[data.task_id])) {
                state.taskOneLoad[data.task_id].push({resolve, reject})
                return;
            }
            state.taskOneLoad[data.task_id] = []
            //
            dispatch("call", {
                url: 'project/task/one',
                data,
            }).then(result => {
                dispatch("saveTask", result.data);
                resolve(result)
                state.taskOneLoad[data.task_id].some(item => {
                    item.resolve(result)
                })
            }).catch(e => {
                console.warn(e);
                reject(e)
                state.taskOneLoad[data.task_id].some(item => {
                    item.reject(e)
                })
            }).finally(_ => {
                delete state.taskOneLoad[data.task_id]
            });
        });
    },

    /**
     * 获取Dashboard相关任务
     * @param state
     * @param dispatch
     * @param getters
     * @param timeout
     */
    getTaskForDashboard({state, dispatch, getters}, timeout) {
        window.__getTaskForDashboard && clearTimeout(window.__getTaskForDashboard)
        if (typeof timeout === "number") {
            if (timeout > -1) {
                window.__getTaskForDashboard = setTimeout(_ => dispatch("getTaskForDashboard", null), timeout)
            }
            return;
        }
        //
        if (state.loadDashboardTasks === true) {
            return;
        }
        state.loadDashboardTasks = true;
        //
        dispatch("getTasks", null).finally(_ => {
            state.loadDashboardTasks = false;
        })
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
            dispatch("getTasks", {project_id}).then(resolve).catch(reject)
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
            dispatch("getTasks", {parent_id}).then(resolve).catch(reject)
        })
    },

    /**
     * 删除任务
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    removeTask({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(data.task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("setLoad", {
                key: `task-${data.task_id}`,
                delay: 300
            })
            dispatch("call", {
                url: 'project/task/remove',
                data,
            }).then(result => {
                dispatch("forgetTask", data.task_id)
                resolve(result)
            }).catch(e => {
                console.warn(e);
                dispatch("getTaskOne", data.task_id).catch(() => {})
                reject(e)
            }).finally(_ => {
                dispatch("cancelLoad", `task-${data.task_id}`)
            });
        });
    },

    /**
     * 归档（还原）任务
     * @param state
     * @param dispatch
     * @param data Number|JSONObject{task_id, ?archived_at}
     * @returns {Promise<unknown>}
     */
    archivedTask({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            if (/^\d+$/.test(data)) {
                data = {task_id: data}
            }
            if ($A.runNum(data.task_id) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("setLoad", {
                key: `task-${data.task_id}`,
                delay: 300
            })
            dispatch("call", {
                url: 'project/task/archived',
                data,
            }).then(result => {
                dispatch("saveTask", result.data)
                resolve(result)
            }).catch(e => {
                console.warn(e);
                dispatch("getTaskOne", data.task_id).catch(() => {})
                reject(e)
            }).finally(_ => {
                dispatch("cancelLoad", `task-${data.task_id}`)
            });
        });
    },

    /**
     * 获取任务详细描述
     * @param state
     * @param dispatch
     * @param task_id
     */
    getTaskContent({state, dispatch}, task_id) {
        if ($A.runNum(task_id) === 0) {
            return;
        }
        dispatch("call", {
            url: 'project/task/content',
            data: {
                task_id,
            },
        }).then(result => {
            dispatch("saveTaskContent", result.data)
        }).catch(e => {
            console.warn(e);
        });
    },

    /**
     * 更新任务详情
     * @param state
     * @param dispatch
     * @param data
     */
    saveTaskContent({state, dispatch}, data) {
        $A.execMainDispatch("saveTaskContent", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveTaskContent", item)
            });
        } else if ($A.isJson(data)) {
            const index = state.taskContents.findIndex(({task_id}) => task_id == data.task_id);
            if (index > -1) {
                state.taskContents.splice(index, 1, Object.assign({}, state.taskContents[index], data));
            } else {
                state.taskContents.push(data);
            }
        }
    },

    /**
     * 获取任务文件
     * @param state
     * @param dispatch
     * @param task_id
     */
    getTaskFiles({state, dispatch}, task_id) {
        if ($A.runNum(task_id) === 0) {
            return;
        }
        dispatch("call", {
            url: 'project/task/files',
            data: {
                task_id,
            },
        }).then(result => {
            result.data.forEach((data) => {
                const index = state.taskFiles.findIndex(({id}) => id == data.id)
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
        }).catch(e => {
            console.warn(e);
        });
    },

    /**
     * 忘记任务文件
     * @param state
     * @param dispatch
     * @param file_id
     */
    forgetTaskFile({state, dispatch}, file_id) {
        const ids = $A.isArray(file_id) ? file_id : [file_id];
        ids.some(id => {
            const index = state.taskFiles.findIndex(file => file.id == id)
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
        state.taskArchiveView = task_id;
        state.taskId = task_id;
        if (task_id > 0) {
            dispatch("getTaskOne", {
                task_id,
                archived: 'all'
            }).then(() => {
                dispatch("getTaskContent", task_id);
                dispatch("getTaskFiles", task_id);
                dispatch("getTaskForParent", task_id).catch(() => {});
                dispatch("saveTaskBrowse", task_id);
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
                if (result.data.is_visible === 1) {
                    dispatch("addTaskSuccess", result.data)
                }
                resolve(result)
            }).catch(e => {
                console.warn(e);
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
                console.warn(e);
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
        dispatch("getProjectOne", task.project_id).catch(() => {})
    },

    /**
     * 更新任务
     * @param state
     * @param dispatch
     * @param data {task_id, ?}
     * @returns {Promise<unknown>}
     */
    taskUpdate({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            dispatch("taskBeforeUpdate", data).then(({post}) => {
                dispatch("setLoad", {
                    key: `task-${post.task_id}`,
                    delay: 300
                })
                dispatch("call", {
                    url: 'project/task/update',
                    data: post,
                    method: 'post',
                }).then(result => {
                    dispatch("saveTask", result.data)
                    resolve(result)
                }).catch(e => {
                    console.warn(e);
                    dispatch("getTaskOne", post.task_id).catch(() => {})
                    reject(e)
                }).finally(_ => {
                    dispatch("cancelLoad", `task-${post.task_id}`)
                });
            }).catch(reject)
        });
    },

    /**
     * 更新任务之前判断
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    taskBeforeUpdate({state, dispatch}, data) {
        return new Promise(function (resolve, reject) {
            let post = $A.cloneJSON($A.date2string(data));
            let title = "温馨提示";
            let content = null;
            // 修改时间前置判断
            if (typeof post.times !== "undefined") {
                if (data.times[0] === false) {
                    content = "你确定要取消任务时间吗？"
                }
                const currentTask = state.cacheTasks.find(({id}) => id == post.task_id);
                title = currentTask.parent_id > 0 ? "更新子任务" : "更新主任务"
                if (currentTask) {
                    if (currentTask.parent_id > 0) {
                        // 修改子任务，判断主任务
                        if (post.times[0]) {
                            state.cacheTasks.some(parentTask => {
                                if (parentTask.id != currentTask.parent_id) {
                                    return false;
                                }
                                if (!parentTask.end_at) {
                                    content = "主任务没有设置时间，设置子任务将同步设置主任务"
                                    return true;
                                }
                                let n1 = $A.Date(post.times[0], true),
                                    n2 = $A.Date(post.times[1], true),
                                    o1 = $A.Date(parentTask.start_at, true),
                                    o2 = $A.Date(parentTask.end_at, true);
                                if (n1 < o1) {
                                    content = "新设置的子任务开始时间在主任务时间之外，修改后将同步修改主任务" // 子任务开始时间 < 主任务开始时间
                                    return true;
                                }
                                if (n2 > o2) {
                                    content = "新设置的子任务结束时间在主任务时间之外，修改后将同步修改主任务" // 子任务结束时间 > 主任务结束时间
                                    return true;
                                }
                            })
                        }
                    } else {
                        // 修改主任务，判断子任务
                        state.cacheTasks.some(subTask => {
                            if (subTask.parent_id != currentTask.id) {
                                return false;
                            }
                            if (!subTask.end_at) {
                                return false;
                            }
                            let n1 = $A.Date(post.times[0], true),
                                n2 = $A.Date(post.times[1], true),
                                c1 = $A.Date(currentTask.start_at, true),
                                c2 = $A.Date(currentTask.end_at, true),
                                o1 = $A.Date(subTask.start_at, true),
                                o2 = $A.Date(subTask.end_at, true);
                            if (c1 == o1 && c2 == o2) {
                                return false;
                            }
                            if (!post.times[0]) {
                                content = `子任务（${subTask.name}）已设置时间，清除主任务时间后将同步清除子任务的时间`
                                return true;
                            }
                            if (n1 > o1) {
                                content = `新设置的开始时间在子任务（${subTask.name}）时间之内，修改后将同步修改子任务` // 主任务开始时间 > 子任务开始时间
                                return true;
                            }
                            if (n2 < o2) {
                                content = `新设置的结束时间在子任务（${subTask.name}）时间之内，修改后将同步修改子任务` // 主任务结束时间 < 子任务结束时间
                                return true;
                            }
                        })
                    }
                }
            }
            //
            if (content === null) {
                resolve({
                    confirm: false,
                    post
                });
                return
            }
            $A.modalConfirm({
                title,
                content,
                onOk: () => {
                    resolve({
                        confirm: true,
                        post
                    });
                },
                onCancel: () => {
                    reject({msg: false})
                }
            });
        });
    },

    /**
     * 获取任务流程信息
     * @param state
     * @param dispatch
     * @param task_id
     * @returns {Promise<unknown>}
     */
    getTaskFlow({state, dispatch}, task_id) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/flow',
                data: {
                    task_id: task_id
                },
            }).then(result => {
                let task = state.cacheTasks.find(({id}) => id == task_id)
                let {data} = result
                data.turns.some(item => {
                    const index = state.taskFlowItems.findIndex(({id}) => id == item.id);
                    if (index > -1) {
                        state.taskFlowItems.splice(index, 1, item);
                    } else {
                        state.taskFlowItems.push(item);
                    }
                    if (task
                        && task.flow_item_id == item.id
                        && task.flow_item_name != item.name) {
                        state.cacheTasks.filter(({flow_item_id})=> flow_item_id == item.id).some(task => {
                            dispatch("saveTask", {
                                id: task.id,
                                flow_item_name: `${item.status}|${item.name}`,
                            })
                        })
                    }
                })
                //
                delete data.turns;
                const index = state.taskFlows.findIndex(({task_id}) => task_id == data.task_id);
                if (index > -1) {
                    state.taskFlows.splice(index, 1, data);
                } else {
                    state.taskFlows.push(data);
                }
                resolve(result)
            }).catch(e => {
                console.warn(e);
                reject(e);
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
                console.warn(e);
                reject(e);
            });
        });
    },

    /**
     * 获取添加项目列表预设数据
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    getColumnTemplate({state, dispatch}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'system/column/template',
            }).then(result => {
                state.columnTemplate = result.data;
                resolve(result)
            }).catch(e => {
                console.warn(e);
                reject(e);
            });
        });
    },

    /**
     * 保存完成任务临时表
     * @param state
     * @param task_id
     */
    saveTaskCompleteTemp({state}, task_id) {
        if (/^\d+$/.test(task_id) && !state.taskCompleteTemps.includes(task_id)) {
            state.taskCompleteTemps.push(task_id)
        }
    },

    /**
     * 忘记完成任务临时表
     * @param state
     * @param task_id 任务ID 或 true标识忘记全部
     */
    forgetTaskCompleteTemp({state}, task_id) {
        if (task_id === true) {
            state.taskCompleteTemps = [];
        } else if (/^\d+$/.test(task_id)) {
            state.taskCompleteTemps = state.taskCompleteTemps.filter(id => id != task_id);
        }
    },

    /**
     * 保存任务浏览记录
     * @param state
     * @param task_id
     */
    saveTaskBrowse({state}, task_id) {
        const index = state.cacheTaskBrowse.findIndex(({id}) => id == task_id)
        if (index > -1) {
            state.cacheTaskBrowse.splice(index, 1)
        }
        state.cacheTaskBrowse.unshift({
            id: task_id,
            userid: state.userId
        })
        if (state.cacheTaskBrowse.length > 200) {
            state.cacheTaskBrowse.splice(200);
        }
        //
        $A.IDBSave("cacheTaskBrowse", state.cacheTaskBrowse);
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
            const index = state.cacheDialogs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                const original = state.cacheDialogs[index]
                const nowTime = data.user_ms
                const originalTime = original.user_ms || 0
                if (nowTime < originalTime) {
                    typeof data.unread !== "undefined" && delete data.unread
                    typeof data.mention !== "undefined" && delete data.mention
                    typeof data.position_msgs !== "undefined" && delete data.position_msgs
                }
                state.cacheDialogs.splice(index, 1, Object.assign({}, original, data));
            } else {
                state.cacheDialogs.push(data);
            }
            //
            $A.IDBSave("cacheDialogs", state.cacheDialogs);
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
        if ($A.isArray(data)) {
            data.forEach((msg) => {
                dispatch("updateDialogLastMsg", msg)
            });
        } else if ($A.isJson(data)) {
            const index = state.cacheDialogs.findIndex(({id}) => id == data.dialog_id);
            if (index > -1) {
                const updateData = {
                    id: data.dialog_id,
                    last_msg: data,
                    last_at: $A.formatDate("Y-m-d H:i:s")
                }
                if (data.mtype == 'tag') {
                    updateData.has_tag = true;
                }
                if (data.mtype == 'image') {
                    updateData.has_image = true;
                }
                if (data.mtype == 'file') {
                    updateData.has_file = true;
                }
                if (data.link) {
                    updateData.has_link = true;
                }
                dispatch("saveDialog", updateData);
            } else {
                dispatch("getDialogOne", data.dialog_id).catch(() => {})
            }
        }
    },

    /**
     * 获取会话列表
     * @param state
     * @param dispatch
     * @param getters
     * @param requestData
     * @returns {Promise<unknown>}
     */
    getDialogs({state, dispatch, getters}, requestData) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                state.cacheDialogs = [];
                reject({msg: 'Parameter error'});
                return;
            }
            if (!$A.isJson(requestData)) {
                requestData = {}
            }
            if (typeof requestData.page === "undefined") {
                requestData.page = 1
            }
            if (typeof requestData.pagesize === "undefined") {
                requestData.pagesize = 20
            }
            const callData = $callData('dialogs', requestData, state)
            //
            callData.showLoad() && state.loadDialogs++;
            dispatch("call", {
                url: 'dialog/lists',
                data: callData.get()
            }).then(({data}) => {
                dispatch("saveDialog", data.data);
                callData.save(data).then(ids => dispatch("forgetDialog", ids))
                //
                if (data.next_page_url && data.current_page < 5) {
                    requestData.page++
                    dispatch("getDialogs", requestData).then(resolve).catch(reject)
                } else {
                    resolve()
                }
            }).catch(e => {
                console.warn(e);
                reject(e)
            }).finally(_ => {
                callData.showLoad() && state.loadDialogs--;
            });
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
                console.warn(e);
                reject(e);
            });
        });
    },

    /**
     * 获取会话待办
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogTodo({state, dispatch}, dialog_id) {
        dispatch("call", {
            url: 'dialog/todo',
            data: {
                dialog_id,
            },
        }).then(({data}) => {
            if ($A.arrayLength(data) > 0) {
                dispatch("saveDialog", {
                    id: dialog_id,
                    todo_num: $A.arrayLength(data)
                });
                state.dialogTodos = state.dialogTodos.filter(item => item.dialog_id != dialog_id)
                dispatch("saveDialogTodo", data)
            } else {
                dispatch("saveDialog", {
                    id: dialog_id,
                    todo_num: 0
                });
            }
        }).catch(console.warn);
    },

    /**
     * 打开会话
     * @param state
     * @param dispatch
     * @param dialog_id
     * @returns {Promise<unknown>}
     */
    openDialog({state, dispatch}, dialog_id) {
        return new Promise(resolve => {
            let search_msg_id;
            if ($A.isJson(dialog_id)) {
                search_msg_id = dialog_id.search_msg_id;
                dialog_id = dialog_id.dialog_id;
            }
            //
            requestAnimationFrame(_ => {
                state.dialogSearchMsgId = /^\d+$/.test(search_msg_id) ? search_msg_id : 0;
                state.dialogId = /^\d+$/.test(dialog_id) ? dialog_id : 0;
                resolve()
            })
        })
    },

    /**
     * 打开个人会话
     * @param state
     * @param dispatch
     * @param userid
     */
    openDialogUserid({state, dispatch}, userid) {
        return new Promise(function (resolve, reject) {
            const dialog = state.cacheDialogs.find(item => {
                if (item.type !== 'user' || !item.dialog_user) {
                    return false
                }
                return item.dialog_user.userid === userid
            });
            if (dialog) {
                dispatch("openDialog", dialog.id);
                resolve(dialog);
                return;
            }
            dispatch("showSpinner", 600)
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
            }).then(({data}) => {
                dispatch("saveDialog", data);
                dispatch("openDialog", data.id);
                resolve(data);
            }).catch(e => {
                console.warn(e);
                reject(e);
            }).finally(_ => {
                dispatch("hiddenSpinner")
            })
        });
    },

    /**
     * 忘记对话数据
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    forgetDialog({state, dispatch}, dialog_id) {
        $A.execMainDispatch("forgetDialog", dialog_id)
        //
        const ids = $A.isArray(dialog_id) ? dialog_id : [dialog_id];
        ids.some(id => {
            const index = state.cacheDialogs.findIndex(dialog => dialog.id == id);
            if (index > -1) {
                dispatch("forgetDialogMsg", state.dialogMsgs.filter(item => item.dialog_id == dialog_id).map(item => item.id))
                state.cacheDialogs.splice(index, 1);
            }
        })
        if (ids.includes(state.dialogId)) {
            state.dialogId = 0
        }
        //
        $A.IDBSave("cacheDialogs", state.cacheDialogs);
    },

    /**
     * 保存正在会话
     * @param state
     * @param dispatch
     * @param data {uid, dialog_id}
     */
    saveInDialog({state, dispatch}, data) {
        $A.execMainDispatch("saveInDialog", data)
        //
        const index = state.dialogIns.findIndex(item => item.uid == data.uid);
        if (index > -1) {
            state.dialogIns.splice(index, 1, Object.assign({}, state.dialogIns[index], data));
        } else {
            state.dialogIns.push(data);
        }
        // 会话消息总数量大于1500时只保留最近打开的30个会话
        const msg_max = 1500
        const retain_num = 30
        state.dialogHistory = state.dialogHistory.filter(id => id != data.dialog_id)
        state.dialogHistory.push(data.dialog_id)
        if (state.dialogMsgs.length > msg_max && state.dialogHistory.length > retain_num) {
            const historys = state.dialogHistory.slice().reverse()
            const newIds = []
            const delIds = []
            historys.forEach(id => {
                if (newIds.length < retain_num || state.dialogIns.findIndex(item => item.dialog_id == id) > -1) {
                    newIds.push(id)
                } else {
                    delIds.push(id)
                }
            })
            if (delIds.length > 0) {
                state.dialogMsgs = state.dialogMsgs.filter(item => !delIds.includes(item.dialog_id));
                $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
            }
            state.dialogHistory = newIds
        }
    },

    /**
     * 忘记正在会话
     * @param state
     * @param dispatch
     * @param uid
     */
    forgetInDialog({state, dispatch}, uid) {
        $A.execMainDispatch("forgetInDialog", uid)
        //
        const index = state.dialogIns.findIndex(item => item.uid == uid);
        if (index > -1) {
            state.dialogIns.splice(index, 1);
        }
    },

    /**
     * 关闭对话
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    closeDialog({state, dispatch}, dialog_id) {
        $A.execMainDispatch("closeDialog", dialog_id)
        //
        if (!/^\d+$/.test(dialog_id)) {
            return
        }
        // 更新草稿状态
        const dialog = state.cacheDialogs.find(item => item.id == dialog_id);
        if (dialog) {
            dialog.extra_draft_has = dialog.extra_draft_content ? 1 : 0
        }
        // 关闭会话后只保留会话最后50条数据
        const retain = 50
        const msgs = state.dialogMsgs.filter(item => item.dialog_id == dialog_id)
        if (msgs.length > retain) {
            const delIds = msgs.sort((a, b) => {
                return b.id - a.id
            }).splice(retain).map(item => item.id)
            state.dialogMsgs = state.dialogMsgs.filter(item => !delIds.includes(item.id))
            $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
        }
    },

    /**
     * 保存待办数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogTodo({state, dispatch}, data) {
        $A.execMainDispatch("saveDialogTodo", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveDialogTodo", item)
            });
        } else if ($A.isJson(data)) {
            const index = state.dialogTodos.findIndex(item => item.id == data.id);
            if (index > -1) {
                state.dialogTodos.splice(index, 1, Object.assign({}, state.dialogTodos[index], data));
            } else {
                state.dialogTodos.push(data);
            }
        }
    },

    /**
     * 忘记待办数据
     * @param state
     * @param dispatch
     * @param msg_id
     */
    forgetDialogTodoForMsgId({state, dispatch}, msg_id) {
        $A.execMainDispatch("forgetDialogTodoForMsgId", msg_id)
        //
        const index = state.dialogTodos.findIndex(item => item.msg_id == msg_id);
        if (index > -1) {
            state.dialogTodos.splice(index, 1);
        }
    },

    /**
     * 保存聊天草稿
     * @param state
     * @param dispatch
     * @param data {id, extra_draft_content}
     */
    saveDialogDraft({state, dispatch}, data) {
        state.dialogDraftTimer[data.id] && clearInterval(state.dialogDraftTimer[data.id])
        state.dialogDraftTimer[data.id] = setTimeout(_ => {
            if (state.dialogId != data.id) {
                data.extra_draft_has = data.extra_draft_content ? 1 : 0
            }
            dispatch("saveDialog", data)
        }, data.extra_draft_content ? 600 : 0)
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
            const index = state.dialogMsgs.findIndex(({id}) => id == data.id);
            data = Object.assign({}, state.dialogMsgs[index], data)
            if (index > -1) {
                state.dialogMsgs.splice(index, 1, data);
            } else {
                state.dialogMsgs.push(data);
            }
            $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
            //
            const dialog = state.cacheDialogs.find(({id, last_msg}) => id == data.dialog_id && last_msg && last_msg.id === data.id);
            if (dialog) {
                dispatch("saveDialog", {
                    id: data.dialog_id,
                    last_msg: Object.assign({}, dialog.last_msg, data),
                })
            }
        }
    },

    /**
     * 忘记消息数据
     * @param state
     * @param dispatch
     * @param msg_id
     */
    forgetDialogMsg({state, dispatch}, msg_id) {
        $A.execMainDispatch("forgetDialogMsg", msg_id)
        //
        const ids = $A.isArray(msg_id) ? msg_id : [msg_id];
        ids.some(id => {
            const index = state.dialogMsgs.findIndex(item => item.id == id);
            if (index > -1) {
                const msgData = state.dialogMsgs[index]
                dispatch("decrementMsgReplyNum", msgData);
                dispatch("audioStop", $A.getObject(msgData, 'msg.path'));
                state.dialogMsgs.splice(index, 1);
                $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
            }
        })
        dispatch("forgetDialogTodoForMsgId", msg_id)
    },

    /**
     * 获取会话消息
     * @param state
     * @param dispatch
     * @param getters
     * @param data {dialog_id, msg_id, ?msg_type, ?position_id, ?prev_id, ?next_id, ?save_before, ?save_after, ?clear_before, ?spinner}
     * @returns {Promise<unknown>}
     */
    getDialogMsgs({state, dispatch, getters}, data) {
        return new Promise((resolve, reject) => {
            let saveBefore = _ => {}
            let saveAfter = _ => {}
            let clearBefore = false
            let spinner = false
            if (typeof data.save_before !== "undefined") {
                saveBefore = typeof data.save_before === "function" ? data.save_before : _ => {}
                delete data.save_before
            }
            if (typeof data.save_after !== "undefined") {
                saveAfter = typeof data.save_after === "function" ? data.save_after : _ => {}
                delete data.save_after
            }
            if (typeof data.clear_before !== "undefined") {
                clearBefore = typeof data.clear_before === "boolean" ? data.clear_before : false
                delete data.clear_before
            }
            if (typeof data.spinner !== "undefined") {
                spinner = data.spinner
                delete data.spinner
            }
            //
            const loadKey = `msg::${data.dialog_id}-${data.msg_id}-${data.msg_type || ''}`
            if (getters.isLoad(loadKey)) {
                reject({msg: 'Loading'});
                return
            }
            dispatch("setLoad", loadKey)
            //
            if (clearBefore) {
                state.dialogMsgs = state.dialogMsgs.filter(({dialog_id}) => dialog_id !== data.dialog_id)
                $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
            }
            //
            dispatch("call", {
                url: 'dialog/msg/list',
                data,
                spinner,
                complete: _ => dispatch("cancelLoad", loadKey)
            }).then(result => {
                saveBefore()
                const resData = result.data;
                if ($A.isJson(resData.dialog)) {
                    setTimeout(_ => dispatch("saveDialog", resData.dialog), 10)    // 延迟更新对话详情是因为等消息处理完
                    //
                    const ids = resData.list.map(({id}) => id)
                    state.dialogMsgs = state.dialogMsgs.filter(item => {
                        return item.dialog_id != data.dialog_id || ids.includes(item.id) || $A.Time(item.created_at) >= resData.time
                    });
                    $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
                }
                if ($A.isArray(resData.todo)) {
                    state.dialogTodos = state.dialogTodos.filter(item => item.dialog_id != data.dialog_id)
                    dispatch("saveDialogTodo", resData.todo)
                }
                //
                dispatch("saveDialogMsg", resData.list)
                resolve(result)
                saveAfter()
            }).catch(e => {
                console.warn(e);
                reject(e)
            }).finally(_ => {
                // 将原数据清除，避免死循环
                if (data.prev_id) {
                    const prevMsg = state.dialogMsgs.find(({prev_id}) => prev_id == data.prev_id)
                    if (prevMsg) {
                        prevMsg.prev_id = 0
                    }
                }
                if (data.next_id) {
                    const nextMsg = state.dialogMsgs.find(({next_id}) => next_id == data.next_id)
                    if (nextMsg) {
                        nextMsg.next_id = 0
                    }
                }
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
        if ($A.isJson(data)) {
            if (data.userid == state.userId) return;
            if (data.read_at) return;
            data.read_at = $A.formatDate();
            state.wsReadWaitData[data.id] = data.id;
        }
        clearTimeout(state.wsReadTimeout);
        state.wsReadTimeout = setTimeout(_ => {
            const ids = Object.values(state.wsReadWaitData);
            state.wsReadWaitData = {};
            if (ids.length === 0) {
                return
            }
            //
            dispatch("call", {
                url: 'dialog/msg/read',
                data: {
                    id: ids.join(",")
                }
            }).then(({data}) => {
                dispatch("saveDialog", data)
            }).catch(_ => {
                ids.some(id => {
                    state.wsReadWaitData[id] = id;
                })
            });
        }, 50);
    },

    /**
     * 标记已读、未读
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    dialogMsgMark({state, dispatch}, data) {
        return new Promise((resolve, reject) => {
            dispatch("call", {
                url: 'dialog/msg/mark',
                data,
            }).then(result => {
                dispatch("saveDialog", result.data)
                resolve(result)
            }).catch(e => {
                reject(e)
            })
        })
    },

    /** *****************************************************************************************/
    /** ************************************* loads *********************************************/
    /** *****************************************************************************************/

    /**
     * 设置等待
     * @param state
     * @param dispatch
     * @param key
     */
    setLoad({state, dispatch}, key) {
        if ($A.isJson(key)) {
            setTimeout(_ => {
                dispatch("setLoad", key.key)
            }, key.delay || 0)
            return;
        }
        const load = state.loads.find(item => item.key == key)
        if (!load) {
            state.loads.push({key, num: 1})
        } else {
            load.num++;
        }
    },

    /**
     * 取消等待
     * @param state
     * @param key
     */
    cancelLoad({state}, key) {
        const load = state.loads.find(item => item.key == key)
        if (!load) {
            state.loads.push({key, num: -1})
        } else {
            load.num--;
        }
    },

    /**
     * 显示全局浮窗加载器
     * @param state
     * @param delay
     */
    showSpinner({state}, delay) {
        const id = $A.randomString(6)
        state.floatSpinnerTimer.push({
            id,
            timer: setTimeout(_ => {
                state.floatSpinnerTimer = state.floatSpinnerTimer.filter(item => item.id !== id)
                state.floatSpinnerLoad++
            }, typeof delay === "number" ? delay : 0)
        })
    },

    /**
     * 隐藏全局浮窗加载器
     * @param state
     */
    hiddenSpinner({state}) {
        const item = state.floatSpinnerTimer.shift()
        if (item) {
            clearTimeout(item.timer)
        } else {
            state.floatSpinnerLoad--
        }
    },

    /**
     * 预览图片
     * @param state
     * @param data
     */
    previewImage({state}, data) {
        if (!$A.isJson(data)) {
            data = {index:0, list: [data]}
        }
        state.previewImageIndex = data.index;
        state.previewImageList = data.list;
    },

    /**
     * 播放音频
     * @param state
     * @param dispatch
     * @param src
     */
    audioPlay({state, dispatch}, src) {
        const old = document.getElementById("__audio_play_element__")
        if (old) {
            // 删除已存在
            old.pause()
            old.src = null
            old.parentNode.removeChild(old);
        }
        if (!src || src === state.audioPlaying) {
            // 空地址或跟现在播放的地址一致时仅停止
            state.audioPlaying = null
            return
        }
        //
        const audio = document.createElement("audio")
        audio.id = state.audioPlayId = "__audio_play_element__"
        audio.controls = false
        audio.loop = false
        audio.volume = 1
        audio.src = state.audioPlaying = src
        audio.onended = _ => {
            dispatch("audioStop", audio.src)
        }
        document.body.appendChild(audio)
        audio.play().then(_ => {})
    },

    /**
     * 停止播放音频
     * @param state
     * @param src
     */
    audioStop({state}, src) {
        const old = document.getElementById("__audio_play_element__")
        if (!old) {
            return
        }
        if (old.src === src || src === true) {
            old.pause()
            old.src = null
            old.parentNode.removeChild(old);
            state.audioPlaying = null
        }
    },

    /** *****************************************************************************************/
    /** *********************************** websocket *******************************************/
    /** *****************************************************************************************/

    /**
     * 初始化 websocket
     * @param state
     * @param dispatch
     * @param commit
     */
    websocketConnection({state, dispatch, commit}) {
        clearTimeout(state.wsTimeout);
        if (state.ws) {
            state.ws.close();
            state.ws = null;
        }
        if (state.userId === 0) {
            return;
        }
        //
        let url = $A.apiUrl('../ws');
        url = url.replace("https://", "wss://");
        url = url.replace("http://", "ws://");
        url += `?action=web&token=${state.userToken}&language=${languageType}`;
        //
        const wgLog = $A.openLog;
        const wsRandom = $A.randomString(16);
        state.wsRandom = wsRandom;
        //
        state.ws = new WebSocket(url);
        state.ws.onopen = async (e) => {
            wgLog && console.log("[WS] Open", e, $A.formatDate())
            state.wsOpenNum++;
            //
            dispatch("websocketSend", {
                type: 'encrypt',
                data: {
                    type: 'pgp',
                    key: (await dispatch("pgpGetLocalKey")).publicKeyB64
                }
            })
        };
        state.ws.onclose = async (e) => {
            wgLog && console.log("[WS] Close", e, $A.formatDate())
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                wsRandom === state.wsRandom && dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onerror = async (e) => {
            wgLog && console.log("[WS] Error", e, $A.formatDate())
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                wsRandom === state.wsRandom && dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onmessage = async (e) => {
            wgLog && console.log("[WS] Message", e);
            let result = $A.jsonParse(e.data);
            if (result.type === "encrypt" && result.encrypted) {
                result = await dispatch("pgpDecryptApi", result.encrypted)
            }
            const msgDetail = $A.formatMsgBasic(result);
            const {type, msgId} = msgDetail;
            switch (type) {
                case "open":
                    $A.setSessionStorage("userWsFd", msgDetail.data.fd)
                    break

                case "receipt":
                    typeof state.wsCall[msgId] === "function" && state.wsCall[msgId](msgDetail.body, true);
                    delete state.wsCall[msgId];
                    break

                case "line":
                    dispatch("saveUserOnlineStatus", msgDetail.data);
                    break

                default:
                    msgId && dispatch("websocketSend", {type: 'receipt', msgId}).catch(_ => {});
                    state.wsMsg = msgDetail;
                    Object.values(state.wsListener).forEach((call) => {
                        if (typeof call === "function") {
                            try {
                                call(msgDetail);
                            } catch (err) {
                                wgLog && console.log("[WS] Callerr", err);
                            }
                        }
                    });
                    switch (type) {
                        /**
                         * 聊天会话消息
                         */
                        case "dialog": // 更新会话
                            (function (msg) {
                                const {mode, silence, data} = msg;
                                const {dialog_id} = data;
                                switch (mode) {
                                    case 'delete':
                                        // 删除消息
                                        dispatch("forgetDialogMsg", data.id)
                                        //
                                        const dialog = state.cacheDialogs.find(({id}) => id == dialog_id);
                                        if (dialog) {
                                            // 更新最后消息
                                            const newData = {
                                                id: dialog_id,
                                                last_at: data.last_msg && data.last_msg.created_at,
                                                last_msg: data.last_msg,
                                            }
                                            if (data.update_read) {
                                                // 更新未读数量
                                                dispatch("call", {
                                                    url: 'dialog/msg/unread',
                                                    data: {dialog_id}
                                                }).then(({data}) => {
                                                    dispatch("saveDialog", Object.assign(newData, data))
                                                }).catch(() => {});
                                            } else {
                                                dispatch("saveDialog", newData)
                                            }
                                        }
                                        break;
                                    case 'add':
                                    case 'chat':
                                        if (!state.dialogMsgs.find(({id}) => id == data.id)) {
                                            // 新增任务消息数量
                                            dispatch("increaseTaskMsgNum", data);
                                            // 新增回复数量
                                            dispatch("increaseMsgReplyNum", data);
                                            //
                                            if (mode === "chat" || $A.isSubElectron) {
                                                return;
                                            }
                                            if (data.userid !== state.userId) {
                                                // 更新对话新增未读数
                                                const dialog = state.cacheDialogs.find(({id}) => id == dialog_id);
                                                if (dialog) {
                                                    const newData = {
                                                        id: dialog_id,
                                                        unread: dialog.unread + 1,
                                                        mention: dialog.mention,
                                                        user_at: data.user_at,
                                                        user_ms: data.user_ms,
                                                    }
                                                    if (data.mention) {
                                                        newData.mention++;
                                                    }
                                                    dispatch("saveDialog", newData)
                                                }
                                            }
                                            if (!silence) {
                                                Store.set('dialogMsgPush', data);
                                            }
                                        }
                                        // 更新消息列表
                                        dispatch("saveDialogMsg", data)
                                        // 更新最后消息
                                        dispatch("updateDialogLastMsg", data);
                                        break;
                                    case 'update':
                                    case 'readed':
                                        // 更新、已读回执
                                        if (state.dialogMsgs.find(({id}) => id == data.id)) {
                                            dispatch("saveDialogMsg", data)
                                            // 更新待办
                                            if (typeof data.todo !== "undefined") {
                                                dispatch("getDialogTodo", dialog_id)
                                            }
                                        } else if (mode === 'readed') {
                                            // 消息不存在，重试已读标记
                                            let readedNum = 0
                                            const readedTimer = setInterval(_ => {
                                                if (readedNum > 6) {
                                                    clearInterval(readedTimer)
                                                }
                                                if (state.dialogMsgs.find(({id}) => id == data.id)) {
                                                    clearInterval(readedTimer)
                                                    dispatch("saveDialogMsg", data)
                                                }
                                                readedNum++
                                            }, 500)
                                        }
                                        break;
                                    case 'groupAdd':
                                    case 'groupJoin':
                                    case 'groupRestore':
                                        // 群组添加、加入、恢复
                                        dispatch("getDialogOne", data.id).catch(() => {})
                                        break;
                                    case 'groupUpdate':
                                        // 群组更新
                                        if (state.cacheDialogs.find(({id}) => id == data.id)) {
                                            dispatch("saveDialog", data)
                                        }
                                        break;
                                    case 'groupExit':
                                    case 'groupDelete':
                                        // 群组退出、解散
                                        dispatch("forgetDialog", data.id)
                                        break;
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
                                    case 'recovery':
                                        dispatch("saveProject", data)
                                        break;
                                    case 'detail':
                                        dispatch("getProjectOne", data.id).catch(() => {})
                                        dispatch("getTaskForProject", data.id).catch(() => {})
                                        break;
                                    case 'delete':
                                    case 'archived':
                                        dispatch("forgetProject", data.id);
                                        break;
                                    case 'sort':
                                        dispatch("getTaskForProject", data.id).catch(() => {})
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
                                    case 'restore':     // 恢复（删除）
                                        dispatch("addTaskSuccess", data)
                                        break;
                                    case 'update':
                                    case 'archived':    // 归档
                                    case 'recovery':    // 恢复（归档）
                                        dispatch("saveTask", data)
                                        break;
                                    case 'dialog':
                                        dispatch("saveTask", data)
                                        dispatch("getDialogOne", data.dialog_id).catch(() => {})
                                        break;
                                    case 'upload':
                                        dispatch("getTaskFiles", data.task_id)
                                        break;
                                    case 'filedelete':
                                        dispatch("forgetTaskFile", data.id)
                                        break;
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
     * @returns {Promise<unknown>}
     */
    websocketSend({state}, params) {
        return new Promise((resolve, reject) => {
            if (!$A.isJson(params)) {
                reject()
                return
            }
            const {type, data, callback} = params
            let msgId = undefined
            if (!state.ws) {
                typeof callback === "function" && callback(null, false)
                reject()
                return
            }
            if (typeof callback === "function") {
                msgId = $A.randomString(16)
                state.wsCall[msgId] = callback
            }
            try {
                state.ws?.send(JSON.stringify({type, msgId, data}))
                resolve()
            } catch (e) {
                typeof callback === "function" && callback(null, false)
                reject(e)
            }
        })
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
                dispatch("websocketSend", {type: 'path', data: {path}}).catch(_ => {});
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
        if (state.ws) {
            state.ws.close();
            state.ws = null;
        }
    },

    /** *****************************************************************************************/
    /** *************************************** pgp *********************************************/
    /** *****************************************************************************************/

    /**
     * 创建密钥对
     * @param state
     * @returns {Promise<unknown>}
     */
    pgpGenerate({state}) {
        return new Promise(async resolve => {
            const data = await openpgp.generateKey({
                type: 'ecc',
                curve: 'curve25519',
                passphrase: state.clientId,
                userIDs: [{name: 'doo', email: 'admin@admin.com'}],
            })
            data.publicKeyB64 = $urlSafe(data.publicKey.replace(/\s*-----(BEGIN|END) PGP PUBLIC KEY BLOCK-----\s*/g, ''))
            resolve(data)
        })
    },

    /**
     * 获取密钥对（不存在自动创建）
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    pgpGetLocalKey({state, dispatch}) {
        return new Promise(async resolve => {
            // 已存在
            if (state.localKeyPair.privateKey) {
                return resolve(state.localKeyPair)
            }
            // 避免重复生成
            while (state.localKeyLock === true) {
                await new Promise(r => setTimeout(r, 100));
            }
            if (state.localKeyPair.privateKey) {
                return resolve(state.localKeyPair)
            }
            // 生成密钥对
            state.localKeyLock = true
            state.localKeyPair = await dispatch("pgpGenerate")
            state.localKeyLock = false
            resolve(state.localKeyPair)
        })
    },

    /**
     * 加密
     * @param state
     * @param dispatch
     * @param data {message:any, ?publicKey:string}
     * @returns {Promise<unknown>}
     */
    pgpEncrypt({state, dispatch}, data) {
        return new Promise(async resolve => {
            if (!$A.isJson(data)) {
                data = {message: data}
            }
            const message = data.message || data.text
            const publicKeyArmored = data.publicKey || data.key || (await dispatch("pgpGetLocalKey")).publicKey
            const encryptionKeys = await openpgp.readKey({armoredKey: publicKeyArmored})
            //
            const encrypted = await openpgp.encrypt({
                message: await openpgp.createMessage({text: message}),
                encryptionKeys,
            })
            resolve(encrypted)
        })
    },

    /**
     * 解密
     * @param state
     * @param dispatch
     * @param data {encrypted:any, ?privateKey:string, ?passphrase:string}
     * @returns {Promise<unknown>}
     */
    pgpDecrypt({state, dispatch}, data) {
        return new Promise(async resolve => {
            if (!$A.isJson(data)) {
                data = {encrypted: data}
            }
            const encrypted = data.encrypted || data.text
            const privateKeyArmored = data.privateKey || data.key || (await dispatch("pgpGetLocalKey")).privateKey
            const decryptionKeys = await openpgp.decryptKey({
                privateKey: await openpgp.readPrivateKey({armoredKey: privateKeyArmored}),
                passphrase: data.passphrase || state.clientId
            })
            //
            const {data: decryptData} = await openpgp.decrypt({
                message: await openpgp.readMessage({armoredMessage: encrypted}),
                decryptionKeys
            })
            resolve(decryptData)
        })
    },

    /**
     * API加密
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    pgpEncryptApi({state, dispatch}, data) {
        return new Promise(resolve => {
            data = $A.jsonStringify(data)
            dispatch("pgpEncrypt", {
                message: data,
                publicKey: state.apiKeyData.key
            }).then(data => {
                resolve(data.replace(/\s*-----(BEGIN|END) PGP MESSAGE-----\s*/g, ''))
            })
        })
    },

    /**
     * API解密
     * @param state
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    pgpDecryptApi({state, dispatch}, data) {
        return new Promise(resolve => {
            dispatch("pgpDecrypt", {
                encrypted: "-----BEGIN PGP MESSAGE-----\n\n" + data + "\n-----END PGP MESSAGE-----"
            }).then(data => {
                resolve($A.jsonParse(data))
            })
        })
    }
}
