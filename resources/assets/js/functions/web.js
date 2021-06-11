/**
 * 页面专用
 */
(function (window) {
    const $ = window.$A;
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
