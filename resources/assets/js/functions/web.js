/**
 * 页面专用
 */
(function (window) {

    let apiUrl = window.location.origin + '/api/';
    let $ = window.$A;

    $.extend({

        fillUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return window.location.origin + '/' + str;
        },

        apiUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return apiUrl + str;
        },

        /**
         * @param params {url,data,method,timeout,header,spinner,websocket,timeout, before,complete,success,error,after}
         * @returns {boolean}
         */
        apiAjax(params) {
            if (!$A.isJson(params)) return false;
            if (typeof params.success === 'undefined') params.success = () => { };
            if (typeof params.header === 'undefined') params.header = {};
            params.url = this.apiUrl(params.url);
            params.data = this.date2string(params.data);
            params.header['Content-Type'] = 'application/json';
            params.header['language'] = $A.getLanguage();
            params.header['token'] = $A.store.state.userToken;
            //
            if (params.spinner === true) {
                let beforeCall = params.before;
                params.before = () => {
                    $A.aAjaxLoadNum++;
                    $A(".common-spinner").show();
                    typeof beforeCall == "function" && beforeCall();
                };
                //
                let completeCall = params.complete;
                params.complete = () => {
                    $A.aAjaxLoadNum--;
                    if ($A.aAjaxLoadNum <= 0) {
                        $A(".common-spinner").hide();
                    }
                    typeof completeCall == "function" && completeCall();
                };
            }
            //
            let callback = params.success;
            params.success = (data, status, xhr) => {
                if (typeof data === 'object') {
                    if (data.ret === -1 && params.checkRole !== false) {
                        //身份丢失
                        $A.modalError({
                            content: data.msg,
                            onOk: () => {
                                $A.logout();
                            }
                        });
                        return;
                    }
                    if (data.ret === -2 && params.role !== false) {
                        //没有权限
                        $A.modalError({
                            content: data.msg || "你没有相关的权限查看或编辑！"
                        });
                    }
                }
                if (typeof callback === "function") {
                    callback(data, status, xhr);
                }
            };
            //
            if (params.websocket === true || params.ws === true) {
                const apiWebsocket = $A.randomString(16);
                const apiTimeout = setTimeout(() => {
                    const WListener = $A.aAjaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                    $A.aAjaxWsListener = $A.aAjaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                    if (WListener) {
                        WListener.complete();
                        WListener.error("timeout");
                        WListener.after();
                    }
                }, params.timeout || 30000);
                $A.aAjaxWsListener.push({
                    apiWebsocket: apiWebsocket,
                    complete: typeof params.complete === "function" ? params.complete : () => { },
                    after: typeof params.after === "function" ? params.after : () => { },
                    success: typeof params.success === "function" ? params.success : () => { },
                    error: typeof params.error === "function" ? params.error : () => { },
                });
                //
                params.complete = () => { };
                params.success = () => { };
                params.error = () => { };
                params.after = () => { };
                params.header['Api-Websocket'] = apiWebsocket;
                //
                if ($A.aAjaxWsReady === false) {
                    $A.aAjaxWsReady = true;
                    $A.store.commit("wsMsgListener", {
                        name: "apiWebsocket",
                        callback: (msg) => {
                            switch (msg.type) {
                                case 'apiWebsocket':
                                    clearTimeout(apiTimeout);
                                    const apiWebsocket = msg.apiWebsocket;
                                    const apiSuccess = msg.apiSuccess;
                                    const apiResult = msg.data;
                                    const WListener = $A.aAjaxWsListener.find((item) => item.apiWebsocket == apiWebsocket);
                                    $A.aAjaxWsListener = $A.aAjaxWsListener.filter((item) => item.apiWebsocket != apiWebsocket);
                                    if (WListener) {
                                        WListener.complete();
                                        if (apiSuccess) {
                                            WListener.success(apiResult);
                                        } else {
                                            WListener.error(apiResult);
                                        }
                                        WListener.after();
                                    }
                                    break;
                            }
                        }
                    });
                }
            }
            //
            $A.ajaxc(params);
            return true;
        },
        aAjaxLoadNum: 0,
        aAjaxWsReady: false,
        aAjaxWsListener: [],

        /**
         * 登出（打开登录页面）
         */
        logout() {
            const from = window.location.pathname == '/' ? '' : encodeURIComponent(window.location.href);
            $A.store.commit('setUserInfo', {});
            $A.goForward({path: '/login', query: from ? {from: from} : {}}, true);
        },
    });

    /**
     * =============================================================================
     * *****************************   iviewui assist   ****************************
     * =============================================================================
     */
    $.extend({
        // 弹窗
        modalConfig(config) {
            if (typeof config === "string") {
                config = {
                    content: config
                };
            }
            config.title = $A.L(config.title || (typeof config.render === 'undefined' ? '温馨提示' : ''));
            config.content = $A.L(config.content || '');
            config.okText = $A.L(config.okText || '确定');
            config.cancelText = $A.L(config.cancelText || '取消');
            return config;
        },

        modalInput(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalInput(config) }, millisecond);
                return;
            }
            if (typeof config === "string") config = {title:config};
            let inputId = "modalInput_" + $A.randomString(6);
            $A.Modal.confirm({
                render: (h) => {
                    return h('div', [
                        h('div', {
                            style: {
                                fontSize: '16px',
                                fontWeight: '500',
                                marginBottom: '20px',
                            }
                        }, $A.L(config.title)),
                        h('Input', {
                            props: {
                                value: config.value,
                                placeholder: $A.L(config.placeholder),
                                elementId: inputId,
                            },
                            on: {
                                input: (val) => {
                                    config.value = val;
                                }
                            }
                        })
                    ])
                },
                loading: true,
                onOk: () => {
                    if (typeof config.onOk === "function") {
                        if (config.onOk(config.value, () => {
                            $A.Modal.remove();
                        }) === true) {
                            $A.Modal.remove();
                        }
                    } else {
                        $A.Modal.remove();
                    }
                },
            });
            setTimeout(() => {
                document.getElementById(inputId) && document.getElementById(inputId).focus();
            });
        },

        modalConfirm(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalConfirm(config) }, millisecond);
                return;
            }
            $A.Modal.confirm($A.modalConfig(config));
        },

        modalSuccess(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalSuccess(config) }, millisecond);
                return;
            }
            $A.Modal.success($A.modalConfig(config));
        },

        modalInfo(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalInfo(config) }, millisecond);
                return;
            }
            $A.Modal.info($A.modalConfig(config));
        },

        modalWarning(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalWarning(config) }, millisecond);
                return;
            }
            $A.Modal.warning($A.modalConfig(config));
        },

        modalError(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalError(config) }, millisecond);
                return;
            }
            $A.Modal.error($A.modalConfig(config));
        },

        modalInfoShow(title, data, addConfig) {
            let content = '';
            for (let i in data) {
                let item = data[i];
                content += `<div class="modal-info-show">`;
                content += `    <div class="column">${item.column}：</div>`;
                content += `    <div class="value">${item.value || item.value == '0' ? item.value : '-'}</div>`;
                content += `</div>`;
            }
            let config = {
                title: title,
                content: content,
                okText: $A.L('关闭'),
                closable: true
            };
            if (typeof addConfig == 'object' && addConfig) {
                config = Object.assign(config, addConfig);
            }
            this.Modal.info(config);
        },

        modalAlert(msg) {
            alert($A.L(msg));
        },

        //提示
        messageSuccess(msg) {
            $A.Message.success($A.L(msg));
        },

        messageWarning(msg) {
            $A.Message.warning($A.L(msg));
        },

        messageError(msg) {
            $A.Message.error($A.L(msg));
        },

        //通知
        noticeConfig(config) {
            if (typeof config === "string") {
                config = {
                    desc: config
                };
            }
            config.title = $A.L(config.title || (typeof config.render === 'undefined' ? '温馨提示' : ''));
            config.desc = $A.L(config.desc || '');
            return config;
        },

        noticeSuccess(config) {
            $A.Notice.success($A.noticeConfig(config));
        },

        noticeWarning(config) {
            $A.Notice.warning($A.noticeConfig(config));
        },

        noticeError(config) {
            if (typeof config === "string") {
                config = {
                    desc: config,
                    duration: 6
                };
            }
            $A.Notice.error($A.noticeConfig(config));
        },
    });

    window.$A = $;
})(window);
