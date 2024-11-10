import {Store} from 'le5le-store';
import * as openpgp from 'openpgp_hi/lightweight';
import {initLanguage, languageList, languageName} from "../language";
import {$callData, $urlSafe, SSEClient} from './utils'

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

            // 清理缓存、读取缓存
            const clearCache = await $A.IDBString("clearCache")
            if (clearCache) {
                if (clearCache === "handle") {
                    action = "handleClearCache"
                }
                await $A.IDBRemove("clearCache")
                await $A.IDBSet("cacheVersion", "clear")
            }
            const cacheVersion = await $A.IDBString("cacheVersion")
            if (cacheVersion && cacheVersion !== state.cacheVersion) {
                await dispatch("handleClearCache")
            } else {
                await dispatch("handleReadCache")
            }

            // 主题皮肤
            await dispatch("synchTheme")

            // Keyboard
            await dispatch("handleKeyboard")

            // 客户端ID
            if (!state.clientId) {
                state.clientId = $A.randomString(6)
                await $A.IDBSet("clientId", state.clientId)
            }

            // 获取apiKey
            dispatch("call", {
                url: "users/key/client",
                data: {client_id: state.clientId},
                encrypt: false,
            }).then(({data}) => {
                state.apiKeyData = data;
            })

            // 获取系统设置
            dispatch("systemSetting")

            // 加载语言包
            await $A.loadScriptS([
                `language/web/key.js`,
                `language/web/${languageName}.js`,
                `language/iview/${languageName}.js`,
            ])
            initLanguage()

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
            'language': languageName,
            'token': state.userToken,
            'fd': $A.getSessionStorageString("userWsFd"),
            'version': window.systemInfo.version || "0.0.1",
            'platform': $A.Platform,
        }
        if (!state.userToken && state.meetingWindow?.meetingSharekey) {
            header.sharekey = state.meetingWindow.meetingSharekey;
        }
        if ($A.isJson(params.header)) {
            params.header = Object.assign(header, params.header)
        } else {
            params.header = header
        }
        if (state.systemConfig.e2e_message === 'open'
            && params.encrypt === undefined
            && $A.inArray(params.url, [
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
            if (window.systemInfo.debug === "yes"
                || /Windows NT 5.1|Windows XP/.test(userAgent)
                || userAgent.indexOf("Windows NT 6.0") !== -1
                || userAgent.indexOf("Windows NT 6.1") !== -1
                || userAgent.indexOf("Windows NT 6.2") !== -1) {
                params.encrypt = false  // 是 Windows Xp, Vista, 7, 8 系统，不支持加密
            }
        }
        params.url = $A.apiUrl(params.url)
        params.data = $A.newDateString(params.data)
        //
        const cloneParams = $A.cloneJSON(params)
        return new Promise(async (resolve, reject) => {
            // 判断服务器地址
            if (/^https*:\/\/public\//.test(params.url)) {
                reject({ret: -1, data: {}, msg: "No server address"})
                return
            }
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
                    reject({ret: -1, data: {}, msg: $A.L('返回参数错误')})
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
                    reject({ret, data, msg: msg || $A.L('未知错误')})
                    //
                    if (ret === -4001) {
                        dispatch("forgetProject", data.project_id)
                    } else if (ret === -4002) {
                        if (data.force === 1) {
                            state.taskArchiveView = 0
                        }
                        dispatch("forgetTask", data.task_id)
                    } else if (ret === -4003) {
                        dispatch("forgetDialog", data.dialog_id)
                    } else if (ret === -4004) {
                        dispatch("getTaskForParent", data.task_id).catch(() => {})
                    }
                }
            }
            params.error = (xhr, status) => {
                const networkException = window.navigator.onLine === false || (status === 0 && xhr.readyState === 4)
                if (networkException
                    && cloneParams.method !== "post"
                    && cloneParams.__networkFailureRetry !== true) {
                    // 网络异常，重试一次
                    setTimeout(_ => {
                        cloneParams.__networkFailureRetry = true
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }, 1000)
                    return
                }
                if (params.checkNetwork !== false) {
                    state.ajaxNetworkException = networkException
                }
                if (networkException) {
                    reject({ret: -1001, data: {}, msg: $A.L('网络异常，请重试。')})
                } else {
                    reject({ret: -1, data: {}, msg: $A.L('请求失败，请重试。')})
                }
                console.error(xhr, status);
            }
            //
            $A.ajaxc(params)
        })
    },

    /**
     * 取消请求
     * @param state
     * @param requestId
     * @returns {Promise<unknown>}
     */
    callCancel({state}, requestId) {
        return new Promise((resolve, reject) => {
            if ($A.ajaxcCancel(requestId)) {
                resolve()
            } else {
                reject()
            }
        })
    },

    /**
     * 获取系统设置
     * @param dispatch
     * @param state
     * @returns {Promise<unknown>}
     */
    systemSetting({dispatch, state}) {
        return new Promise((resolve, reject) => {
            switch (state.systemConfig.__state) {
                case "success":
                    resolve(state.systemConfig)
                    break

                case "loading":
                    setTimeout(_ => {
                        dispatch("systemSetting").then(resolve).catch(reject)
                    }, 100)
                    break

                default:
                    state.systemConfig.__state = "loading"
                    dispatch("call", {
                        url: "system/setting",
                    }).then(({data}) => {
                        state.systemConfig = Object.assign(data, {
                            timezoneDifference: $A.updateTimezone(data.server_timezone),
                            __state: "success",
                        })
                        resolve(state.systemConfig)
                    }).catch(_ => {
                        state.systemConfig.__state = "error"
                        reject()
                    });
                    break
            }
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
            if ($A.isSoftware) {
                reject()
                return
            }
            dispatch("systemSetting").then(data => {
                if (data.start_home === 'open') {
                    resolve()
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
     * 显示文件（打开文件所在位置）
     * @param state
     * @param dispatch
     * @param params
     */
    filePos({state, dispatch}, params) {
        if ($A.isSubElectron) {
            $A.execMainDispatch("filePos", params)
            $A.Electron.sendMessage('mainWindowActive');
            return
        }
        dispatch('openTask', 0)
        if (state.windowPortrait) {
            dispatch("openDialog", 0);
        }
        $A.goForward({name: 'manage-file', params});
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
     * @param dispatch
     * @param mode
     * @returns {Promise<unknown>}
     */
    setTheme({state, dispatch}, mode) {
        return new Promise(function (resolve) {
            if (mode === undefined) {
                resolve(false)
                return;
            }
            if (!$A.dark.utils.supportMode()) {
                if ($A.isEEUiApp) {
                    $A.modalWarning("仅Android设置支持主题功能");
                } else {
                    $A.modalWarning("仅客户端或Chrome浏览器支持主题功能");
                }
                resolve(false)
                return;
            }
            dispatch("synchTheme", mode)
            resolve(true)
        });
    },

    /**
     * 同步主题
     * @param state
     * @param dispatch
     * @param mode
     */
    synchTheme({state, dispatch}, mode = undefined) {
        if (typeof mode === "undefined") {
            mode = state.themeConf
        } else {
            state.themeConf = mode
        }
        switch (mode) {
            case 'dark':
                $A.dark.enableDarkMode()
                break;
            case 'light':
                $A.dark.disableDarkMode()
                break;
            default:
                state.themeConf = "auto"
                $A.dark.autoDarkMode()
                break;
        }
        state.themeName = $A.dark.isDarkEnabled() ? 'dark' : 'light'
        window.localStorage.setItem("__system:themeConf__", state.themeConf)
        //
        if ($A.isEEUiApp) {
            $A.eeuiAppSendMessage({
                action: 'updateTheme',
                themeName: state.themeName,
            });
        }
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
        const tmpKey = state.userId + $A.dayjs().unix()
        if (window.__getBasicDataKey === tmpKey) {
            return
        }
        window.__getBasicDataKey = tmpKey
        //
        dispatch("getProjects").catch(() => {});
        dispatch("getDialogAuto").catch(() => {});
        dispatch("getDialogTodo", 0).catch(() => {});
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
                    url: 'approve/process/doto'
                }).then(({data}) => {
                    state.approveUnreadNumber = data.total || 0;
                }).catch(({msg}) => {
                    if( msg.indexOf("404 not found") !== -1){
                        $A.modalInfo({
                            title: '版本过低',
                            content: '服务器版本过低，请升级服务器。',
                        })
                    }
                });
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
    saveUserInfoBase({state, dispatch}, info) {
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
                url: $A.mainUrl('api/users/share/list') + `?token=${state.userToken}`
            });
            $A.eeuiAppSendMessage({
                action:"userUploadUrl",
                token: state.userToken,
                dirUrl: $A.mainUrl('api/file/content/upload') + `?token=${state.userToken}`,
                chatUrl: $A.mainUrl('api/dialog/msg/sendfiles') + `?token=${state.userToken}`,
            });
            //
            resolve()
        })
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
            await dispatch("saveUserInfoBase", info);
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
        let time = $A.dayjs().unix();
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
                    Store.set('userActive', {type: 'cache', data: temp});
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
            time = $A.dayjs().unix();
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
        Store.set('userActive', {type: 'cache', data});
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
     * 处理快捷键配置
     * @param state
     * @param newData
     * @returns {Promise<unknown>}
     */
    handleKeyboard({state}, newData) {
        return new Promise(resolve => {
            if (!window.localStorage.getItem("__system:keyboardConf__")) {
                window.localStorage.setItem("__system:keyboardConf__", window.localStorage.getItem("__keyboard:data__"))
                window.localStorage.removeItem("__keyboard:data__")
            }
            const data = $A.isJson(newData) ? newData : ($A.jsonParse(window.localStorage.getItem("__system:keyboardConf__")) || {})
            data.screenshot_key = (data.screenshot_key || "").trim().toLowerCase()
            data.send_button_app = data.send_button_app || 'enter'         // button, enter 移动端发送按钮，默认 enter （键盘回车发送）
            data.send_button_desktop = data.send_button_desktop || 'enter'  // button, enter 桌面端发送按钮，默认 enter （键盘回车发送）
            window.localStorage.setItem("__system:keyboardConf__", $A.jsonStringify(data))
            state.cacheKeyboard = data
            resolve(data)
        })
    },

    /**
     * 清除缓存
     * @param state
     * @param dispatch
     * @param userData
     * @returns {Promise<unknown>}
     */
    handleClearCache({state, dispatch}, userData) {
        return new Promise(async resolve => {
            // localStorage
            const keys = ['themeConf', 'languageName', 'keyboardConf'];
            const savedData = keys.reduce((acc, key) => ({
                ...acc,
                [key]: window.localStorage.getItem(`__system:${key}__`)
            }), {});
            window.localStorage.clear();
            keys.forEach(key =>
                window.localStorage.setItem(`__system:${key}__`, savedData[key])
            );

            // localForage
            const cacheItems = {
                clientId: await $A.IDBString("clientId"),
                cacheServerUrl: await $A.IDBString("cacheServerUrl"),
                cacheProjectParameter: await $A.IDBArray("cacheProjectParameter"),
                cacheLoginEmail: await $A.IDBString("cacheLoginEmail"),
                cacheFileSort: await $A.IDBJson("cacheFileSort"),
                cacheTaskBrowse: await $A.IDBArray("cacheTaskBrowse"),
                cacheTranslationLanguage: await $A.IDBString("cacheTranslationLanguage"),
                cacheTranslations: await $A.IDBArray("cacheTranslations"),
                cacheEmojis: await $A.IDBArray("cacheEmojis"),
                userInfo: await $A.IDBJson("userInfo"),
                cacheVersion: state.cacheVersion,
            };
            await $A.IDBClear();
            await Promise.all(
                Object.entries(cacheItems).map(([key, value]) =>
                    $A.IDBSet(key, value)
                )
            );

            // userInfo
            await dispatch("saveUserInfoBase", $A.isJson(userData) ? userData : cacheItems.userInfo)

            // readCache
            await dispatch("handleReadCache")

            resolve()
        });
    },

    /**
     * 读取缓存
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    handleReadCache({state}) {
        return new Promise(async resolve => {
            // 定义需要获取的数据映射
            const dataMap = {
                string: [
                    'clientId',
                    'cacheServerUrl',
                    'cacheTranslationLanguage'
                ],
                array: [
                    'cacheUserBasic',
                    'cacheProjects',
                    'cacheColumns',
                    'cacheTasks',
                    'cacheProjectParameter',
                    'cacheTaskBrowse',
                    'cacheTranslations',
                    'dialogMsgs',
                    'fileLists',
                    'callAt',
                    'cacheEmojis',
                    'cacheDialogs'
                ],
                json: [
                    'userInfo'
                ]
            };

            // 批量获取数据
            const data = await Promise.all([
                ...dataMap.string.map(key => $A.IDBString(key)),
                ...dataMap.array.map(key => $A.IDBArray(key)),
                ...dataMap.json.map(key => $A.IDBJson(key))
            ]);

            // 更新state
            [...dataMap.string, ...dataMap.array, ...dataMap.json].forEach((key, index) => {
                state[key] = data[index];
            });

            // 特殊处理cacheDialogs
            state.cacheDialogs = state.cacheDialogs.map(item => ({
                ...item,
                loading: false,
                extra_draft_has: item.extra_draft_content ? 1 : 0
            }));

            // TranslationLanguage检查
            if (typeof languageList[state.cacheTranslationLanguage] === "undefined") {
                state.cacheTranslationLanguage = languageName;
            }

            // 处理用户信息
            if (state.userInfo.userid) {
                state.userId = state.userInfo.userid = $A.runNum(state.userInfo.userid);
                state.userToken = state.userInfo.token;
                state.userIsAdmin = $A.inArray("admin", state.userInfo.identity);
            }

            // 处理本地存储的用户信息
            const localId = $A.runNum(window.localStorage.getItem("__system:userId__"));
            const localToken = window.localStorage.getItem("__system:userToken__") || "";
            if (state.userId === 0 && localId && localToken) {
                state.userId = localId;
                state.userToken = localToken;
            }

            // 处理ServerUrl
            if (state.cacheServerUrl) {
                window.systemInfo.apiUrl = state.cacheServerUrl
            }

            resolve();
        })
    },

    /** *****************************************************************************************/
    /** *************************************** 新窗口打开 ****************************************/
    /** *****************************************************************************************/

    /**
     * 链接添加用户身份
     * @param state
     * @param url
     * @returns {Promise<unknown>}
     */
    userUrl({state}, url) {
        return new Promise(resolve => {
            const newUrl = $A.urlAddParams(url, {
                language: languageName,
                theme: state.themeConf,
                userid: state.userId,
                token: state.userToken,
            })
            resolve(newUrl)
        })
    },

    /**
     * 打开子窗口（App）
     * @param dispatch
     * @param objects
     */
    openAppChildPage({dispatch}, objects) {
        dispatch("userUrl", objects.params.url).then(url => {
            objects.params.url = url
            $A.eeuiAppOpenPage(objects)
        })
    },

    /**
     * 打开地图选位置（App）
     * @param dispatch
     * @param objects {{key: string, point: string}}
     * @returns {Promise<unknown>}
     */
    openAppMapPage({dispatch}, objects) {
        return new Promise(resolve => {
            const params = {
                title: $A.L("定位签到"),
                label: $A.L("选择附近地点"),
                placeholder: $A.L("搜索地点"),
                noresult: $A.L("附近没有找到地点"),
                errtip: $A.L("定位失败"),
                selectclose: "true",
                channel: $A.randomString(6)
            }
            $A.eeuiAppSetVariate(`location::${params.channel}`, "");
            const url = $A.urlAddParams($A.eeuiAppRewriteUrl('../public/tools/map/index.html'), Object.assign(params, objects || {}))
            dispatch('openAppChildPage', {
                pageType: 'app',
                pageTitle: params.title,
                url: 'web.js',
                params: {
                    titleFixed: true,
                    allowAccess: true,
                    url
                },
                callback: ({status}) => {
                    if (status === 'pause') {
                        const data = $A.jsonParse($A.eeuiAppGetVariate(`location::${params.channel}`));
                        if (data.point) {
                            $A.eeuiAppSetVariate(`location::${params.channel}`, "");
                            resolve(data);
                        }
                    }
                }
            })
        })
    },

    /**
     * 打开子窗口（客户端）
     * @param dispatch
     * @param params
     */
    openChildWindow({dispatch}, params) {
        dispatch("userUrl", params.path).then(path => {
            $A.Electron.sendMessage('openChildWindow', Object.assign(params, {path}))
        })
    },

    /**
     * 打开新标签窗口（客户端）
     * @param dispatch
     * @param url
     */
    openWebTabWindow({dispatch}, url) {
        if ($A.getDomain(url) != $A.getDomain($A.mainUrl())) {
            $A.Electron.sendMessage('openWebTabWindow', {url})
            return
        }
        dispatch("userUrl", url).then(url => {
            $A.Electron.sendMessage('openWebTabWindow', {url})
        })
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
     * 获取压缩进度
     * @param state
     * @param dispatch
     * @param data
     */
    packProgress({state, dispatch}, data) {
        $A.execMainDispatch("packProgress", data)
        //
        const index = state.filePackLists.findIndex(({name}) => name == data.name);
        if (index > -1) {
            state.filePackLists[index].progress = data.progress;
        } else {
            state.filePackLists.push(data);
            $A.IDBSave("filePackLists", state.filePackLists, 600)
        }
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
                if (dialog.type == 'group' && dialog.group_type == 'project' && dialog.group_info && dialog.group_info.id == data.id) {
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
                    return $A.dayjs(b.top_at) - $A.dayjs(a.top_at);
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
            setTimeout(() => {
                state.loadProjects++;
            }, 2000)
            dispatch("call", {
                url: 'project/lists',
                data: callData.get()
            }).then(({data}) => {
                dispatch("saveProject", data.data);
                callData.save(data).then(ids => dispatch("forgetProject", ids))
                state.projectTotal = data.total_all;
                //
                resolve(data)
            }).catch(e => {
                console.warn(e);
                reject(e)
            }).finally(_ => {
                state.loadProjects--;
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
            data._time = $A.dayjs().unix();
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
                if (dialog.type == 'group' && dialog.group_type == 'task' && dialog.group_info && dialog.group_info.id == data.id) {
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
     * 更新任务“今日任务”、“过期任务”
     * @param state
     * @param dispatch
     */
    todayAndOverdue({state, dispatch}) {
        const now = $A.daytz();
        const today = now.format("YYYY-MM-DD");
        state.cacheTasks.some(task => {
            if (!task.end_at) {
                return false;
            }
            const data = {};
            const endAt = $A.dayjs(task.end_at)
            if (!task.today && endAt.format("YYYY-MM-DD") == today) {
                data.today = true
            }
            if (!task.overdue && endAt < now) {
                data.overdue = true;
            }
            if (Object.keys(data).length > 0) {
                dispatch("saveTask", Object.assign(task, data));
            }
        })
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
        if (requestData === null) {
            requestData = {}
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
                dispatch("saveTask", data.data);
                callData.save(data).then(ids => dispatch("forgetTask", ids))
                //
                if (data.next_page_url) {
                    requestData.page = data.current_page + 1
                    if (data.current_page % 30 === 0) {
                        $A.modalConfirm({
                            content: "数据已超过" + data.to + "条，是否继续加载？",
                            onOk: () => {
                                dispatch("getTasks", requestData).then(resolve).catch(reject)
                            },
                            onCancel: () => {
                                resolve()
                            }
                        });
                    } else {
                        dispatch("getTasks", requestData).then(resolve).catch(reject)
                    }
                } else {
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
                state.taskArchiveView = 0;
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
        dispatch("setLoad", {
            key: `task-${task_id}`,
            delay: 1200
        })
        dispatch("call", {
            url: 'project/task/content',
            data: {
                task_id,
            },
        }).then(result => {
            dispatch("saveTaskContent", result.data)
        }).catch(e => {
            console.warn(e);
        }).finally(_ => {
            dispatch("cancelLoad", `task-${task_id}`)
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
        if ($A.isSubElectron) {
            if (task_id > 0) {
                $A.Electron.sendMessage('updateChildWindow', {
                    name: `task-${task_id}`,
                    path: `/single/task/${task_id}`,
                });
            } else {
                $A.Electron.sendMessage('windowClose');
            }
            return
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
        } else {
            state.taskOperation = {};
        }
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
            const post = $A.cloneJSON($A.newDateString(data));
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
            let post = $A.cloneJSON($A.newDateString(data));
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
                                let n1 = $A.dayjs(post.times[0]).unix(),
                                    n2 = $A.dayjs(post.times[1]).unix(),
                                    o1 = $A.dayjs(parentTask.start_at).unix(),
                                    o2 = $A.dayjs(parentTask.end_at).unix();
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
                            let n1 = $A.dayjs(post.times[0]).unix(),
                                n2 = $A.dayjs(post.times[1]).unix(),
                                c1 = $A.dayjs(currentTask.start_at).unix(),
                                c2 = $A.dayjs(currentTask.end_at).unix(),
                                o1 = $A.dayjs(subTask.start_at).unix(),
                                o2 = $A.dayjs(subTask.end_at).unix();
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
     * @param project_id
     * @returns {Promise<unknown>}
     */
    getTaskFlow({state, dispatch}, {task_id, project_id}) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'project/task/flow',
                data: {
                    task_id: task_id,
                    project_id: project_id || 0
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

    /**
     * 任务默认时间
     * @param state
     * @param dispatch
     * @param array
     * @returns {Promise<unknown>}
     */
    taskDefaultTime({state, dispatch}, array) {
        return new Promise(async resolve => {
            if ($A.isArray(array)) {
                array[0] = await dispatch("taskDefaultStartTime", array[0])
                array[1] = await dispatch("taskDefaultEndTime", array[1])
            }
            resolve(array)
        });
    },

    /**
     * 任务默认开始时间
     * @param state
     * @param value
     * @returns {Promise<unknown>}
     */
    taskDefaultStartTime({state}, value) {
        return new Promise(resolve => {
            if (/(\s|^)([0-2]\d):([0-5]\d)(:\d{1,2})*$/.test(value)) {
                value = value.replace(/(\s|^)([0-2]\d):([0-5]\d)(:\d{1,2})*$/, "$1" + state.systemConfig.task_default_time[0])
            }
            resolve(value)
        });
    },

    /**
     * 任务默认结束时间
     * @param state
     * @param value
     * @returns {Promise<unknown>}
     */
    taskDefaultEndTime({state}, value) {
        return new Promise(resolve => {
            if (/(\s|^)([0-2]\d):([0-5]\d)(:\d{1,2})*$/.test(value)) {
                value = value.replace(/(\s|^)([0-2]\d):([0-5]\d)(:\d{1,2})*$/, "$1" + state.systemConfig.task_default_time[1])
            }
            resolve(value)
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
            data.id = parseInt(data.id)
            const index = state.cacheDialogs.findIndex(({id}) => id == data.id);
            if (index > -1) {
                const original = state.cacheDialogs[index]
                const nowTime = data.user_ms
                const originalTime = original.user_ms || 0
                if (nowTime < originalTime) {
                    typeof data.unread !== "undefined" && delete data.unread
                    typeof data.unread_one !== "undefined" && delete data.unread_one
                    typeof data.mention !== "undefined" && delete data.mention
                    typeof data.mention_ids !== "undefined" && delete data.mention_ids
                }
                if (data.unread_one) {
                    if (state.dialogMsgs.find(m => m.id == data.unread_one)?.read_at) {
                        delete data.unread_one
                    }
                }
                if (data.mention_ids) {
                    data.mention_ids = data.mention_ids.filter(id => {
                        return !state.dialogMsgs.find(m => m.id == id)?.read_at
                    })
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
                    last_at: data.created_at || $A.daytz().format("YYYY-MM-DD HH:mm:ss")
                }
                if (data.mtype == 'tag') {
                    updateData.has_tag = true;
                }
                if (data.mtype == 'todo') {
                    updateData.has_todo = true;
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
     * 获取会话列表（避免重复获取）
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    getDialogAuto({state, dispatch}) {
        return new Promise(function (resolve, reject) {
            if (state.loadDialogAuto) {
                reject({msg: 'Loading'});
                return
            }
            setTimeout(_ => {
                state.loadDialogs++;
            }, 2000)
            state.loadDialogAuto = true
            dispatch("getDialogs")
                .then(resolve)
                .catch(reject)
                .finally(_ => {
                    state.loadDialogs--;
                    state.loadDialogAuto = false
                })
        })
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
            dispatch("call", {
                url: 'dialog/lists',
                data: callData.get()
            }).then(({data}) => {
                dispatch("saveDialog", data.data);
                callData.save(data).then(ids => dispatch("forgetDialog", ids))
                //
                if (data.current_page === 1) {
                    dispatch("getDialogLatestMsgs", data.data.map(({id}) => id))
                }
                //
                if (data.next_page_url && data.current_page < 5) {
                    requestData.page++
                    dispatch("getDialogs", requestData).then(resolve).catch(reject)
                } else {
                    resolve()
                    dispatch("getDialogBeyonds")
                }
            }).catch(e => {
                console.warn(e);
                reject(e)
            });
        });
    },

    /**
     * 获取超期未读会话
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    async getDialogBeyonds({state, dispatch}) {
        const key = await $A.IDBString("dialogBeyond")
        const val = $A.daytz().format("YYYY-MM-DD HH")
        if (key == val) {
            return  // 一小时取一次
        }
        await $A.IDBSet("dialogBeyond", val)
        //
        const filter = (func) => {
            return state.cacheDialogs
                .filter(func)
                .sort((a, b) => {
                    return $A.dayjs(a.last_at) - $A.dayjs(b.last_at);
                })
                .find(({id}) => id > 0)
        }
        const unreadDialog = filter(({unread, last_at}) => {
            return unread > 0 && last_at
        });
        const todoDialog = filter(({todo_num, last_at}) => {
            return todo_num > 0 && last_at
        });
        //
        dispatch("call", {
            url: 'dialog/beyond',
            data: {
                unread_at: unreadDialog ? unreadDialog.last_at : $A.dayjs().unix(),
                todo_at: todoDialog ? todoDialog.last_at : $A.dayjs().unix()
            }
        }).then(({data}) => {
            dispatch("saveDialog", data);
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
                if (dialog_id > 0) {
                    dispatch("saveDialog", {
                        id: dialog_id,
                        todo_num: $A.arrayLength(data)
                    });
                    state.dialogTodos = state.dialogTodos.filter(item => item.dialog_id != dialog_id)
                }
                dispatch("saveDialogTodo", data)
            } else {
                if (dialog_id > 0) {
                    dispatch("saveDialog", {
                        id: dialog_id,
                        todo_num: 0
                    });
                }
            }
        }).catch(console.warn);
    },

    /**
     * 获取会话消息置顶
     * @param state
     * @param dispatch
     * @param dialog_id
     */
    getDialogMsgTop({state, dispatch}, dialog_id) {
        dispatch("call", {
            url: 'dialog/msg/topinfo',
            data: {
                dialog_id,
            },
        }).then(({data}) => {
            if ($A.isJson(data)) {
                dispatch("saveDialogMsgTop", data)
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
            let dialog_msg_id;
            if ($A.isJson(dialog_id)) {
                search_msg_id = dialog_id.search_msg_id;
                dialog_msg_id = dialog_id.dialog_msg_id;
                dialog_id = dialog_id.dialog_id;
            }
            //
            requestAnimationFrame(_ => {
                state.dialogSearchMsgId = /^\d+$/.test(search_msg_id) ? search_msg_id : 0;
                state.dialogMsgId = /^\d+$/.test(dialog_msg_id) ? dialog_msg_id : 0;
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
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
                spinner: 600
            }).then(({data}) => {
                dispatch("saveDialog", data);
                dispatch("openDialog", data.id);
                resolve(data);
            }).catch(e => {
                console.warn(e);
                reject(e);
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
        // 会话消息总数量大于5000时只保留最近打开的50个会话
        const msg_max = 5000
        const retain_num = 500
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
        if (!/^\d+$/.test(dialog_id)) {
            return
        }
        $A.execMainDispatch("closeDialog", dialog_id)
        //
        // 更新草稿状态
        const dialog = state.cacheDialogs.find(item => item.id == dialog_id);
        if (dialog) {
            dialog.extra_draft_has = dialog.extra_draft_content ? 1 : 0
        }
        // 关闭会话后删除会话超限消息
        const msgs = state.dialogMsgs.filter(item => item.dialog_id == dialog_id)
        if (msgs.length > state.dialogMsgKeep) {
            const delIds = msgs.sort((a, b) => {
                return b.id - a.id
            }).splice(state.dialogMsgKeep).map(item => item.id)
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
     * 保存置顶数据
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogMsgTop({state, dispatch}, data) {
        $A.execMainDispatch("saveDialogMsgTop", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveDialogMsgTop", item)
            });
        } else if ($A.isJson(data)) {
            state.dialogMsgTops = state.dialogMsgTops.filter(item => item.dialog_id != data.dialog_id)
            const index = state.dialogMsgTops.findIndex(item => item.id == data.id);
            if (index > -1) {
                state.dialogMsgTops.splice(index, 1, Object.assign({}, state.dialogMsgTops[index], data));
            } else {
                state.dialogMsgTops.push(data);
            }
        }
    },

    /**
     * 忘记消息置顶数据
     * @param state
     * @param dispatch
     * @param msg_id
     */
    forgetDialogMsgTopForMsgId({state, dispatch}, msg_id) {
        $A.execMainDispatch("forgetDialogMsgTopForMsgId", msg_id)
        //
        const index = state.dialogMsgTops.findIndex(item => item.msg_id == msg_id);
        if (index > -1) {
            state.dialogMsgTops.splice(index, 1);
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
            if (index > -1) {
                const original = state.dialogMsgs[index]
                if (original.read_at) {
                    delete data.read_at
                }
                data = Object.assign({}, original, data)
                state.dialogMsgs.splice(index, 1, data);
            } else {
                state.dialogMsgs.push(data);
            }
            $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
            //
            const dialog = state.cacheDialogs.find(({id}) => id == data.dialog_id);
            if (dialog) {
                let isUpdate = false
                if (!data.read_at
                    && data.userid != state.userId
                    && !state.dialogIns.find(({dialog_id}) => dialog_id == dialog.id)) {
                    if (dialog.unread_one) {
                        dialog.unread_one = Math.min(dialog.unread_one, data.id)
                    } else {
                        dialog.unread_one = data.id
                    }
                    isUpdate = true
                }
                if (dialog.last_msg && dialog.last_msg.id == data.id) {
                    dialog.last_msg = Object.assign({}, dialog.last_msg, data)
                    isUpdate = true
                }
                if (isUpdate) {
                    dispatch("saveDialog", dialog)
                }
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
        dispatch("forgetDialogMsgTopForMsgId", msg_id)
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
            data.pagesize = 25;
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
                    const ids = resData.list.map(({id}) => id)
                    state.dialogMsgs = state.dialogMsgs.filter(item => {
                        return item.dialog_id != data.dialog_id || ids.includes(item.id) || $A.dayjs(item.created_at).unix() >= resData.time
                    });
                    $A.IDBSave("dialogMsgs", state.dialogMsgs, 600)
                    dispatch("saveDialog", resData.dialog)
                }
                if ($A.isArray(resData.todo)) {
                    state.dialogTodos = state.dialogTodos.filter(item => item.dialog_id != data.dialog_id)
                    dispatch("saveDialogTodo", resData.todo)
                }
                if ($A.isJson(resData.top)) {
                    dispatch("saveDialogMsgTop", resData.top)
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
     * 获取最新消息
     * @param state
     * @param dispatch
     * @param dialogIds
     * @returns {Promise<unknown>}
     */
    getDialogLatestMsgs({state, dispatch}, dialogIds = []) {
        return new Promise(function (resolve, reject) {
            if (state.userId === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            if (!$A.isArray(dialogIds)) {
                reject({msg: 'Parameter is not array'});
                return
            }
            if (dialogIds.length === 0) {
                resolve()
                return
            }
            //
            const wait = dialogIds.slice(5)
            const dialogs = dialogIds.slice(0, 5)
            dispatch("call", {
                method: 'post',
                url: 'dialog/msg/latest',
                data: {
                    dialogs: dialogs.map(id => {
                        return {
                            id,
                            latest_id: state.dialogMsgs.sort((a, b) => {
                                return b.id - a.id
                            }).find(({dialog_id}) => dialog_id == id)?.id || 0
                        }
                    }),
                    take: state.dialogMsgKeep
                },
            }).then(({data}) => {
                dispatch("saveDialogMsg", data.data);
                if (wait.length > 0) {
                    dispatch("getDialogLatestMsgs", wait).then(resolve).catch(reject)
                } else {
                    resolve()
                }
            }).catch(e => {
                reject(e)
            });
        })
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
            data.read_at = $A.daytz().format("YYYY-MM-DD HH:mm:ss");
            state.readWaitData[data.id] = state.readWaitData[data.id] || 0
            //
            const dialog = state.cacheDialogs.find(({id}) => id == data.dialog_id);
            if (dialog) {
                let mark = false
                if (data.id == dialog.unread_one) {
                    dialog.unread_one = 0
                    mark = true
                }
                if ($A.isArray(dialog.mention_ids)) {
                    const index = dialog.mention_ids.findIndex(id => id == data.id)
                    if (index > -1) {
                        dialog.mention_ids.splice(index, 1)
                        mark = true
                    }
                }
                if (mark) {
                    dispatch("saveDialog", dialog)
                    state.readWaitData[data.id] = data.dialog_id
                }
            }
        }
        clearTimeout(state.readTimeout);
        state.readTimeout = setTimeout(_ => {
            state.readTimeout = null;
            //
            if (state.userId === 0) {
                return;
            }
            if (Object.values(state.readWaitData).length === 0) {
                return
            }
            const ids = $A.cloneJSON(state.readWaitData);
            state.readWaitData = {};
            //
            dispatch("call", {
                method: 'post',
                url: 'dialog/msg/read',
                data: {
                    id: ids
                }
            }).then(({data}) => {
                for (const id in ids) {
                    if (ids.hasOwnProperty(id) && /^\d+$/.test(ids[id])) {
                        state.dialogMsgs.some(item => {
                            if (item.dialog_id == ids[id] && item.id >= id) {
                                item.read_at = $A.daytz().format("YYYY-MM-DD HH:mm:ss")
                            }
                        })
                    }
                }
                dispatch("saveDialog", data)
            }).catch(_ => {
                state.readWaitData = ids;
            }).finally(_ => {
                state.readLoadNum++
            });
        }, 50);
    },

    /**
     * 消息去除点
     * @param state
     * @param dispatch
     * @param data
     */
    dialogMsgDot({state, dispatch}, data) {
        if (!$A.isJson(data)) {
            return;
        }
        if (!data.dot) {
            return;
        }
        data.dot = 0;
        //
        dispatch("call", {
            url: 'dialog/msg/dot',
            data: {
                id: data.id
            }
        }).then(({data}) => {
            dispatch("saveDialog", data)
        });
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
                if (typeof data.after_msg_id !== "undefined") {
                    state.dialogMsgs.some(item => {
                        if (item.dialog_id == data.dialog_id && item.id >= data.after_msg_id) {
                            item.read_at = $A.daytz().format("YYYY-MM-DD HH:mm:ss")
                        }
                    })
                }
                dispatch("saveDialog", result.data)
                resolve(result)
            }).catch(e => {
                reject(e)
            })
        })
    },

    /**
     * 消息流
     * @param state
     * @param dispatch
     * @param streamUrl
     */
    streamDialogMsg({state, dispatch}, streamUrl) {
        if (!/^https*:\/\//i.test(streamUrl)) {
            streamUrl = $A.mainUrl(streamUrl.substring(1))
        }
        if (state.dialogSseList.find(item => item.streamUrl == streamUrl)) {
            return
        }
        const sse = new SSEClient(streamUrl)
        sse.subscribe(['append', 'replace', 'done'], (type, e) => {
            switch (type) {
                case 'append':
                    Store.set('dialogMsgChange', {
                        id: e.lastEventId,
                        type: 'append',
                        text: e.data
                    });
                    break;

                case 'replace':
                    Store.set('dialogMsgChange', {
                        id: e.lastEventId,
                        type: 'replace',
                        text: e.data
                    });
                    break;

                case 'done':
                    const index = state.dialogSseList.findIndex(item => sse === item.sse)
                    if (index > -1) {
                        state.dialogSseList.splice(index, 1)
                    }
                    sse.unsunscribe()
                    break;
            }
        })
        state.dialogSseList.push({sse, streamUrl, time: $A.dayjs().unix()})
        if (state.dialogSseList.length > 10) {
            state.dialogSseList.shift().sse.close()
        }
    },

    /**
     * 保存翻译
     * @param state
     * @param data {key, content, language}
     */
    saveTranslation({state}, data) {
        if (!$A.isJson(data)) {
            return
        }
        const translation = state.cacheTranslations.find(item => item.key == data.key && item.language == data.language)
        if (translation) {
            translation.content = data.content
        } else {
            const label = languageList[data.language] || data.language
            state.cacheTranslations.push(Object.assign(data, {label}))
        }
        $A.IDBSave("cacheTranslations", state.cacheTranslations.slice(-200))
    },

    /**
     * 设置翻译语言
     * @param state
     * @param language
     */
    setTranslationLanguage({state}, language) {
        state.cacheTranslationLanguage = language
        $A.IDBSave('cacheTranslationLanguage', language);
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
     * @param dispatch
     * @param delay
     */
    hiddenSpinner({state, dispatch}, delay) {
        if (typeof delay === "number") {
            setTimeout(_ => {
                dispatch("hiddenSpinner")
            }, delay)
            return
        }
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
     * @param data {{index: number | string, list: array} | string}
     */
    previewImage({state}, data) {
        if (!$A.isJson(data)) {
            data = {index: 0, list: [data]}
        }
        data.list = data.list.map(item => {
            if ($A.isJson(item)) {
                item.src = $A.thumbRestore(item.src)
            } else {
                item = $A.thumbRestore(item)
            }
            return item
        })
        if (typeof data.index === "string") {
            const current = $A.thumbRestore(data.index)
            data.index = Math.max(0, data.list.findIndex(item => {
                return $A.isJson(item) ? item.src == current : item == current
            }))
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
            old.src = ""
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
            old.src = ""
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
     */
    websocketConnection({state, dispatch}) {
        clearTimeout(state.wsTimeout);
        if (state.ws) {
            state.ws.close();
            state.ws = null;
        }
        if (state.userId === 0) {
            return;
        }
        //
        let url = $A.mainUrl('ws');
        url = url.replace("https://", "wss://");
        url = url.replace("http://", "ws://");
        url += `?action=web&token=${state.userToken}&language=${languageName}`;
        //
        const wgLog = $A.openLog;
        const wsRandom = $A.randomString(16);
        state.wsRandom = wsRandom;
        //
        state.ws = new WebSocket(url);
        state.ws.onopen = async (e) => {
            wgLog && console.log("[WS] Open", e, $A.daytz().format("YYYY-MM-DD HH:mm:ss"))
            state.wsOpenNum++;
            //
            if (window.systemInfo.debug === "yes" || state.systemConfig.e2e_message !== 'open') {
                return  // 测试环境不发送加密信息
            }
            dispatch("websocketSend", {
                type: 'encrypt',
                data: {
                    type: 'pgp',
                    key: (await dispatch("pgpGetLocalKey")).publicKeyB64
                }
            })
        };
        state.ws.onclose = async (e) => {
            wgLog && console.log("[WS] Close", e, $A.daytz().format("YYYY-MM-DD HH:mm:ss"))
            state.ws = null;
            //
            clearTimeout(state.wsTimeout);
            state.wsTimeout = setTimeout(() => {
                wsRandom === state.wsRandom && dispatch('websocketConnection');
            }, 3000);
        };
        state.ws.onerror = async (e) => {
            wgLog && console.log("[WS] Error", e, $A.daytz().format("YYYY-MM-DD HH:mm:ss"))
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
                    Store.set('userActive', {type: 'line', data: msgDetail.data});
                    break

                case "msgStream":
                    dispatch("streamDialogMsg", msgDetail.stream_url);
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
                                                last_msg: data.last_msg,
                                                last_at: data.last_msg ? data.last_msg.created_at : $A.daytz().format("YYYY-MM-DD HH:mm:ss"),
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
                                        const saveMsg = (data, count) => {
                                            if (count > 5 || state.dialogMsgs.find(({id}) => id == data.id)) {
                                                // 更新消息列表
                                                dispatch("saveDialogMsg", data)
                                                // 更新最后消息
                                                dispatch("updateDialogLastMsg", data);
                                                return;
                                            }
                                            setTimeout(_ => {
                                                saveMsg(data, ++count)
                                            }, 20);
                                        }
                                        saveMsg(data, 0);
                                        break;
                                    case 'update':
                                    case 'readed':
                                        const updateMsg = (data, count) => {
                                            if (state.dialogMsgs.find(({id}) => id == data.id)) {
                                                dispatch("saveDialogMsg", data)
                                                // 更新待办
                                                if (typeof data.todo !== "undefined") {
                                                    dispatch("getDialogTodo", dialog_id)
                                                }
                                                return;
                                            }
                                            if (count <= 5) {
                                                setTimeout(_ => {
                                                    updateMsg(data, ++count)
                                                }, 500);
                                            }
                                        }
                                        updateMsg(data, 0);
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
                                    case 'updateTopMsg':
                                        // 更新置顶
                                        dispatch("saveDialog", {
                                            id: data.dialog_id,
                                            top_msg_id: data.top_msg_id,
                                            top_userid: data.top_userid
                                        })
                                        dispatch("getDialogMsgTop", dialog_id)
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
                                    case 'recovery':
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
                                    case 'compress':
                                        dispatch("packProgress", data);
                                        break;
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 工作报告
                         */
                        case "report":
                            (function ({action}) {
                                if (action == 'unreadUpdate') {
                                    dispatch("getReportUnread", 1000)
                                }
                            })(msgDetail);
                            break;

                        /**
                         * 流程审批
                         */
                        case "approve":
                            (function ({action}) {
                                if (action == 'unread') {
                                    dispatch("getApproveUnread", 1000)
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
        if (typeof params === "string") {
            state.wsListener[params] && delete state.wsListener[params];
            return;
        }
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
    },

    /** *****************************************************************************************/
    /** *************************************** meeting *********************************************/
    /** *****************************************************************************************/

    /**
    * 关闭会议窗口
    * @param state
    * @param type
    */
    closeMeetingWindow({state}, type) {
        state.meetingWindow = {
            show: false,
            type: type,
            meetingid: 0
        };
    },

    /**
     * 显示会议窗口
     * @param state
     * @param data
     */
    showMeetingWindow({state}, data) {
        state.meetingWindow = Object.assign(data, {
            show: data.type !== 'direct',
        });
    },

    /** *****************************************************************************************/
    /** *************************************** okr *********************************************/
    /** *****************************************************************************************/

    /**
     * 打开Okr详情页
     * @param state
     * @param dispatch
     * @param link_id
     */
    openOkr({state}, link_id) {
        if (link_id > 0) {
            if (window.innerWidth < 910) {
                $A.goForward({
                    path:'/manage/apps/okr/okrDetails?data=' + link_id,
                });
            }else{
                state.okrWindow = {
                    type: 'open',
                    model: 'details',
                    show: true,
                    id: link_id
                };
                setTimeout(()=>{
                    state.okrWindow.show = false;
                    state.okrWindow.id = 0;
                },10)
            }
        }

    },
}
