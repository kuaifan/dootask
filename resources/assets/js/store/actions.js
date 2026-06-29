import * as openpgp from 'openpgp_hi/lightweight';
import {initLanguage, languageList, languageName} from "../language";
import {$callData, $urlSafe, SSEClient} from '../utils'
import {isLocalHost} from "../components/Replace/utils";
import emitter from "./events";
import axios from "axios";

const dialogDraftState = { timer: {}, subTemp: null }

export default {
    /**
     * 预加载
     * @param state
     */
    preload({state}) {
        window.addEventListener('resize', () => {
            const windowWidth = $A(window).width(),
                windowHeight = $A(window).height(),
                windowOrientation = $A.screenOrientation()

            state.windowTouch = "ontouchend" in document

            state.windowWidth = windowWidth
            state.windowHeight = windowHeight

            state.windowOrientation = windowOrientation
            state.windowLandscape = windowOrientation === 'landscape'
            state.windowPortrait = windowOrientation === 'portrait'

            state.windowIsFullScreen = $A.isFullScreen()

            state.formOptions = {
                class: windowWidth > 576 ? '' : 'form-label-weight-bold',
                labelPosition: windowWidth > 576 ? 'right' : 'top',
                labelWidth: windowWidth > 576 ? 'auto' : '',
            }

            $A.eeuiAppSendMessage({
                action: 'windowSize',
                width: windowWidth,
                height: windowHeight,
            });
        })

        window.addEventListener('scroll', () => {
            state.windowScrollY = window.scrollY
        })

        window.addEventListener('message', ({data}) => {
            data = $A.jsonParse(data);
            if (data.action === 'eeuiAppSendMessage') {
                const items = $A.isArray(data.data) ? data.data : [data.data];
                items.forEach(item => {
                    $A.eeuiAppSendMessage(item);
                })
            }
        })

        window.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                $A("body").addClass("fullscreen-mode")
            } else {
                $A("body").removeClass("fullscreen-mode")
            }
        });

        window.visualViewport?.addEventListener('resize', () => {
            state.viewportHeight = window.visualViewport.height || 0;
        });
    },

    /**
     * 初始化
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    init({state, dispatch}) {
        return new Promise(async resolve => {
            // 语言、主题、用户信息
            const urlParams = $A.urlParameterAll()
            const paramMap = {
                language: '__system:languageName__',
                theme: '__system:themeConf__',
                userid: '__system:userId__',
                token: '__system:userToken__'
            };
            const paramUser = {
                userid: 0,
                token: null
            }
            Object.entries(paramMap).forEach(([param, key]) => {
                if (urlParams[param]) {
                    window.localStorage.setItem(key, urlParams[param]);
                    param === 'userid' && (paramUser.userid = $A.runNum(urlParams[param]))
                    param === 'token' && (paramUser.token = urlParams[param])
                }
            });
            if (Object.keys(paramMap).some(param => urlParams[param])) {
                const newUrl = $A.removeURLParameter(window.location.href, Object.keys(paramMap));
                window.history.replaceState(null, '', newUrl);
            }

            // 处理用户身份信息
            if (paramUser.userid > 0 && paramUser.token) {
                const userInfo = await $A.IDBJson('userInfo')
                await $A.IDBSet("userInfo", Object.assign(userInfo, paramUser));
            }

            // 清理缓存、读取缓存
            let action = null
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

            // 载入静态资源
            await $A.loadScriptS([
                // 基础包
                'js/jsencrypt.min.js',
                'js/scroll-into-view.min.js',

                // 加载语言包
                `language/web/key.js`,
                `language/web/${languageName}.js`,
                `language/iview/${languageName}.js`,
            ])

            // 初始化语言
            initLanguage()

            resolve(action)
        })
    },

    /**
     * 获取安全区域
     * @param state
     * @returns {Promise<unknown>}
     */
    safeAreaInsets({state}) {
        return new Promise(resolve => {
            if (!state.isFirstPage) {
                return resolve(null)
            }
            $A.eeuiAppGetSafeAreaInsets().then(async data => {
                data.top = data.top || state.safeAreaSize?.data?.top || 0
                data.bottom = data.bottom || state.safeAreaSize?.data?.bottom || 0
                const proportion = data.height / window.outerHeight
                state.safeAreaSize = {
                    top: Math.round(data.top / proportion * 100) / 100,
                    bottom: Math.round(data.bottom / proportion * 100) / 100,
                    data
                }
                resolve(state.safeAreaSize)
            }).catch(e => {
                console.warn(e)
                resolve(null)
            })
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
        if (params.departmentOwner !== false
            && state.systemConfig.department_owner_project_view === 'open'
            && state.departmentOwnerReadonlyUrls.includes(params.url)
            && (state.cacheDepartmentOwnerIds || []).length > 0) {
            if (!$A.isJson(params.data)) params.data = {}
            if (params.data.department_owner_ids === undefined) {
                params.data.department_owner_ids = state.cacheDepartmentOwnerIds.join(',')
            }
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
            if (/^https?:\/\/public\//.test(params.url)) {
                reject({ret: -1, data: {}, msg: "No server address"})
                return
            }

            // 加密传输
            const encrypt = []
            if (params.encrypt === true) {
                if (params.data) {
                    // 有数据才加密
                    if (state.apiKeyData.type === 'pgp') {
                        // PGP加密
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

            // 等待效果（Spinner）
            if (params.spinner === true || (typeof params.spinner === "number" && params.spinner > 0)) {
                const {before, complete} = params
                params.before = () => {
                    dispatch("showSpinner", typeof params.spinner === "number" ? params.spinner : 0)
                    typeof before === "function" && before()
                }
                params.complete = () => {
                    dispatch("hiddenSpinner")
                    typeof complete === "function" && complete()
                }
            }

            // 请求成功
            params.success = async (result, status, xhr) => {
                // 数据校验
                if (!$A.isJson(result)) {
                    console.log(result, status, xhr)
                    reject({ret: -1, data: {}, msg: $A.L('返回参数错误')})
                    return
                }

                // 数据解密
                if (params.encrypt === true && result.encrypted) {
                    result = await dispatch("pgpDecryptApi", result.encrypted)
                }
                const {ret, data, msg} = result

                // 身份判断（身份丢失）
                if (ret === -1) {
                    state.userId = 0
                    if (params.checkAuth !== false) {
                        state.ajaxAuthException = msg || $A.L('请登录后继续...')
                        reject(Object.assign(result, {msg: false}))
                        return
                    }
                }

                // 身份判断（需要昵称）
                if (ret === -2 && params.checkNick !== false) {
                    dispatch("userEditInput", 'nickname').then(() => {
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }).catch(err => {
                        reject({ret: -1, data, msg: err || $A.L('请设置昵称！')})
                    })
                    return
                }

                // 身份判断（需要联系电话）
                if (ret === -3 && params.checkTel !== false) {
                    dispatch("userEditInput", 'tel').then(() => {
                        dispatch("call", cloneParams).then(resolve).catch(reject)
                    }).catch(err => {
                        reject({ret: -1, data, msg: err || $A.L('请设置联系电话！')})
                    })
                    return
                }

                // 返回数据
                if (ret === 1) {
                    resolve({data, msg, xhr})
                    return
                }

                // 错误处理
                reject({ret, data, msg: msg || $A.L('未知错误')})
                if (ret === -4001) {
                    dispatch("forgetProject", {id: data.project_id})
                } else if (ret === -4002) {
                    data.force === 1 && (state.taskArchiveView = 0)
                    dispatch("forgetTask", {id: data.task_id})
                } else if (ret === -4003) {
                    dispatch("forgetDialog", {id: data.dialog_id})
                } else if (ret === -4004) {
                    dispatch("getTaskForParent", data.task_id).catch(() => {})
                }
            }

            // 请求失败
            params.error = async (xhr, status) => {
                const reason = {ret: -1, data: {}, msg: $A.L('请求失败，请稍后重试。')}
                const isNetworkException = window.navigator.onLine === false || (status === 0 && xhr.readyState === 4)

                // 网络异常
                if (isNetworkException) {
                    // 重试一次
                    if (cloneParams.method !== "post" && cloneParams.networkFailureRetry !== false) {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        dispatch("call", Object.assign(cloneParams, {networkFailureRetry: false})).then(resolve).catch(reject)
                        return
                    }
                    // 异常提示
                    reason.ret = -1001
                    reason.msg = params.checkNetwork !== false ? false : $A.L('网络异常，请稍后重试。')
                    if (params.checkNetwork !== false && $A.Ready !== false) {
                        state.ajaxNetworkException = $A.L("网络连接失败，请检查网络设置。")
                    }
                }

                // 异常处理
                reject(reason)
                console.error(xhr, status);
            }

            // 发起请求
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
            $A.Electron.request({
                action: 'openDownloadWindow',
                language: languageName,
                theme: state.themeName,
            });
            $A.Electron.request({
                action: 'createDownload',
                url
            });
        } else if ($A.isEEUIApp) {
            $A.eeuiAppOpenWeb(url);
        } else {
            window.open(url)
        }
    },

    /**
     * 显示文件（打开文件所在位置）
     * @param state
     * @param getters
     * @param dispatch
     * @param data
     */
    filePos({state, getters, dispatch}, data) {
        if ($A.isSubElectron) {
            $A.syncDispatch("filePos", data)
            $A.Electron.sendMessage('mainWindowActive');
            return
        }
        dispatch('openTask', 0)
        if (!getters.isMessengerPage || state.windowPortrait) {
            // 如果 当前不是消息页面 或 是竖屏 则关闭对话窗口
            dispatch("openDialog", 0);
        }
        $A.goForward({name: 'manage-file', params: data});
    },

    /**
     * 切换面板变量
     * @param commit
     * @param state
     * @param data
     * @param data|{key, project_id}
     */
    toggleProjectParameter({commit, state}, data) {
        $A.syncDispatch("toggleProjectParameter", data)
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
                commit("project/parameter/push", $A.projectParameterTemplate(project_id))
                index = state.cacheProjectParameter.findIndex(item => item.project_id == project_id)
            }
            const cache = state.cacheProjectParameter[index];
            if (!$A.isJson(key)) {
                key = {[key]: value || !cache[key]};
            }
            commit("project/parameter/splice", {index, data: Object.assign(cache, key)})
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
                if ($A.isEEUIApp) {
                    $A.modalWarning("仅Android设置支持主题功能");
                } else {
                    $A.modalWarning("仅客户端或Chrome浏览器支持主题功能");
                }
                resolve(false)
                return;
            }
            dispatch("synchTheme", {mode})
            resolve(true)
        });
    },

    /**
     * 同步主题
     * @param state
     * @param dispatch
     * @param mode
     * @param args
     */
    synchTheme({state, dispatch}, {mode, ...args} = {}) {
        $A.syncDispatch("synchTheme", {...args, mode})
        //
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
        if ($A.isEEUIApp) {
            $A.eeuiAppSendMessage({
                action: 'updateTheme',
                themeName: state.themeName,
                themeDefault: {
                    theme: {
                        dark: '#131313',
                        light: '#f8f8f8'
                    },
                    nav: {
                        dark: '#cdcdcd',
                        light: '#232323'
                    }
                }
            });
        } else if ($A.isElectron) {
            $A.Electron.sendMessage('setStore', {
                key: 'themeConf',
                value: state.themeConf
            });
        }
    },

    /**
     * 获取基本数据（项目、对话、仪表盘任务、会员基本信息）
     * @param state
     * @param dispatch
     * @param timeout
     */
    async getBasicData({state, dispatch}, timeout) {
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
        dispatch("getDialogAuto").catch(() => {});
        dispatch("getDialogTodo", 0).catch(() => {});
        dispatch("getTaskPriority", 1000);
        dispatch("getReportUnread", 1000);
        dispatch("getProjectsForDepartmentOwnerView").catch(() => {});
        dispatch("getTaskForDashboard");
        dispatch("dialogMsgRead");
        dispatch("updateMicroAppsStatus");
        //
        const allIds = Object.values(state.userAvatar).map(({userid}) => userid).filter(id => id > 0);
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
     * 获取会员扩展信息
     * @param state
     * @param dispatch
     * @param userid
     * @returns {Promise<unknown>}
     */
    getUserExtra({state, dispatch}, userid) {
        return new Promise(async (resolve, reject) => {
            if (!userid) {
                reject({msg: "userid missing"});
                return;
            }
            const cacheMap = state.cacheUserExtra || {};
            const cacheItem = cacheMap[`${userid}`];
            const now = Date.now();
            if (cacheItem && cacheItem.data && (now - cacheItem.updatedAt) < 30000) {
                resolve(cacheItem.data);
                return;
            }
            try {
                const {data} = await dispatch("call", {
                    url: 'users/extra',
                    data: {userid},
                });
                state.cacheUserExtra = Object.assign({}, cacheMap, {
                    [`${userid}`]: {
                        data,
                        updatedAt: Date.now()
                    }
                });
                resolve(data);
            } catch (error) {
                reject(error);
            }
        });
    },

    /**
     * 缓存会员扩展信息
     * @param state
     * @param payload {userid, data}
     */
    saveUserExtra({state}, payload) {
        const userid = $A.runNum(payload?.userid);
        if (!userid || !$A.isJson(payload?.data)) {
            return;
        }
        const cacheMap = state.cacheUserExtra || {};
        const current = cacheMap[`${userid}`]?.data || {};
        state.cacheUserExtra = Object.assign({}, cacheMap, {
            [`${userid}`]: {
                data: Object.assign({}, current, payload.data),
                updatedAt: Date.now()
            }
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
            if ($A.isSubElectron || ($A.isEEUIApp && !state.isFirstPage)) {
                // 子窗口（Electron）、不是第一个页面（App） 不保存
            } else {
                await $A.IDBSet("userInfo", state.userInfo);
            }
            //
            $A.eeuiAppSendMessage({
                action: 'userChatList',
                language: $A.eeuiAppConvertLanguage(),
                url: $A.mainUrl('api/users/share/list') + `?token=${state.userToken}`
            });
            $A.eeuiAppSendMessage({
                action:"userUploadUrl",
                dirUrl: $A.mainUrl('api/file/content/upload') + `?token=${state.userToken}`,
                chatUrl: $A.mainUrl('api/dialog/msg/sendfiles') + `?token=${state.userToken}`,
            });
            //
            resolve()
        })
    },

    /**
     * 更新会员信息
     * @param commit
     * @param state
     * @param dispatch
     * @param info
     * @returns {Promise<unknown>}
     */
    saveUserInfo({commit, state, dispatch}, info) {
        return new Promise(async resolve => {
            await dispatch("saveUserInfoBase", info);
            //
            dispatch("getBasicData", null);
            if (state.userId > 0) {
                commit("user/save", state.cacheUserBasic.filter(({userid}) => userid !== state.userId))
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
                    emitter.emit('userActive', {type: 'cache', data: temp});
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
            checkAuth: false
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
     * 获取用户基础信息（缓存没有则请求网络）
     * @param state
     * @param dispatch
     * @param userid
     * @returns {Promise<unknown>}
     */
    getUserData({state, dispatch}, userid) {
        return new Promise(async (resolve, reject) => {
            let tempUser = state.cacheUserBasic.find(item => item.userid == userid);
            if (!tempUser) {
                try {
                    const {data} = await dispatch("call", {
                        url: 'users/basic',
                        data: {
                            userid: [userid]
                        },
                        checkAuth: false
                    });
                    tempUser = data.find(item => item.userid == userid);
                } catch (_) {}
            }
            if (tempUser) {
                resolve($A.cloneJSON(tempUser));
            } else {
                reject();
            }
        })
    },

    /**
     * 保存用户基础信息
     * @param commit
     * @param state
     * @param data
     */
    saveUserBasic({commit, state}, data) {
        $A.syncDispatch("saveUserBasic", data)
        //
        const index = state.cacheUserBasic.findIndex(({userid}) => userid == data.userid);
        if (index > -1) {
            data = Object.assign({}, state.cacheUserBasic[index], data)
            commit("user/splice", {index, data})
        } else {
            commit("user/push", data)
        }
        emitter.emit('userActive', {type: 'cache', data});
    },

    /**
     * 修改机器人信息
     * @param dispatch
     * @param data
     * @returns {Promise<unknown>}
     */
    editUserBot({dispatch}, data) {
        return new Promise((resolve, reject) => {
            let dialogId = 0
            if (data.dialog_id) {
                dialogId = data.dialog_id;
                delete data.dialog_id;
            }
            dispatch("call", {
                url: 'users/bot/edit',
                data,
                method: 'post'
            }).then(({data, msg}) => {
                dispatch("saveUserBasic", {
                    userid: data.id,
                    nickname: data.name,
                    userimg: data.avatar,
                });
                if (dialogId) {
                    dispatch("saveDialog", {
                        id: dialogId,
                        name: data.name
                    });
                }
                resolve({data, msg})
            }).catch(reject);
        })
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
     * 获取部门列表
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    getDepartmentList({dispatch}) {
        return new Promise((resolve, reject) => {
            const generateList = (data, parent_id = 0, level = 0, chains = []) => {
                let result = [];
                data.some(item => {
                    if (item.parent_id == parent_id) {
                        const newItem = Object.assign({}, item, {
                            chains: chains.concat([item.name]),
                            level: level + 1
                        });
                        result.push(newItem);
                        // 递归获取子部门
                        const children = generateList(data, item.id, level + 1, chains.concat([item.name]));
                        result = result.concat(children);
                    }
                });
                return result;
            };

            dispatch("call", {
                url: 'users/department/list',
            }).then(({data}) => {
                resolve(generateList(data, 0, 1));
            }).catch(reject);
        });
    },

    /**
     * 登出（打开登录页面）
     * @param state
     * @param dispatch
     * @param appendFrom
     * @returns {Promise<unknown>}
     */
    logout({state, dispatch}, appendFrom = true) {
        return new Promise(async resolve => {
            try {
                await dispatch("call", {
                    url: "users/logout",
                    timeout: 6000
                })
            } catch (e) {
                console.log(e);
            }
            dispatch("handleClearCache", {}).then(() => {
                let from = ["/", "/login"].includes(window.location.pathname) ? "" : encodeURIComponent(window.location.href);
                if (appendFrom === false) {
                    from = null;
                }
                $A.goForward({name: 'login', query: from ? {from: from} : {}}, true);
                resolve();
            });
        })
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
            data.send_button_app = data.send_button_app || 'button'        // button, enter 移动端发送按钮，默认 button （显示发送按钮）
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
            const keysToKeep = [
                'clientId',
                'cacheServerUrl',
                'cacheCalendarView',
                'cacheProjectParameter',
                'cacheLoginEmail',
                'cacheFileSort',
                'cacheTranslationLanguage',
                'cacheTranslations',
                'cacheEmojis',
                'userInfo',
                'mcpServerStatus',
                'aiAssistant.model',
                'aiAssistant.sessions',
            ];
            await $A.IDBClear(keysToKeep);
            await $A.IDBSet('cacheVersion', state.cacheVersion);

            // userInfo
            const cachedUserInfo = await $A.IDBJson("userInfo");
            await dispatch("saveUserInfoBase", $A.isJson(userData) ? userData : cachedUserInfo)

            // readCache
            await dispatch("handleReadCache")

            // Reset auth exception flag after successful login flow
            state.ajaxAuthException = null

            resolve()
        });
    },

    /**
     * 读取缓存
     * @param state
     * @param dispatch
     * @returns {Promise<unknown>}
     */
    handleReadCache({state, commit}) {
        return new Promise(async resolve => {
            // 定义需要获取的数据映射
            const dataMap = {
                string: [
                    'clientId',
                    'cacheServerUrl',
                    'cacheCalendarView',
                    'cacheTranslationLanguage'
                ],
                array: [
                    'cacheUserBasic',
                    'cacheProjects',
                    'cacheDepartmentOwnerIds',
                    'cacheColumns',
                    'cacheTasks',
                    'cacheProjectParameter',
                    'cacheTranslations',
                    'dialogMsgs',
                    'dialogDrafts',
                    'dialogQuotes',
                    'fileLists',
                    'callAt',
                    'cacheEmojis',
                    'cacheDialogs',
                    'microAppsIds',
                    'microAppsMenus',
                ],
                json: [
                    'userInfo',
                    'taskRelatedCache',
                    'dialogCommonCountCache',
                    'mcpServerStatus'
                ]
            };

            // 批量获取数据
            const data = await Promise.all([
                ...dataMap.string.map(key => $A.IDBString(key)),
                ...dataMap.array.map(key => $A.IDBArray(key)),
                ...dataMap.json.map(key => $A.IDBJson(key))
            ]);

            // 更新 state
            [...dataMap.string, ...dataMap.array, ...dataMap.json].forEach((key, index) => {
                if (key === 'cacheDepartmentOwnerIds') {
                    commit('department/owner/ids/save', data[index]);
                } else {
                    state[key] = data[index];
                }
            });

            // 特殊处理 cacheDialogs
            state.cacheDialogs = state.cacheDialogs.map(item => ({
                ...item,
                loading: false,
            }));

            // 特殊处理 dialogDrafts
            state.dialogDrafts = state.dialogDrafts.filter(item => !!item.content).map(item => ({
                ...item,
                tag: !!item.content,
            }));

            // TranslationLanguage 检查
            if (typeof languageList[state.cacheTranslationLanguage] === "undefined") {
                state.cacheTranslationLanguage = languageName;
            }

            // 处理用户信息
            if (state.userInfo.userid) {
                state.userId = state.userInfo.userid = $A.runNum(state.userInfo.userid);
                state.userToken = state.userInfo.token;
                state.userIsAdmin = $A.inArray("admin", state.userInfo.identity);
            }

            // 处理 ServerUrl
            if (state.cacheServerUrl) {
                window.systemInfo.apiUrl = state.cacheServerUrl
            }

            resolve();
        })
    },

    /**
     * Electron 页面卸载触发
     */
    onBeforeUnload() {
        if ($A.isSubElectron && dialogDraftState.subTemp) {
            $A.syncDispatch("saveDialogDraft", dialogDraftState.subTemp)
            dialogDraftState.subTemp = null;
        }
    },

    /**
     * 滚动到底部（将 el 底部对齐到网页底部）
     * @param state
     * @param el
     */
    scrollBottom({state}, el) {
        if (!el) {
            return
        }
        const rect = el.getBoundingClientRect();
        if (!rect) {
            return;
        }
        window.scrollTo({
            top: rect.bottom + state.safeAreaSize.bottom,
            behavior: 'smooth'
        });
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
            // 如果是访问：服务器域名 且 当前是本地文件，则将服务器域名替换成本地路径
            if ($A.getDomain(url) == $A.mainDomain() && isLocalHost(window.location)) {
                try {
                    const remoteURL = new URL(url)
                    if (/^\/(single|meeting)\//.test(remoteURL.pathname)) {
                        // 判断将服务器域名替换成本地路径
                        const localURL = new URL(window.location)
                        localURL.hash = remoteURL.pathname + remoteURL.search
                        return resolve(localURL.toString())
                    }
                } catch (e) {
                    // 解析失败则不做任何处理
                }
            }

            // 基本参数
            const params = {
                language: languageName,
                theme: state.themeConf,
                userid: state.userId,
            }
            // 如果是访问：服务器域名 或 本地文件，则添加 token 参数
            if ($A.getDomain(url) == $A.mainDomain() || isLocalHost(url)) {
                params.token = state.userToken
            }
            resolve($A.urlAddParams(url, params))
        })
    },

    /**
     * 打开地图选位置（App）
     * @param dispatch
     * @param objects {{type: string, key: string, point: string, radius: number}}
     * @returns {Promise<unknown>}
     */
    openAppMapPage({dispatch}, objects) {
        return new Promise(resolve => {
            const title = $A.L("定位签到")
            const channel = $A.randomString(6)
            const params = {
                title,
                label: $A.L("选择附近地点"),
                placeholder: $A.L("搜索地点"),
                noresult: $A.L("附近没有找到地点"),
                errtip: $A.L("定位失败"),
                selectclose: "true",
                channel,
            }
            $A.eeuiAppSetVariate(`location::${channel}`, "");
            const url = $A.urlAddParams(window.location.origin + '/tools/map/index.html', Object.assign(params, objects || {}))
            dispatch('openAppChildPage', {
                pageType: 'app',
                pageTitle: title,
                url: 'web.js',
                params: {
                    titleFixed: true,
                    hiddenDone: true,
                    url
                },
                callback: ({status}) => {
                    if (status === 'pause') {
                        const data = $A.jsonParse($A.eeuiAppGetVariate(`location::${channel}`));
                        if (data.point) {
                            $A.eeuiAppSetVariate(`location::${channel}`, "");
                            if (data.distance > objects.radius) {
                                $A.modalError(`你选择的位置「${data.title}」不在签到范围内`)
                                return
                            }
                            resolve(data);
                        }
                    }
                }
            })
        })
    },

    /**
     * 打开子窗口（App）
     * @param dispatch
     * @param objects
     */
    async openAppChildPage({dispatch}, objects) {
        objects.params.url = await dispatch("userUrl", objects.params.url)

        if (typeof objects.params.allowAccess === "undefined") {
            // 如果是本地文件，则允许跨域
            objects.params.allowAccess = isLocalHost(objects.params.url)
        }
        if (typeof objects.params.showProgress === "undefined") {
            // 如果不是本地文件，则显示进度条
            objects.params.showProgress = !isLocalHost(objects.params.url)
        }

        $A.eeuiAppOpenPage(objects)
    },

    /**
     * 打开窗口（客户端）
     * @param dispatch
     * @param params {path, name, mode, force, title, titleFixed, width, height, minWidth, minHeight, userAgent, webPreferences}
     *   - path: 要打开的地址（或直接传 URL 字符串）
     *   - name: 窗口/标签名称
     *   - mode: 'tab' | 'window'，默认 'tab'
     *   - force: 是否强制刷新
     *   - title: 窗口标题
     *   - titleFixed: 是否固定标题
     *   - width/height: 窗口尺寸（mode='window' 有效）
     *   - minWidth/minHeight: 最小尺寸（mode='window' 有效）
     *   - userAgent: 自定义 UserAgent
     *   - webPreferences: 网页偏好设置
     */
    async openWindow({dispatch}, params) {
        // 兼容直接传入 URL 字符串的情况
        if (typeof params === 'string') {
            params = { path: params }
        }

        // 外站 URL 自动移除 preload 脚本（通过 contextIsolation: false）
        const pathDomain = $A.getDomain(params.path)
        const isExternal = pathDomain && pathDomain !== $A.mainDomain()
        if (isExternal) {
            params.webPreferences = Object.assign({contextIsolation: false}, params.webPreferences)
        } else {
            params.path = await dispatch("userUrl", params.path)
        }

        $A.Electron.sendMessage('openWindow', {
            name: params.name,
            url: params.path,
            mode: params.mode,
            title: params.title,
            titleFixed: params.titleFixed,
            width: params.width,
            height: params.height,
            minWidth: params.minWidth,
            minHeight: params.minHeight,
            userAgent: params.userAgent,
            force: params.force,
            webPreferences: params.webPreferences,
        })
    },

    /** *****************************************************************************************/
    /** ************************************** 文件 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存文件数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveFile({commit, state, dispatch}, data) {
        $A.syncDispatch("saveFile", data)
        //
        if ($A.isArray(data)) {
            data.forEach((file) => {
                dispatch("saveFile", file);
            });
        } else if ($A.isJson(data)) {
            let base = {_load: false, _edit: false};
            const index = state.fileLists.findIndex(({id}) => id == data.id);
            if (index > -1) {
                commit("file/splice", {index, data: Object.assign(base, state.fileLists[index], data)})
            } else {
                commit("file/push", Object.assign(base, data))
            }
        }
    },

    /**
     * 忘记文件数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetFile({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetFile", data)
        //
        const ids = $A.isArray(data.id) ? data.id : [data.id];
        ids.some(id => {
            commit("file/save", state.fileLists.filter(file => file.id != id))
            state.fileLists.some(file => {
                if (file.pid == id) {
                    dispatch("forgetFile", file);
                }
            });
        })
    },

    /**
     * 获取压缩进度
     * @param state
     * @param dispatch
     * @param data
     */
    packProgress({state, dispatch}, data) {
        $A.syncDispatch("packProgress", data)
        //
        const index = state.filePackLists.findIndex(({name}) => name == data.name);
        if (index > -1) {
            state.filePackLists[index].progress = data.progress;
        } else {
            state.filePackLists.push(data);
        }
    },

    /**
     * 获取文件
     * @param commit
     * @param state
     * @param dispatch
     * @param pid
     * @returns {Promise<unknown>}
     */
    getFiles({commit, state, dispatch}, pid) {
        return new Promise(function (resolve, reject) {
            dispatch("call", {
                url: 'file/lists',
                data: {
                    pid
                },
            }).then((result) => {
                const ids = result.data.map(({id}) => id)
                commit("file/save", state.fileLists.filter((item) => item.pid != pid || ids.includes(item.id)));
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

    /**
     * 获取有效的部门负责人视角部门ID
     * @param state
     * @param ids
     * @returns {Array<number>}
     */
    normalizeDepartmentOwnerIds({state}, ids) {
        const validIds = (state.userInfo.managed_departments || []).map(item => parseInt(item.id));
        if (!$A.isArray(ids)) ids = [];
        return ids
            .map(id => parseInt(id))
            .filter(id => id > 0 && (validIds.length === 0 || validIds.includes(id)));
    },

    /**
     * 加载负责人视角下的项目列表
     * @param state
     * @param dispatch
     * @returns {Promise<void>}
     */
    async getProjectsForDepartmentOwnerView({state, dispatch, commit}) {
        await dispatch("systemSetting").catch(() => {});
        if (state.systemConfig.department_owner_project_view !== 'open') {
            commit('department/owner/ids/save', []);
            dispatch("getProjectByQueue");
            return;
        }
        const restoredDepartmentOwnerIds = await dispatch("restoreDepartmentOwnerView");
        if ((restoredDepartmentOwnerIds || []).length > 0) {
            await dispatch("getProjects", {
                __replace: true,
                department_owner_ids: restoredDepartmentOwnerIds.join(',')
            });
            commit('department/owner/ids/save', restoredDepartmentOwnerIds);
            return;
        }
        dispatch("getProjectByQueue");
    },

    /**
     * 恢复部门负责人视角
     * @param state
     * @param dispatch
     * @returns {Promise<void>}
     */
    async restoreDepartmentOwnerView({state, dispatch, commit}) {
        if (state.departmentOwnerViewRestored) {
            return [];
        }
        if (state.systemConfig.department_owner_project_view !== 'open') {
            commit('department/owner/ids/save', []);
            return [];
        }
        state.departmentOwnerViewRestored = true;
        const ids = await $A.IDBArray("cacheDepartmentOwnerIds", []);
        if (!ids.length) {
            return [];
        }
        const restored = await dispatch("normalizeDepartmentOwnerIds", ids);
        if (restored.length > 0) {
            state.departmentOwnerProjectsRefreshing = true;
        }
        commit('department/owner/ids/save', restored);
        return restored;
    },

    /**
     * 设置部门负责人视角
     * @param state
     * @param dispatch
     * @param ids
     * @returns {Promise<void>}
     */
    async setDepartmentOwnerIds({state, dispatch, commit}, ids) {
        if (state.systemConfig.department_owner_project_view !== 'open') {
            ids = [];
        }
        const normalized = await dispatch("normalizeDepartmentOwnerIds", ids);
        const oldValue = (state.cacheDepartmentOwnerIds || []).slice().sort().join(',');
        const newValue = normalized.slice().sort().join(',');
        if (oldValue === newValue) {
            return;
        }
        state.departmentOwnerProjectsRefreshing = true;
        await dispatch("refreshDepartmentOwnerProjects", normalized);
        commit('department/owner/ids/save', normalized);
    },

    /**
     * 切换部门负责人视角后刷新项目数据
     * @param state
     * @param dispatch
     * @returns {Promise<void>}
     */
    async refreshDepartmentOwnerProjects({state, dispatch}, ownerIds = state.cacheDepartmentOwnerIds) {
        const currentProjectId = state.projectId;
        ownerIds = (ownerIds || []).map(id => parseInt(id)).filter(id => id > 0);
        state.departmentOwnerProjectsRefreshing = true;
        state.callAt = state.callAt.filter(item => {
            const key = String(item.key);
            return !key.startsWith('projects::') && !key.startsWith('tasks::');
        });
        try {
            await dispatch("getProjects", {
                __replace: true,
                department_owner_ids: ownerIds.join(',')
            });
            if (currentProjectId > 0) {
                const exists = state.cacheProjects.find(({id}) => id == currentProjectId);
                if (!exists) {
                    const project = $A.cloneJSON(state.cacheProjects).sort((a, b) => {
                        if (a.top_at || b.top_at) {
                            return $A.sortDay(b.top_at, a.top_at);
                        }
                        return b.id - a.id;
                    }).find(({id}) => id);
                    if (project) {
                        $A.goForward({name: 'manage-project', params: {projectId: project.id}});
                    } else {
                        $A.goForward({name: 'manage-dashboard'});
                    }
                    return;
                }
                await dispatch("getProjectOne", currentProjectId).catch(() => {});
                await dispatch("getTaskForProject", currentProjectId).catch(() => {});
            }
        } catch (e) {
            if ((state.cacheDepartmentOwnerIds || []).length === 0 && currentProjectId > 0) {
                $A.goForward({name: 'manage-dashboard'});
            }
        } finally {
            state.departmentOwnerProjectsRefreshing = false;
        }
    },

    /** *****************************************************************************************/
    /** ************************************** 项目 **********************************************/
    /** *****************************************************************************************/

    /**
     * 保存项目数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveProject({commit, state, dispatch}, data) {
        $A.syncDispatch("saveProject", data)
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
                commit("project/splice", {index, data: Object.assign({}, state.cacheProjects[index], data)})
            } else {
                if (typeof data.project_user === "undefined") {
                    data.project_user = []
                }
                commit("project/push", data)
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
        }
    },

    /**
     * 忘记项目数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetProject({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetProject", data)
        //
        const ids = $A.isArray(data.id) ? data.id : [data.id];
        ids.some(id => {
            const index = state.cacheProjects.findIndex(project => project.id == id);
            if (index > -1) {
                dispatch("forgetTask", {id: state.cacheTasks.filter(item => item.project_id == data.id).map(item => item.id)})
                dispatch("forgetColumn", {id: state.cacheColumns.filter(item => item.project_id == data.id).map(item => item.id)})
                commit("project/splice", {index})
                state.projectTotal = Math.max(0, state.projectTotal - 1)
            }
        })
        if (ids.includes(state.projectId)) {
            const project = $A.cloneJSON(state.cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.sortDay(b.top_at, a.top_at);
                }
                return b.id - a.id;
            }).find(({id}) => id && id != data.id);
            if (project) {
                $A.goForward({name: 'manage-project', params: {projectId: project.id}});
            } else {
                $A.goForward({name: 'manage-dashboard'});
            }
        }
    },

    /**
     * 获取项目
     * @param state
     * @param dispatch
     * @param requestData
     * @returns {Promise<unknown>}
     */
    getProjects({state, dispatch}, requestData) {
        if (!$A.isJson(requestData)) {
            requestData = {}
        }
        const replace = requestData.__replace === true;
        delete requestData.__replace;
        if (state.systemConfig.department_owner_project_view !== 'open') {
            delete requestData.department_owner_ids
        } else if (requestData.department_owner_ids === undefined) {
            if ((state.cacheDepartmentOwnerIds || []).length > 0) {
                requestData.department_owner_ids = state.cacheDepartmentOwnerIds.join(',')
            } else {
                delete requestData.department_owner_ids
            }
        }
        if (replace) {
            state.callAt = state.callAt.filter(item => !String(item.key).startsWith('projects::'))
            $A.IDBSet("callAt", state.callAt).catch(() => {})
        }
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
                if (replace) {
                    state.cacheProjects = [];
                }
                dispatch("saveProject", data.data);
                callData.save(data).then(ids => dispatch("forgetProject", {id: ids}))
                state.projectTotal = data.total_all;
                //
                resolve(data)
            }).catch(e => {
                console.warn(e);
                reject(e)
            }).finally(_ => {
                if (replace) {
                    state.departmentOwnerProjectsRefreshing = false;
                }
                state.loadProjects--;
            });
        });
    },

    /**
     * 获取项目（队列）
     * @param dispatch
     * @param timeout
     */
    getProjectByQueue({dispatch}, timeout = null) {
        window.__getProjectByQueueTimer && clearTimeout(window.__getProjectByQueueTimer)
        if (typeof timeout === "number") {
            window.__getProjectByQueueTimer = setTimeout(_ => dispatch("getProjectByQueue", null), timeout)
            return
        }
        dispatch("getProjects").catch(() => {});
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
                    project_id
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
                dispatch("forgetProject", {id: project_id})
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
                dispatch("forgetProject", {id: project_id})
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
                dispatch("forgetProject", {id: project_id})
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
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveColumn({commit, state, dispatch}, data) {
        $A.syncDispatch("saveColumn", data)
        //
        if ($A.isArray(data)) {
            data.forEach((column) => {
                dispatch("saveColumn", column)
            });
        } else if ($A.isJson(data)) {
            const index = state.cacheColumns.findIndex(({id}) => id == data.id);
            if (index > -1) {
                commit("project/column/splice", {index, data: Object.assign({}, state.cacheColumns[index], data)})
            } else {
                commit("project/column/push", data)
            }
        }
    },

    /**
     * 忘记列表数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetColumn({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetColumn", data)
        //
        const ids = $A.isArray(data.id) ? data.id : [data.id];
        const project_ids = [];
        ids.some(id => {
            const index = state.cacheColumns.findIndex(column => column.id == id);
            if (index > -1) {
                dispatch("forgetTask", {id: state.cacheTasks.filter(item => item.column_id == data.id).map(item => item.id)})
                project_ids.push(state.cacheColumns[index].project_id)
                commit("project/column/splice", {index})
            }
        })
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id).catch(() => {}))
    },

    /**
     * 获取列表
     * @param commit
     * @param state
     * @param dispatch
     * @param project_id
     * @returns {Promise<unknown>}
     */
    getColumns({commit, state, dispatch}, project_id) {
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
                commit("project/column/save", state.cacheColumns.filter((item) => item.project_id != project_id || ids.includes(item.id)))
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
                dispatch("forgetColumn", {id: column_id})
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
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveTask({commit, state, dispatch}, data) {
        $A.syncDispatch("saveTask", data)
        //
        if ($A.isArray(data)) {
            data.forEach((task) => {
                dispatch("saveTask", task)
            });
        } else if ($A.isJson(data)) {
            data._time = $A.dayjs().unix();
            //
            if (data.flow_item_name && data.flow_item_name.indexOf("|") !== -1) {
                const flowInfo = $A.convertWorkflow(data.flow_item_name)
                data.flow_item_status = flowInfo.status;
                data.flow_item_name = flowInfo.name;
                data.flow_item_color = flowInfo.color;
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
                commit("task/splice", {index, data: Object.assign({}, state.cacheTasks[index], data)});
            } else {
                commit("task/push", data);
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
        }
    },

    /**
     * 忘记任务数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetTask({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetTask", data)
        //
        const ids = ($A.isArray(data.id) ? data.id : [data.id]).filter(id => id != state.taskArchiveView);
        const parent_ids = [];
        const project_ids = [];
        ids.some(id => {
            const index = state.cacheTasks.findIndex(task => task.id == id);
            if (index > -1) {
                if (state.cacheTasks[index].parent_id) {
                    parent_ids.push(state.cacheTasks[index].parent_id)
                }
                project_ids.push(state.cacheTasks[index].project_id)
                commit("task/splice", {index})
            }
            state.cacheTasks.filter(task => task.parent_id == id).some(childTask => {
                let cIndex = state.cacheTasks.findIndex(task => task.id == childTask.id);
                if (cIndex > -1) {
                    project_ids.push(childTask.project_id)
                    commit("task/splice", {index: cIndex})
                }
            })
        })
        Array.from(new Set(parent_ids)).some(id => dispatch("getTaskOne", id).catch(() => {}))
        Array.from(new Set(project_ids)).some(id => dispatch("getProjectOne", id).catch(() => {}))
        //
        if (ids.includes(state.taskId)) {
            state.taskId = 0;
        }
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
     * @param commit
     * @param data
     */
    increaseTaskMsgNum({state, commit}, data) {
        $A.syncDispatch("increaseTaskMsgNum", data)
        //
        const index = state.cacheTasks.findIndex(item => item.dialog_id === data.id);
        if (index !== -1) {
            const newData = $A.cloneJSON(state.cacheTasks[index])
            newData.msg_num++;
            commit("task/splice", {index, data: newData})
        }
    },

    /**
     * 新增回复数量
     * @param state
     * @param commit
     * @param data
     */
    increaseMsgReplyNum({state, commit}, data) {
        $A.syncDispatch("increaseMsgReplyNum", data)
        //
        const index = state.dialogMsgs.findIndex(m => m.id == data.id)
        if (index !== -1) {
            const newData = $A.cloneJSON(state.dialogMsgs[index])
            newData.reply_num++
            commit("message/splice", {index, data: newData})
        }
    },

    /**
     * 减少回复数量
     * @param state
     * @param commit
     * @param data
     */
    decrementMsgReplyNum({state, commit}, data) {
        $A.syncDispatch("decrementMsgReplyNum", data)
        //
        const index = state.dialogMsgs.findIndex(m => m.id == data.id)
        if (index !== -1) {
            const newData = $A.cloneJSON(state.dialogMsgs[index])
            newData.reply_num--
            commit("message/splice", {index, data: newData})
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
        if (!$A.isJson(requestData)) {
            requestData = {}
        }
        if ((state.cacheDepartmentOwnerIds || []).length > 0) {
            requestData.department_owner_ids = state.cacheDepartmentOwnerIds.join(',')
        } else {
            delete requestData.department_owner_ids
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
                callData.save(data).then(ids => dispatch("forgetTask", {id: ids}))
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
     * 获取任务的子任务数据
     * @param state
     * @param dispatch
     * @param taskId
     */
    getTaskSubData({state, dispatch}, taskId) {
        if (!taskId) {
            return;
        }
        const parentTask = state.cacheTasks.find(({id}) => id == taskId);
        if (!parentTask) {
            return;
        }
        dispatch("call", {
            url: 'project/task/subdata',
            data: {
                task_id: taskId
            },
        }).then(({data}) => {
            dispatch("saveTask", Object.assign(parentTask, data))
        }).catch(e => {
            console.warn(e);
        });
    },

    /**
     * 获取Dashboard相关任务
     * @param state
     * @param dispatch
     * @param timeout
     */
    getTaskForDashboard({state, dispatch}, timeout) {
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
                dispatch("forgetTask", {id: data.task_id})
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
     * 子任务升级为主任务
     * @param dispatch
     * @param data Number|JSONObject{task_id}
     * @returns {Promise<unknown>}
     */
    taskConvertToMain({dispatch}, data) {
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
                url: 'project/task/upgrade',
                data,
            }).then(result => {
                const {task, parent} = result.data || {};
                if (task) {
                    dispatch("saveTask", task);
                }
                if (parent) {
                    dispatch("saveTask", parent);
                }
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
                task_id
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
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveTaskContent({commit, state, dispatch}, data) {
        $A.syncDispatch("saveTaskContent", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveTaskContent", item)
            });
        } else if ($A.isJson(data)) {
            const index = state.taskContents.findIndex(({task_id}) => task_id == data.task_id);
            if (index > -1) {
                commit("task/content/splice", {index, data: Object.assign({}, state.taskContents[index], data)})
            } else {
                commit("task/content/push", data)
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
                task_id
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
                $A.Electron.sendMessage('updateWindow', {
                    name: `task-${task_id}`,
                    path: `/single/task/${task_id}`,
                });
            } else {
                $A.Electron.sendMessage('windowClose');
            }
            return
        }
        if (state.taskId > 0) {
            emitter.emit('handleMoveTop', 'taskModal');   // 已打开任务时将任务窗口置顶
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
                spinner: 600,
                method: 'post',
            }).then(result => {
                if (result.data.is_visible === 1) {
                    dispatch("addTaskSuccess", result.data)
                }
                state.taskLatestId = result.data.id
                resolve(result)
            }).catch(e => {
                console.warn(e);
                reject(e);
            });
        });
    },

    /**
     * 获取任务关联列表
     * @param state
     * @param dispatch
     * @param commit
     * @param taskId
     * @returns {Promise<unknown>}
     */
    getTaskRelated({state, commit, dispatch}, taskId) {
        taskId = parseInt(taskId, 10);
        if (!taskId) {
            return Promise.resolve([]);
        }
        return new Promise((resolve, reject) => {
            dispatch("call", {
                url: 'project/task/related',
                data: {task_id: taskId},
            }).then(({data}) => {
                const list = (data.list || []).map(item => ({
                    ...item,
                    mention: !!item.mention,
                    mentioned_by: !!item.mentioned_by,
                }));
                commit('task/related/save', {
                    taskId,
                    list,
                    updatedAt: Date.now(),
                });
                resolve(list);
            }).catch(reject);
        });
    },

    deleteTaskRelated({commit, dispatch}, {taskId, relatedTaskId}) {
        return new Promise((resolve, reject) => {
            dispatch("call", {
                url: 'project/task/related/delete',
                data: {task_id: taskId, related_task_id: relatedTaskId},
            }).then(({msg}) => {
                commit('task/related/clear', taskId);
                commit('task/related/clear', relatedTaskId);
                resolve(msg);
            }).catch(reject);
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
                spinner: 600,
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
        dispatch("getTaskSubData", task.parent_id)
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
                    dispatch("getTaskSubData", result.data.parent_id)
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
                                flow_item_name: `${item.status}|${item.name}|${item.color}`,
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
     * @param timeout
     */
    getTaskPriority({state, dispatch}, timeout) {
        window.__getTaskPriority && clearTimeout(window.__getTaskPriority)
        window.__getTaskPriority = setTimeout(() => {
            dispatch("call", {
                url: 'system/priority',
            }).then(result => {
                state.taskPriority = result.data;
            }).catch(e => {
                console.warn(e);
            });
        }, typeof timeout === "number" ? timeout : 1000)
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
     * @param dispatch
     * @param task_id
     */
    saveTaskBrowse({dispatch}, task_id) {
        // 直接调用API保存到远程，不维护本地缓存
        dispatch('call', {
            url: 'users/task/browse_save',
            data: {
                task_id: task_id
            },
        }).catch(error => {
            console.warn('保存任务浏览历史失败:', error);
        });
    },

    /**
     * 获取任务浏览历史
     * @param dispatch
     * @param limit
     */
    getTaskBrowseHistory({dispatch}, limit = 20) {
        return dispatch('call', {
            url: 'users/task/browse',
            data: {
                limit: limit
            },
            method: 'get',
        });
    },

    /**
     * 获取最近浏览历史
     * @param dispatch
     * @param params
     * @returns {Promise<unknown>}
     */
    getRecentBrowseHistory({dispatch}, params = {}) {
        return dispatch('call', {
            url: 'users/recent/browse',
            data: params,
            method: 'get',
        });
    },

    /**
     * 删除最近浏览记录
     * @param dispatch
     * @param id
     * @returns {Promise<unknown>}
     */
    removeRecentBrowseRecord({dispatch}, id) {
        return dispatch('call', {
            url: 'users/recent/delete',
            data: {id},
            method: 'post',
        });
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
            if ($A.isArray(array) && array.length === 2) {
                if (/\s+(00:00|23:59)$/.test(array[0]) && /\s+(00:00|23:59)$/.test(array[1])) {
                    array[0] = await dispatch("taskDefaultStartTime", array[0])
                    array[1] = await dispatch("taskDefaultEndTime", array[1])
                }
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

    /**
     * 拉取当前用户跨项目可见的全部任务模板。
     * 替代旧版按项目隔离取数。state.taskTemplates 现在存"我所有可见模板"（全量）。
     *
     * @param {Object} ctx
     * @param {Number|null} currentProjectId  当前所在项目 ID（用于排序优先；可空）
     * @returns {Promise<void>}
     */
    async updateTaskTemplates({state, dispatch}, currentProjectId) {
        const project = (state.cacheProjects || []).find(({id}) => id == currentProjectId)
        if (project && project.task_template_share === 'close') {
            const {data} = await dispatch("call", {
                url: 'project/task/template_list',
                data: {
                    project_id: currentProjectId || 0,
                },
            })
            state.taskTemplates = Array.isArray(data) ? data : []
            return
        }
        const {data} = await dispatch("call", {
            url: 'project/task/template_visible',
            data: {
                current_project_id: currentProjectId || 0,
            },
        })
        state.taskTemplates = Array.isArray(data) ? data : []
    },

    /** *****************************************************************************************/
    /** ************************************** 收藏 **********************************************/
    /** *****************************************************************************************/

    /**
     * 检查收藏状态
     * @param dispatch
     * @param {object} params {type: 'task|project|file|message', id: number}
     */
    checkFavoriteStatus({dispatch}, {type, id}) {
        return dispatch('call', {
            url: 'users/favorite/check',
            data: {
                type: type,
                id: id
            },
            method: 'get',
        });
    },

    /**
     * 切换收藏状态
     * @param dispatch
     * @param {object} params {type: 'task|project|file|message', id: number}
     */
    toggleFavorite({dispatch}, {type, id}) {
        return new Promise((resolve, reject) => {
            dispatch('call', {
                url: 'users/favorite/toggle',
                data: {
                    type: type,
                    id: id
                },
                method: 'post',
            }).then(result => {
                resolve(result)
                //
                const {data, msg} = result
                if (!data.favorited) {
                    $A.messageSuccess(msg);
                    return
                }
                $A.Message.success({
                    duration: 5,
                    render: h => {
                        return h('span', [
                            h('span', $A.L(msg)),
                            h('a', {
                                style: {
                                    marginLeft: '8px'
                                },
                                on: {
                                    click: () => {
                                        const currentRemark = data && typeof data.remark === 'string' ? data.remark : '';
                                        $A.modalInput({
                                            title: $A.L('修改备注'),
                                            placeholder: $A.L('请输入修改备注'),
                                            okText: $A.L('保存'),
                                            value: currentRemark,
                                            onOk: (inputValue) => {
                                                const remark = typeof inputValue === 'string' ? inputValue.trim() : '';
                                                if (!remark) {
                                                    return $A.L('请输入修改备注');
                                                }
                                                return new Promise((resolveRemark, rejectRemark) => {
                                                    dispatch('call', {
                                                        url: 'users/favorite/remark',
                                                        data: {
                                                            type,
                                                            id,
                                                            remark,
                                                        },
                                                        method: 'post',
                                                    }).then(({msg}) => {
                                                        $A.messageSuccess(msg || $A.L('操作成功'));
                                                        resolveRemark();
                                                    }).catch(({msg}) => {
                                                        rejectRemark(msg || $A.L('操作失败'));
                                                    });
                                                });
                                            }
                                        });
                                    }
                                }
                            }, $A.L('修改备注')),
                        ])
                    }
                });
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('操作失败'));
                reject()
            });
        });
    },

    /**
     * 批量检查收藏状态
     * @param dispatch
     * @param {object} params {type: 'task|project|file|message', items: array}
     */
    checkFavoritesStatus({dispatch}, {type, items}) {
        if (!Array.isArray(items) || items.length === 0) {
            return Promise.resolve([]);
        }

        // 批量检查收藏状态
        const promises = items.map(item => {
            return dispatch('checkFavoriteStatus', {type, id: item.id})
                .then(({data}) => ({
                    id: item.id,
                    favorited: data.favorited || false
                }))
                .catch(() => ({
                    id: item.id,
                    favorited: false
                }));
        });

        return Promise.all(promises);
    },

    /** *****************************************************************************************/
    /** ************************************** 会话 **********************************************/
    /** *****************************************************************************************/

    /**
     * 更新会话数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialog({commit, state, dispatch}, data) {
        $A.syncDispatch("saveDialog", data)
        //
        if ($A.isArray(data)) {
            data.forEach((dialog) => {
                dispatch("saveDialog", dialog)
            });
        } else if ($A.isJson(data)) {
            data.id = parseInt(data.id)
            const index = state.cacheDialogs.findIndex(({id}) => id == data.id);
            let lastForce = false
            if (typeof data.last_force !== "undefined") {
                lastForce = true
                delete data.last_force
            }
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
                if (!lastForce
                    && data.last_at
                    && original.last_at
                    && $A.dayjs(data.last_at) < $A.dayjs(original.last_at)) {
                    delete data.last_at
                    delete data.last_msg
                }
                commit("dialog/splice", {index, data: Object.assign({}, original, data)})
            } else {
                commit("dialog/push", data)
            }
        }
    },

    /**
     * 更新会话最后消息
     * @param state
     * @param dispatch
     * @param data
     */
    updateDialogLastMsg({state, dispatch}, data) {
        $A.syncDispatch("updateDialogLastMsg", data)
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
     * @param requestData
     * @returns {Promise<unknown>}
     */
    getDialogs({state, dispatch}, requestData) {
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
                callData.save(data).then(ids => dispatch("forgetDialog", {id: ids}))
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
                    return $A.sortDay(a.last_at, b.last_at);
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
     * @param dialogId
     * @returns {Promise<unknown>}
     */
    getDialogOne({state, dispatch}, dialogId) {
        return new Promise(function (resolve, reject) {
            if ($A.runNum(dialogId) === 0) {
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("call", {
                url: 'dialog/one',
                data: {
                    dialog_id: dialogId,
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
     * @param commit
     * @param state
     * @param dispatch
     * @param dialogId
     */
    getDialogTodo({commit, state, dispatch}, dialogId) {
        dispatch("call", {
            url: 'dialog/todo',
            data: {
                dialog_id: dialogId,
            },
        }).then(({data}) => {
            if ($A.arrayLength(data) > 0) {
                if (dialogId > 0) {
                    dispatch("saveDialog", {
                        id: dialogId,
                        todo_num: $A.arrayLength(data)
                    });
                    commit("dialog/todo/save", state.dialogTodos.filter(item => item.dialog_id != dialogId))
                }
                dispatch("saveDialogTodo", data)
            } else {
                if (dialogId > 0) {
                    dispatch("saveDialog", {
                        id: dialogId,
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
     * @param dialogId
     */
    getDialogMsgTop({state, dispatch}, dialogId) {
        dispatch("call", {
            url: 'dialog/msg/topinfo',
            data: {
                dialog_id: dialogId,
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
     * @param dialogId
     * @returns {Promise<unknown>}
     */
    openDialog({state, dispatch}, dialogId) {
        return new Promise(async (resolve, reject) => {
            let singleWindow,
                searchMsgId,
                dialogMsgId;
            if ($A.isJson(dialogId)) {
                singleWindow = dialogId.single;
                searchMsgId = dialogId.search_msg_id;
                dialogMsgId = dialogId.dialog_msg_id;
                dialogId = dialogId.dialog_id;
            }
            singleWindow = typeof singleWindow === "boolean" ? singleWindow : $A.isSubElectron;
            searchMsgId = /^\d+$/.test(searchMsgId) ? parseInt(searchMsgId) : 0;
            dialogMsgId = /^\d+$/.test(dialogMsgId) ? parseInt(dialogMsgId) : 0;
            dialogId = /^\d+$/.test(dialogId) ? parseInt(dialogId) : 0;
            //
            if (dialogId > 0 && state.cacheDialogs.findIndex(item => item.id == dialogId) === -1) {
                dispatch("showSpinner", 300)
                try {
                    await dispatch("getDialogOne", dialogId)
                } catch (e) {
                    reject(e);
                    return;
                } finally {
                    dispatch("hiddenSpinner")
                }
            }
            //
            if ($A.Electron && singleWindow) {
                dispatch('openDialogNewWindow', dialogId);
                resolve()
                return
            }
            //
            if (state.dialogModalShow) {
                // 已打开对话时将对话窗口置顶
                emitter.emit('handleMoveTop', 'dialogModal');
            } else if (state.dialogId === dialogId) {
                // 如果对话窗口未打开，则清除当前对话ID（避免类此：已经在消息中打开项目对话时无法在其他地方打开项目对话）
                state.dialogId = 0;
            }
            //
            requestAnimationFrame(_ => {
                state.dialogSearchMsgId = searchMsgId;
                state.dialogMsgId = dialogMsgId;
                state.dialogId = dialogId;
                resolve()
            })
        })
    },

    /**
     * 打开会话（通过会员ID打开个人会话）
     * @param state
     * @param dispatch
     * @param userid
     */
    openDialogUserid({state, dispatch}, userid) {
        return new Promise((resolve, reject) => {
            const dialog = state.cacheDialogs.find(item => {
                if (item.type !== 'user' || !item.dialog_user) {
                    return false
                }
                return item.dialog_user.userid === userid
            });
            if (dialog) {
                return dispatch("openDialog", dialog.id).then(resolve).catch(reject)
            }
            dispatch("call", {
                url: 'dialog/open/user',
                data: {
                    userid,
                },
                spinner: 600
            }).then(async ({data}) => {
                dispatch("saveDialog", data);
                dispatch("openDialog", data.id).then(resolve).catch(reject)
            }).catch(e => {
                console.warn(e);
                reject(e);
            })
        });
    },

    /**
     * 打开会话事件
     * @param state
     * @param dispatch
     * @param dialogId
     * @returns {Promise<unknown>}
     */
    openDialogEvent({state, dispatch}, dialogId) {
        return new Promise((resolve, reject) => {
            if (!dialogId) {
                reject({msg: 'Parameter error'});
                return;
            }
            dispatch("call", {
                url: 'dialog/open/event',
                data: {
                    dialog_id: dialogId,
                },
            }).catch(e => {
                console.warn(e);
                reject(e);
            })
        });
    },

    /**
     * 打开会话（客户端新窗口）
     * @param state
     * @param dispatch
     * @param dialogId
     * @returns {Promise<void>}
     */
    openDialogNewWindow({state, dispatch}, dialogId) {
        if ($A.runNum(dialogId) <= 0) {
            return
        }
        const dialogData = state.cacheDialogs.find(({id}) => id === dialogId) || {}
        dispatch('openWindow', {
            name: `dialog-${dialogId}`,
            path: `/single/dialog/${dialogId}`,
            mode: 'window',
            title: dialogData.name,
            width: Math.min(window.screen.availWidth, 1024),
            height: Math.min(window.screen.availHeight, 768),
        });
    },

    /**
     * 忘记对话数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetDialog({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetDialog", data)
        //
        const ids = $A.isArray(data.id) ? data.id : [data.id];
        ids.some(id => {
            if ($A.isJson(id)) {
                id = id.id
            }
            const index = state.cacheDialogs.findIndex(dialog => dialog.id == id);
            if (index > -1) {
                dispatch("forgetDialogMsg", {id: state.dialogMsgs.filter(item => item.dialog_id == data.id).map(item => item.id)})
                commit("dialog/splice", {index})
            }
        })
        if (ids.includes(state.dialogId)) {
            state.dialogId = 0
        }
    },

    /**
     * 保存正在会话
     * @param commit
     * @param state
     * @param dispatch
     * @param data {uid, dialog_id}
     */
    saveInDialog({commit, state, dispatch}, data) {
        $A.syncDispatch("saveInDialog", data)
        //
        const index = state.dialogIns.findIndex(item => item.uid == data.uid);
        if (index > -1) {
            commit("dialog/in/splice", {index, data: Object.assign({}, state.dialogIns[index], data)});
        } else {
            commit("dialog/in/push", data);
        }
        // 会话消息总数量大于5000时只保留最近打开的50个会话
        const msg_max = 5000
        const retain_num = 500
        commit('dialog/history/save', state.dialogHistory.filter(id => id != data.dialog_id))
        commit('dialog/history/push', data.dialog_id)
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
                commit("message/save", state.dialogMsgs.filter(item => !delIds.includes(item.dialog_id)))
            }
            commit('dialog/history/save', newIds)
        }
    },

    /**
     * 忘记正在会话
     * @param state
     * @param commit
     * @param data
     */
    forgetInDialog({state, commit}, data) {
        $A.syncDispatch("forgetInDialog", data)
        //
        const index = state.dialogIns.findIndex(item => item.uid == data.uid);
        if (index > -1) {
            commit("dialog/in/splice", {index})
        }
    },

    /**
     * 关闭对话
     * @param state
     * @param commit
     * @param data
     */
    closeDialog({state, commit}, data) {
        $A.syncDispatch("closeDialog", data)

        // 判断参数
        if (!/^\d+$/.test(data.id)) {
            return
        }

        // 更新草稿标签
        commit('draft/tag', data.id)

        // 关闭会话后删除会话超限消息
        const msgs = state.dialogMsgs.filter(item => item.dialog_id == data.id)
        if (msgs.length > state.dialogMsgKeep) {
            const delIds = msgs.sort((a, b) => b.id - a.id).splice(state.dialogMsgKeep).map(item => item.id)
            commit("message/save", state.dialogMsgs.filter(item => !delIds.includes(item.id)))
        }
    },

    /**
     * 清理会话本地缓存
     * @param state
     * @param commit
     * @param data
     */
    clearDialogMsgs({state, commit}, data) {
        $A.syncDispatch("clearDialogMsgs", data)

        // 判断参数
        if (!/^\d+$/.test(data.id)) {
            return
        }

        // 清理会话本地缓存
        commit("message/save", state.dialogMsgs.filter(item => item.dialog_id != data.id))
    },

    /**
     * 保存待办数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogTodo({commit, state, dispatch}, data) {
        $A.syncDispatch("saveDialogTodo", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveDialogTodo", item)
            });
        } else if ($A.isJson(data)) {
            const index = state.dialogTodos.findIndex(item => item.id == data.id);
            if (index > -1) {
                commit('dialog/todo/splice', {index, data: Object.assign({}, state.dialogTodos[index], data)});
            } else {
                commit('dialog/todo/push', data)
            }
        }
    },

    /**
     * 忘记待办数据
     * @param state
     * @param commit
     * @param data
     */
    forgetDialogTodoForMsgId({state, commit}, data) {
        $A.syncDispatch("forgetDialogTodoForMsgId", data)
        //
        const index = state.dialogTodos.findIndex(item => item.msg_id == data.id);
        if (index > -1) {
            commit('dialog/todo/splice', {index})
        }
    },

    /**
     * 保存置顶数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogMsgTop({commit, state, dispatch}, data) {
        $A.syncDispatch("saveDialogMsgTop", data)
        //
        if ($A.isArray(data)) {
            data.forEach(item => {
                dispatch("saveDialogMsgTop", item)
            });
        } else if ($A.isJson(data)) {
            commit('dialog/msg/top/save', state.dialogMsgTops.filter(item => item.dialog_id != data.dialog_id))
            const index = state.dialogMsgTops.findIndex(item => item.id == data.id);
            if (index > -1) {
                commit('dialog/msg/top/splice', {index, data: Object.assign({}, state.dialogMsgTops[index], data)});
            } else {
                commit('dialog/msg/top/push', data)
            }
        }
    },

    /**
     * 忘记消息置顶数据
     * @param state
     * @param commit
     * @param data
     */
    forgetDialogMsgTopForMsgId({state, commit}, data) {
        $A.syncDispatch("forgetDialogMsgTopForMsgId", data)
        //
        const index = state.dialogMsgTops.findIndex(item => item.msg_id == data.id);
        if (index > -1) {
            commit('dialog/msg/top/splice', {index})
        }
    },

    /**
     * 保存草稿
     * @param commit
     * @param id
     * @param content
     * @param immediate
     * @param args
     */
    saveDialogDraft({commit}, {id, content, immediate = false, ...args}) {
        if ($A.isSubElectron) {
            dialogDraftState.subTemp = {id, content, immediate: true}
            return
        }
        $A.syncDispatch("saveDialogDraft", {...args, id, content, immediate})

        // 清除已有的计时器
        if (dialogDraftState.timer[id]) {
            clearTimeout(dialogDraftState.timer[id])
            delete dialogDraftState.timer[id]
        }

        // 创建新的计时器
        dialogDraftState.timer[id] = setTimeout(() => {
            commit('draft/set', {id, content})
            delete dialogDraftState.timer[id]
        }, (immediate || !content) ? 0 : 600)
    },

    /**
     * 保存引用
     * @param commit
     * @param data {id, type, content}
     */
    saveDialogQuote({commit}, data) {
        commit('quote/set', data)
    },

    /**
     * 移除引用
     * @param commit
     * @param id
     */
    removeDialogQuote({commit}, id) {
        commit('quote/remove', id)
    },

    /** *****************************************************************************************/
    /** ************************************** 消息 **********************************************/
    /** *****************************************************************************************/

    /**
     * 更新消息数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    saveDialogMsg({commit, state, dispatch}, data) {
        $A.syncDispatch("saveDialogMsg", data)
        //
        if ($A.isArray(data)) {
            data.forEach((msg) => {
                dispatch("saveDialogMsg", msg)
            });
            return
        }
        //
        if (data.type == "notice") {
            data.estimateSize = 42;
        }
        const index = state.dialogMsgs.findIndex(({id}) => id == data.id);
        if (index > -1) {
            const original = state.dialogMsgs[index]
            if (original.read_at) {
                delete data.read_at
            }
            data = Object.assign({}, original, data)
            commit("message/splice", {index, data})
        } else {
            commit("message/push", data)
        }
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
    },

    /**
     * 忘记消息数据
     * @param commit
     * @param state
     * @param dispatch
     * @param data
     */
    forgetDialogMsg({commit, state, dispatch}, data) {
        $A.syncDispatch("forgetDialogMsg", data)
        //
        const ids = $A.isArray(data.id) ? data.id : [data.id];
        ids.some(id => {
            const index = state.dialogMsgs.findIndex(item => item.id == id);
            if (index > -1) {
                const msgData = state.dialogMsgs[index]
                dispatch("decrementMsgReplyNum", {id: msgData.reply_id});
                dispatch("audioStop", $A.getObject(msgData, 'msg.path'));
                commit("message/splice", {index})
            }
        })
        dispatch("forgetDialogTodoForMsgId", data)
        dispatch("forgetDialogMsgTopForMsgId", data)
    },

    /**
     * 获取会话消息
     * @param commit
     * @param state
     * @param dispatch
     * @param getters
     * @param data {dialog_id, msg_id, ?msg_type, ?position_id, ?prev_id, ?next_id, ?save_before, ?save_after, ?clear_before, ?spinner}
     * @returns {Promise<unknown>}
     */
    getDialogMsgs({commit, state, dispatch, getters}, data) {
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
                commit("message/save", state.dialogMsgs.filter(({dialog_id}) => dialog_id !== data.dialog_id))
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
                    commit("message/save", state.dialogMsgs.filter(item => {
                        return item.dialog_id != data.dialog_id || ids.includes(item.id) || $A.dayjs(item.created_at).unix() >= resData.time
                    }))
                    dispatch("saveDialog", resData.dialog)
                }
                if ($A.isArray(resData.todo)) {
                    commit("dialog/todo/save", state.dialogTodos.filter(item => item.dialog_id != data.dialog_id))
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
                data && (data.read_at = null);
                return;
            }
            const entries = Object.entries(state.readWaitData);
            if (entries.length === 0) {
                data && (data.read_at = null);
                return
            }
            const ids = Object.fromEntries(entries.slice(0, 100));
            state.readWaitData = Object.fromEntries(entries.slice(100));
            //
            dispatch("call", {
                method: 'post',
                url: 'dialog/msg/read',
                data: {
                    id: ids
                }
            }).then(({data}) => {
                Object.entries(ids)
                    .filter(([_, dialogId]) => /^\d+$/.test(dialogId))
                    .forEach(([msdId, dialogId]) => {
                        state.dialogMsgs
                            .filter(item => item.dialog_id == dialogId && item.id >= msdId)
                            .forEach(item => {
                                item.read_at = $A.daytz().format("YYYY-MM-DD HH:mm:ss");
                            });
                    });
                dispatch("saveDialog", data)
            }).catch(_ => {
                Object.keys(ids)
                    .forEach(id => {
                        const msg = state.dialogMsgs.find(item => item.id == id);
                        if (msg) msg.read_at = null;
                    });
                state.readWaitData = Object.assign(state.readWaitData, ids);
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
     * 消息流订阅
     * @param state
     * @param dispatch
     * @param streamUrl
     */
    streamMsgSubscribe({state, dispatch}, streamUrl) {
        if (!/^https?:\/\//i.test(streamUrl)) {
            streamUrl = $A.mainUrl(streamUrl.substring(1))
        }
        if (state.dialogSseList.find(item => item.streamUrl == streamUrl)) {
            return
        }
        const sse = new SSEClient(streamUrl)
        sse.subscribe(['append', 'replace', 'done'], (type, e) => {
            switch (type) {
                case 'append':
                case 'replace':
                    const data = $A.jsonParse(e.data);
                    dispatch("streamMsgData", {
                        type,
                        id: e.lastEventId,
                        text: data.content
                    })
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
     * 消息流数据
     * @param state
     * @param data
     */
    streamMsgData({state}, data) {
        $A.syncDispatch("streamMsgData", data)
        emitter.emit('streamMsgData', data);
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
     * 删除翻译
     * @param state
     * @param key
     */
    removeTranslation({state}, key) {
        state.cacheTranslations = state.cacheTranslations.filter(item => item.key != key)
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
        if (typeof window.wsInfo === "undefined") {
            window.wsInfo = {
                msgCount: 0,
                repeatCount: 0,
                lastTime: 0,
                lastData: null,
            }
        }
        //
        let url = $A.mainUrl('ws');
        url = url.replace("https://", "wss://");
        url = url.replace("http://", "ws://");
        url += `?action=web&token=${state.userToken}&language=${languageName}&platform=${$A.Platform}`;
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
            if ($A.inArray(state.routeName, ['preload', '404'])) {
                wgLog && console.log("[WS] Preload", e);
                return;
            }
            //
            if ($A.dayjs().unix() - window.wsInfo.lastTime < 3 && window.wsInfo.lastData === e.data) {
                console.log("[WS] Repeat", e, window.wsInfo.repeatCount);
                window.wsInfo.repeatCount++
                return
            }
            window.wsInfo.msgCount++
            window.wsInfo.lastTime = $A.dayjs().unix()
            window.wsInfo.lastData = e.data
            //
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
                    emitter.emit('userActive', {type: 'line', data: msgDetail.data});
                    break

                case "msgStream":
                    if ($A.isSubElectron) {
                        return
                    }
                    dispatch("streamMsgSubscribe", msgDetail.stream_url);
                    break

                case "operation":
                    // AI 助手页面操作派发（assistant/operation/dispatch）：交给浮窗组件执行后回包
                    emitter.emit('aiOperationRequest', msgDetail.data);
                    break

                default:
                    msgId && dispatch("websocketSend", {type: 'receipt', msgId}).catch(_ => {});
                    emitter.emit('websocketMsg', msgDetail);
                    if ($A.isSubElectron) {
                        return
                    }
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
                                        dispatch("forgetDialogMsg", data)
                                        //
                                        const dialog = state.cacheDialogs.find(({id}) => id == dialog_id);
                                        if (dialog) {
                                            // 更新最后消息
                                            const newData = {
                                                id: dialog_id,
                                                last_msg: data.last_msg,
                                                last_at: data.last_msg ? data.last_msg.created_at : $A.daytz().format("YYYY-MM-DD HH:mm:ss"),
                                                last_force: true,
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
                                        const isAdd = mode === "add";
                                        if (!state.dialogMsgs.find(({id}) => id == data.id)) {
                                            // 新增任务消息数量
                                            dispatch("increaseTaskMsgNum", {id: data.dialog_id});
                                            // 新增回复数量
                                            dispatch("increaseMsgReplyNum", {id: data.reply_id});
                                            //
                                            if (isAdd) {
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
                                                emitter.emit('dialogMsgPush', {silence, data});
                                            }
                                        }
                                        const saveMsg = (data, count) => {
                                            if (count > 5 || state.dialogMsgs.find(({id}) => id == data.id)) {
                                                // 更新消息列表
                                                dispatch("saveDialogMsg", data)
                                                // 更新最后消息
                                                isAdd && dispatch("updateDialogLastMsg", data);
                                                return;
                                            }
                                            setTimeout(() => saveMsg(data, count + 1), 50);
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
                                        dispatch("forgetDialog", data)
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
                                        dispatch("forgetProject", data);
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
                                        dispatch("forgetColumn", data)
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
                                        dispatch("forgetTask", data)
                                        break;
                                    case 'relation':
                                        emitter.emit('taskRelationUpdate', data.id)
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
                                        dispatch("forgetFile", data);
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
                         * 应用菜单角标
                         */
                        case "appBadge":
                            commit("appBadges/set", msgDetail.data || {});
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
                msgId = type + '_' + $A.randomString(3)
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
    /** ************************************ App Store ******************************************/
    /** *****************************************************************************************/

    /**
     * 打开微应用
     * @param state
     * @param data
     *  - id                应用ID（必须）
     *  - name              应用名称（必须）
     *  - url               应用地址（必须）
     *  - type              打开类型（可选，string 或 {mobile,desktop,default}；default 用于补齐 mobile/desktop，缺省为 iframe）
     *  - background        背景颜色（可选）
     *  - capsule           应用胶囊配置（可选）
     *  - transparent       是否透明模式 (true/false)，默认 false
     *  - disable_scope_css 是否禁用样式隔离 (true/false)，默认 false
     *  - auto_dark_theme   是否自动适配深色主题 (true/false)，默认 true
     *  - keep_alive        是否开启微应用保活 (true/false)，默认 true
     *  - immersive         是否开启沉浸式模式 (true/false)，默认 false
     *  - props             传递参数
     *  更多说明详见 https://appstore.dootask.com/development/manual
     */
    async openMicroApp({state}, data) {
        // 参数基础校验：必须是对象且包含 id/name/url
        if (!data || !$A.isJson(data)) {
            return
        }
        if (!data.id || !data.name || !data.url) {
            return
        }

        // 解析微应用实际 ID（支持传入短 ID）
        let microAppId = data.id
        if (!state.microAppsIds.includes(microAppId)) {
            microAppId = state.microAppsIds.find(item => typeof item === 'string' && item.endsWith(microAppId)) || null
        }
        if (!microAppId) {
            $A.modalWarning(`应用「${data.id}」未安装`)
            return
        }

        // 以「匹配到的应用菜单第一个 menu（microAppsMenus 中该应用的第一条记录）」作为基础数据，合并出本次打开的 data
        if (data.skip_base_menu !== true) {
            const baseMenu = state.microAppsMenus.find(item => item?.id === microAppId) || null
            if ($A.isJson(baseMenu)) {
                data = Object.assign({}, baseMenu, data)
            }
        }

        // 处理 url 中的占位符/相对路径（以当前系统 baseUrl 为准）
        const serverLocation = new URL($A.mainUrl())
        const url = data.url
            .replace(/^\/+/, '')
            .replace(/^\:(\d+)/ig, (_, port) => {
                return serverLocation.protocol + '//' + serverLocation.hostname + ':' + port;
            })
            .replace(/\{window[._]location[._](\w+)}/ig, (_, property) => {
                if (property in serverLocation) {
                    return serverLocation[property];
                }
            })
            .replace(/\{system_base_url}/g, serverLocation.origin)

        // 组装打开微应用所需的最终 config（用于 MicroApps 组件渲染/启动）
        const config = {
            id: microAppId,
            key: typeof data.key == 'string' ? data.key : '',
            name: data.name,
            title: data.label || data.title || data.name,
            url: $A.mainUrl(url),
            type: data.type || data.url_type,
            background: data.background || null,
            capsule: $A.isJson(data.capsule) ? data.capsule : {},
            transparent: typeof data.transparent == 'boolean' ? data.transparent : false,
            disable_scope_css: typeof data.disable_scope_css == 'boolean' ? data.disable_scope_css : false,
            auto_dark_theme: typeof data.auto_dark_theme == 'boolean' ? data.auto_dark_theme : true,
            keep_alive: typeof data.keep_alive == 'boolean' ? data.keep_alive : true,
            immersive: typeof data.immersive == 'boolean' ? data.immersive : false,
            badge_clear_on_open: typeof data.badge_clear_on_open == 'boolean' ? data.badge_clear_on_open : false,
            props: $A.isJson(data.props) ? data.props : {},
        }

        // 将运行时变量注入到 url（用户信息/主题/语言等）
        config.url = config.url
            .replace(/\{user_id}/g, state.userId)
            .replace(/\{user_nickname}/g, encodeURIComponent(state.userInfo.nickname))
            .replace(/\{user_email}/g, encodeURIComponent(state.userInfo.email))
            .replace(/\{user_avatar}/g, encodeURIComponent(state.userInfo.userimg))
            .replace(/\{user_token}/g, encodeURIComponent(state.userToken))
            .replace(/\{system_theme}/g, state.themeName)
            .replace(/\{system_lang}/g, languageName);

        // 通知 MicroApps 容器打开应用
        emitter.emit('observeMicroApp:open', config);
    },

    /**
     * 微应用是否已安装
     * @param state
     * @param appName
     * @returns {Promise<unknown>}
     */
    isMicroAppInstalled({state}, appName) {
        return new Promise(resolve => {
            if (!appName) {
                resolve(false)
                return
            }
            resolve(!!state.microAppsIds.includes(appName))
        })
    },

    /**
     * 更新微应用状态（已安装应用、菜单项）
     * @param commit
     * @param dispatch
     */
    async updateMicroAppsStatus({commit, state, dispatch}) {
        const {data: {code, data}} = await axios.get($A.mainUrl('appstore/api/v1/internal/installed'), {
            headers: {
                Token: state.userToken,
                Language: languageName,
            }
        })
        if (code === 200) {
            let apps = Array.isArray(data) ? data : [];
            try {
                const {data: customData} = await dispatch('call', {
                    url: 'system/microapp_menu?type=get',
                });
                if ($A.isArray(customData) && customData.length > 0) {
                    customData.forEach(item => {
                        item.menu_items.forEach(menu => {
                            menu.icon = menu.icon || $A.mainUrl("images/application/appstore-default.svg");
                        });
                    });
                    apps = apps.concat(customData);
                }
            } catch (e) {
                // 忽略自定义菜单加载失败
            }
            commit("microApps/data", apps || [])
            // 角标初始同步：一次拉取当前用户全部应用（插件 + 自定义微应用）的角标快照
            try {
                const {data: badgeMap} = await dispatch('call', {url: 'apps/badge/list'});
                commit("appBadges/hydrateMap", badgeMap || {})
            } catch (e) {
                // 忽略角标初始同步失败
            }
        }
    },

    /** *****************************************************************************************/
    /** *********************************** MCP Server ******************************************/
    /** *****************************************************************************************/

    /**
     * 切换 MCP 服务器状态
     * @param state
     * @param commit
     */
    async toggleMcpServer({state, commit}) {
        if (state.mcpServerStatus.running === 'running') {
            // 停止 MCP 服务器
            commit('mcp/server/status', {running: 'stopped'});
        } else {
            // 启动 MCP 服务器
            commit('mcp/server/status', {running: 'running'});
        }
    },

    /** *****************************************************************************************/
    /** *********************************** AI Suggestions **************************************/
    /** *****************************************************************************************/

    /**
     * 采纳 AI 建议
     */
    applyAiSuggestion({}, params) {
        return this.dispatch('call', {
            url: 'project/task/ai_apply',
            data: params,
        });
    },

    /**
     * 忽略 AI 建议
     */
    dismissAiSuggestion({}, params) {
        return this.dispatch('call', {
            url: 'project/task/ai_dismiss',
            data: params,
        });
    }

}
