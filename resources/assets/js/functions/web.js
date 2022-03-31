/**
 * 页面专用
 */
(function (window) {
    const $ = window.$A;
    /**
     * =============================================================================
     * *******************************   web extra   *******************************
     * =============================================================================
     */
    $.extend({
        /**
         * 接口地址
         * @param str
         * @returns {string|string|*}
         */
        apiUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            if (typeof window.systemInfo.apiUrl === "string") {
                str = window.systemInfo.apiUrl + str;
            } else {
                str = window.location.origin + "/api/" + str;
            }
            while (str.indexOf("/../") !== -1) {
                str = str.replace(/\/(((?!\/).)*)\/\.\.\//, "/")
            }
            return str
        },

        /**
         * 服务地址
         * @param str
         * @returns {string}
         */
        originUrl(str) {
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            if (typeof window.systemInfo.origin === "string") {
                str = window.systemInfo.origin + str;
            } else {
                str = window.location.origin + "/" + str;
            }
            while (str.indexOf("/../") !== -1) {
                str = str.replace(/\/(((?!\/).)*)\/\.\.\//, "/")
            }
            return str
        },

        /**
         * 项目配置模板
         * @param project_id
         * @returns {{showMy: boolean, showUndone: boolean, project_id, chat: boolean, showHelp: boolean, showCompleted: boolean, menuType: string, menuInit: boolean, completedTask: boolean}}
         */
        projectParameterTemplate(project_id) {
            return {
                project_id,
                menuInit: false,
                menuType: 'column',
                chat: false,
                showMy: true,
                showHelp: true,
                showUndone: true,
                showCompleted: false,
                completedTask: false,
            }
        },

        /**
         * 格式化websocket的消息
         * @param data
         */
        formatWebsocketMessageDetail(data) {
            if ($A.isJson(data)) {
                for (let key in data) {
                    if (!data.hasOwnProperty(key)) continue;
                    data[key] = $A.formatWebsocketMessageDetail(data[key]);
                }
            } else if ($A.isArray(data)) {
                data.forEach((val, index) => {
                    data[index] = $A.formatWebsocketMessageDetail(val);
                });
            } else if (typeof data === "string") {
                data = data.replace(/\{\{RemoteURL\}\}/g, this.apiUrl('../'))
            }
            return data;
        },

        /**
         * 格式化时间
         * @param date
         * @returns {*|string}
         */
        formatTime(date) {
            let time = $A.Date(date, true),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        /**
         * 小于9补0
         * @param val
         * @returns {number|string}
         */
        formatBit(val) {
            val = +val
            return val > 9 ? val : '0' + val
        },

        /**
         * 秒转时间
         * @param second
         * @returns {string}
         */
        formatSeconds(second) {
            let duration
            let days = Math.floor(second / 86400);
            let hours = Math.floor((second % 86400) / 3600);
            let minutes = Math.floor(((second % 86400) % 3600) / 60);
            let seconds = Math.floor(((second % 86400) % 3600) % 60);
            if (days > 0) {
                if (hours > 0) duration = days + "d," + this.formatBit(hours) + "h";
                else if (minutes > 0) duration = days + "d," + this.formatBit(minutes) + "min";
                else if (seconds > 0) duration = days + "d," + this.formatBit(seconds) + "s";
                else duration = days + "d";
            }
            else if (hours > 0) duration = this.formatBit(hours) + ":" + this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (minutes > 0) duration = this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (seconds > 0) duration = this.formatBit(seconds) + "s";
            return duration;
        },

        /**
         * 倒计时格式
         * @param date
         * @param nowTime
         * @returns {string|*}
         */
        countDownFormat(date, nowTime) {
            let time = Math.round(this.Date(date).getTime() / 1000) - nowTime;
            if (time < 86400 * 7 && time > 0 ) {
                return this.formatSeconds(time);
            } else if (time < 0) {
                return '-' + this.formatSeconds(time * -1);
            } else if (time == 0) {
                return 0 + 's';
            }
            return this.formatTime(date)
        },

        /**
         * 获取一些指定时间
         * @param str
         * @param retInt
         * @returns {*|string}
         */
        getData(str, retInt = false) {
            let now = new Date();                   //当前日期
            let nowDayOfWeek = now.getDay();        //今天本周的第几天
            let nowDay = now.getDate();             //当前日
            let nowMonth = now.getMonth();          //当前月
            let nowYear = now.getYear();            //当前年
            nowYear += (nowYear < 2000) ? 1900 : 0;
            let lastMonthDate = new Date();         //上月日期
            lastMonthDate.setDate(1);
            lastMonthDate.setMonth(lastMonthDate.getMonth()-1);
            let lastMonth = lastMonthDate.getMonth();
            let getQuarterStartMonth = () => {
                let quarterStartMonth = 0;
                if(nowMonth < 3) {
                    quarterStartMonth = 0;
                }
                if (2 < nowMonth && nowMonth < 6) {
                    quarterStartMonth = 3;
                }
                if (5 < nowMonth && nowMonth < 9) {
                    quarterStartMonth = 6;
                }
                if (nowMonth > 8) {
                    quarterStartMonth = 9;
                }
                return quarterStartMonth;
            };
            let getMonthDays = (myMonth) => {
                let monthStartDate = new Date(nowYear, myMonth, 1);
                let monthEndDate = new Date(nowYear, myMonth + 1, 1);
                return (monthEndDate - monthStartDate)/(1000 * 60 * 60 * 24);
            };
            //
            let time = now.getTime();
            switch (str) {
                case '今天':
                    time = now;
                    break;
                case '昨天':
                    time = now - 86400000;
                    break;
                case '前天':
                    time = now - 86400000 * 2;
                    break;
                case '本周':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek);
                    break;
                case '本周结束':
                    time = new Date(nowYear, nowMonth, nowDay + (6 - nowDayOfWeek));
                    break;
                case '上周':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 7);
                    break;
                case '上周结束':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 1);
                    break;
                case '本周2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek + 1);
                    break;
                case '本周结束2':
                    time = new Date(nowYear, nowMonth, nowDay + (6 - nowDayOfWeek) + 1);
                    break;
                case '上周2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 7 + 1);
                    break;
                case '上周结束2':
                    time = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek - 1 + 1);
                    break;
                case '本月':
                    time = new Date(nowYear, nowMonth, 1);
                    break;
                case '本月结束':
                    time = new Date(nowYear, nowMonth, getMonthDays(nowMonth));
                    break;
                case '上个月':
                    time = new Date(nowYear, lastMonth, 1);
                    break;
                case '上个月结束':
                    time = new Date(nowYear, lastMonth, getMonthDays(lastMonth));
                    break;
                case '本季度':
                    time = new Date(nowYear, getQuarterStartMonth(), 1);
                    break;
                case '本季度结束':
                    let quarterEndMonth = getQuarterStartMonth() + 2;
                    time = new Date(nowYear, quarterEndMonth, getMonthDays(quarterEndMonth));
                    break;
            }
            if (retInt === true) {
                return time;
            }
            return $A.formatDate("Y-m-d", parseInt(time / 1000))
        },

        /**
         * 获取日期选择器的 shortcuts 模板参数
         * @returns {(*)[]|[{text, value(): [Date,*]},{text, value(): [Date,*]},{text, value(): [*,*]},{text, value(): [*,*]},{text, value(): [Date,*]},null,null]|(Date|*)[]}
         */
        timeOptionShortcuts() {
            const lastSecond = (e) => {
                return $A.Date($A.formatDate("Y-m-d 23:59:29", Math.round(e / 1000)))
            };
            return [{
                text: $A.L('今天'),
                value() {
                    return [new Date(), lastSecond(new Date().getTime())];
                }
            }, {
                text: $A.L('明天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 1);
                    return [new Date(), lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('本周'),
                value() {
                    return [$A.getData('今天', true), lastSecond($A.getData('本周结束2', true))];
                }
            }, {
                text: $A.L('本月'),
                value() {
                    return [$A.getData('今天', true), lastSecond($A.getData('本月结束', true))];
                }
            }, {
                text: $A.L('3天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 2);
                    return [new Date(), lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('5天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 4);
                    return [new Date(), lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('7天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 6);
                    return [new Date(), lastSecond(e.getTime())];
                }
            }];
        },

        /**
         * 对话标签
         * @param dialog
         * @returns {*[]}
         */
        dialogTags(dialog) {
            let tags = [];
            if (dialog.type == 'group') {
                if (['project', 'task'].includes(dialog.group_type) && $A.isJson(dialog.group_info)) {
                    if (dialog.group_type == 'task' && dialog.group_info.complete_at) {
                        tags.push({
                            color: 'success',
                            text: '已完成'
                        })
                    }
                    if (dialog.group_info.deleted_at) {
                        tags.push({
                            color: 'red',
                            text: '已删除'
                        })
                    } else if (dialog.group_info.archived_at) {
                        tags.push({
                            color: 'default',
                            text: '已归档'
                        })
                    }
                }
            }
            return tags;
        },

        /**
         * 对话完成
         * @param dialog
         * @returns {*[]}
         */
        dialogCompleted(dialog) {
            return this.dialogTags(dialog).find(({color}) => color == 'success');
        },

        /**
         * 返回对话未读数量
         * @param dialog
         * @returns {*|number}
         */
        getDialogUnread(dialog) {
            return dialog ? (dialog.unread || dialog.mark_unread || 0) : 0
        },
    });

    /**
     * =============================================================================
     * *****************************   iviewui assist   ****************************
     * =============================================================================
     */
    $.extend({
        // 加载器
        spinnerShow() {
            $A.spinnerLoadNum++
            if ($A.spinnerLoadNum > 0) {
                const spinner = document.getElementById("common-spinner");
                if (spinner) {
                    spinner.style.display = "block"
                }
            }
        },

        spinnerHide() {
            $A.spinnerLoadNum--
            if ($A.spinnerLoadNum <= 0) {
                const spinner = document.getElementById("common-spinner");
                if (spinner) {
                    spinner.style.display = "none"
                }
            }
        },
        spinnerLoadNum: 0,

        // 弹窗
        modalConfig(config) {
            if (typeof config === "undefined") {
                config = {content: "Undefined"};
            } else if (typeof config === "string") {
                config = {content: config};
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
            const onOk = () => {
                if (typeof config.onOk === "function") {
                    if (config.onOk(config.value, () => {
                        $A.Modal.remove();
                    }) === true) {
                        $A.Modal.remove();
                    }
                } else {
                    $A.Modal.remove();
                }
            };
            const onCancel = () => {
                if (typeof config.onCancel === "function") {
                    config.onCancel();
                }
            };
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
                                },
                                'on-enter': (e) => {
                                    $A(e.target).parents(".ivu-modal-body").find(".ivu-btn-primary").click();
                                }
                            }
                        })
                    ])
                },
                onOk,
                onCancel,
                loading: true,
                okText: $A.L(config.okText || '确定'),
                cancelText: $A.L(config.cancelText || '取消'),
            });
            setTimeout(() => {
                document.getElementById(inputId) && document.getElementById(inputId).focus();
            });
        },

        modalConfirm(config, millisecond = 0) {
            if (config === false) {
                return;
            }
            if (millisecond > 0) {
                setTimeout(() => { $A.modalConfirm(config) }, millisecond);
                return;
            }
            $A.Modal.confirm($A.modalConfig(config));
        },

        modalSuccess(config, millisecond = 0) {
            if (config === false) {
                return;
            }
            if (millisecond > 0) {
                setTimeout(() => { $A.modalSuccess(config) }, millisecond);
                return;
            }
            $A.Modal.success($A.modalConfig(config));
        },

        modalInfo(config, millisecond = 0) {
            if (config === false) {
                return;
            }
            if (millisecond > 0) {
                setTimeout(() => { $A.modalInfo(config) }, millisecond);
                return;
            }
            $A.Modal.info($A.modalConfig(config));
        },

        modalWarning(config, millisecond = 0) {
            if (config === false) {
                return;
            }
            if (millisecond > 0) {
                setTimeout(() => { $A.modalWarning(config) }, millisecond);
                return;
            }
            $A.Modal.warning($A.modalConfig(config));
        },

        modalError(config, millisecond = 0) {
            if (config === false) {
                return;
            }
            if (millisecond > 0) {
                setTimeout(() => { $A.modalError(config) }, millisecond);
                return;
            }
            $A.Modal.error($A.modalConfig(config));
        },

        modalAlert(msg) {
            if (msg === false) {
                return;
            }
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
            if (typeof config === "undefined") {
                config = {desc: "Undefined"};
            } else if (typeof config === "string") {
                config = {desc: config};
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

    /**
     * =============================================================================
     * **********************************   dark   *********************************
     * =============================================================================
     */

    $.extend({
        dark: {
            utils: {
                filter: '-webkit-filter: url(#dark-mode-filter) !important; filter: url(#dark-mode-filter) !important;',
                reverseFilter: '-webkit-filter: url(#dark-mode-reverse-filter) !important; filter: url(#dark-mode-reverse-filter) !important;',
                noneFilter: '-webkit-filter: none !important; filter: none !important;',

                addExtraStyle() {
                    try {
                        return '';
                    } catch (e) {
                        return '';
                    }
                },

                addStyle(id, tag, css) {
                    tag = tag || 'style';
                    let doc = document, styleDom = doc.getElementById(id);
                    if (styleDom) return;
                    let style = doc.createElement(tag);
                    style.rel = 'stylesheet';
                    style.id = id;
                    tag === 'style' ? style.innerHTML = css : style.href = css;
                    document.head.appendChild(style);
                },

                getClassList(node) {
                    return node.classList || [];
                },

                addClass(node, name) {
                    this.getClassList(node).add(name);
                    return this;
                },

                removeClass(node, name) {
                    this.getClassList(node).remove(name);
                    return this;
                },

                hasClass(node, name) {
                    return this.getClassList(node).contains(name);
                },

                hasElementById(eleId) {
                    return document.getElementById(eleId);
                },

                removeElementById(eleId) {
                    let ele = document.getElementById(eleId);
                    ele && ele.parentNode.removeChild(ele);
                },
            },

            createDarkFilter() {
                if (this.utils.hasElementById('dark-mode-svg')) return;
                let svgDom = '<svg id="dark-mode-svg" style="height: 0; width: 0;"><filter id="dark-mode-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.283 -0.567 -0.567 0.000 0.925 -0.567 0.283 -0.567 0.000 0.925 -0.567 -0.567 0.283 0.000 0.925 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter><filter id="dark-mode-reverse-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.333 -0.667 -0.667 0.000 1.000 -0.667 0.333 -0.667 0.000 1.000 -0.667 -0.667 0.333 0.000 1.000 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter></svg>';
                let div = document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
                div.innerHTML = svgDom;
                let frag = document.createDocumentFragment();
                while (div.firstChild)
                    frag.appendChild(div.firstChild);
                document.head.appendChild(frag);
            },

            createDarkStyle() {
                this.utils.addStyle('dark-mode-style', 'style', `
                @media screen {
                    html {
                        ${this.utils.filter}
                    }

                    /* Default Reverse rule */
                    img,
                    video,
                    iframe,
                    canvas,
                    :not(object):not(body) > embed,
                    object,
                    svg image,
                    [style*="background:url"],
                    [style*="background-image:url"],
                    [style*="background: url"],
                    [style*="background-image: url"],
                    [background],
                    twitterwidget,
                    .sr-reader,
                    .no-dark-mode,
                    .no-dark-mode-before:before,
                    .sr-backdrop {
                        ${this.utils.reverseFilter}
                    }

                    [style*="background:url"] *,
                    [style*="background-image:url"] *,
                    [style*="background: url"] *,
                    [style*="background-image: url"] *,
                    input,
                    [background] *,
                    twitterwidget .NaturalImage-image {
                        ${this.utils.noneFilter}
                    }

                    /* Text contrast */
                    html {
                        text-shadow: 0 0 0 !important;
                    }

                    /* Full screen */
                    .no-filter,
                    :-webkit-full-screen,
                    :-webkit-full-screen *,
                    :-moz-full-screen,
                    :-moz-full-screen *,
                    :fullscreen,
                    :fullscreen * {
                        ${this.utils.noneFilter}
                    }

                    /* Page background */
                    html {
                        background: #fff !important;
                    }
                    ${this.utils.addExtraStyle()}
                }

                @media print {
                    .no-print {
                        display: none !important;
                    }
                }`);
            },

            enableDarkMode() {
                if (!$A.isChrome()) {
                    return;
                }
                if (this.isDarkEnabled()) {
                    return
                }
                this.createDarkFilter();
                this.createDarkStyle();
                this.utils.addClass(document.body, "dark-mode-reverse")
            },

            disableDarkMode() {
                if (!this.isDarkEnabled()) {
                    return
                }
                this.utils.removeElementById('dark-mode-svg');
                this.utils.removeElementById('dark-mode-style');
                this.utils.removeClass(document.body, "dark-mode-reverse")
            },

            autoDarkMode() {
                let darkScheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                if (darkScheme) {
                    this.enableDarkMode()
                } else {
                    this.disableDarkMode()
                }
            },

            isDarkEnabled() {
                return this.utils.hasClass(document.body, "dark-mode-reverse")
            },
        }
    });

    window.$A = $;
})(window);
