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
    }
}
