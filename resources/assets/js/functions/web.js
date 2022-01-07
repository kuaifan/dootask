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
            if (window.systemInformation && typeof window.systemInformation.apiUrl === "string") {
                str = window.systemInformation.apiUrl + str;
            } else {
                str = window.location.origin + "/api/" + str;
            }
            while (str.indexOf("/../") !== -1) {
                str = str.replace(/\/(((?!\/).)*)\/\.\.\//, "/")
            }
            return str
        },

        /**
         * 服务器地址
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
            if (window.systemInformation && typeof window.systemInformation.origin === "string") {
                str = window.systemInformation.origin + str;
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
         * @returns {{showMy: boolean, showUndone: boolean, project_id, chat: boolean, showHelp: boolean, showCompleted: boolean, cardInit: boolean, card: boolean, completedTask: boolean}}
         */
        projectParameterTemplate(project_id) {
            return {
                project_id,
                card: true,
                cardInit: false,
                chat: false,
                showMy: true,
                showHelp: true,
                showUndone: true,
                showCompleted: false,
                completedTask: false,
            }
        },

        /**
         * 格式化时间
         * @param date
         * @returns {*|string}
         */
        formatTime(date) {
            let time = Math.round($A.Date(date).getTime() / 1000),
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
            } else if (time <= 0) {
                return '-' + this.formatSeconds(time * -1);
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
                onCancel: () => {
                    if (typeof config.onCancel === "function") {
                        config.onCancel();
                    }
                },
                loading: true,
                okText: $A.L(config.okText || '确定'),
                cancelText: $A.L(config.cancelText || '取消'),
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

    window.$A = $;
})(window);
