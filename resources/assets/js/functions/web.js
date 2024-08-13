import {MarkdownPreview} from "../store/markdown";

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
            if (str == "privacy") {
                const apiHome = $A.getDomain(window.systemInfo.apiUrl)
                if (apiHome == "" || apiHome == "public") {
                    return "https://www.dootask.com/privacy.html"
                }
                str = "../privacy.html"
            }
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
         * 主页地址
         * @param str
         * @returns {string}
         */
        mainUrl(str = null) {
            if (!str) {
                str = ""
            }
            if (str.substring(0, 2) === "//" ||
                str.substring(0, 7) === "http://" ||
                str.substring(0, 8) === "https://" ||
                str.substring(0, 6) === "ftp://" ||
                str.substring(0, 1) === "/") {
                return str;
            }
            return $A.apiUrl(`../${str}`)
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
         * 预览文件地址
         * @param name
         * @param key
         * @returns {*}
         */
        onlinePreviewUrl(name, key) {
            return $A.mainUrl(`online/preview/${name}?key=${key}&version=${window.systemInfo.version}&__=${new Date().getTime()}`)
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
         * 格式化时间
         * @param date
         * @returns {*|string}
         */
        formatTime(date) {
            let now = $A.Time(),
                time = $A.Date(date, true);
            if ($A.formatDate('Ymd', now) === $A.formatDate('Ymd', time)) {
                return $A.formatDate('H:i', time)
            }
            if ($A.formatDate('Ymd', now - 86400) === $A.formatDate('Ymd', time)) {
                return `${$A.L('昨天')} ${$A.formatDate('H:i', time)}`
            }
            if ($A.formatDate('Y', now) === $A.formatDate('Y', time)) {
                return $A.formatDate('m-d', time)
            }
            return $A.formatDate('Y-m-d', time) || '';
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
         * 时间工具
         */
        dateRangeUtil: {
            /***
             * 获得当前时间
             */
            getCurrentDate() {
                return new Date();
            },

            /***
             * 获得本周起止时间
             */
            getCurrentWeek() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //返回date是一周中的某一天
                let week = currentDate.getDay();

                //一天的毫秒数
                let millisecond = 1000 * 60 * 60 * 24;
                //减去的天数
                let minusDay = week != 0 ? week - 1 : 6;
                //alert(minusDay);
                //本周 周一
                let monday = new Date(currentDate.getTime() - (minusDay * millisecond));
                //本周 周日
                let sunday = new Date(monday.getTime() + (6 * millisecond));
                //添加本周时间
                startStop.push(monday); //本周起始时间
                //添加本周最后一天时间
                startStop.push(sunday); //本周终止时间
                //返回
                return startStop;
            },

            /***
             * 获得本月的起止时间
             */
            getCurrentMonth() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前月份0-11
                let currentMonth = currentDate.getMonth();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();
                //求出本月第一天
                let firstDay = new Date(currentYear, currentMonth, 1);


                //当为12月的时候年份需要加1
                //月份需要更新为0 也就是下一年的第一个月
                if (currentMonth == 11) {
                    currentYear++;
                    currentMonth = 0; //就为
                } else {
                    //否则只是月份增加,以便求的下一月的第一天
                    currentMonth++;
                }


                //一天的毫秒数
                let millisecond = 1000 * 60 * 60 * 24;
                //下月的第一天
                let nextMonthDayOne = new Date(currentYear, currentMonth, 1);
                //求出上月的最后一天
                let lastDay = new Date(nextMonthDayOne.getTime() - millisecond);

                //添加至数组中返回
                startStop.push(firstDay);
                startStop.push(lastDay);
                //返回
                return startStop;
            },

            /**
             * 得到本季度开始的月份
             * @param month 需要计算的月份
             ***/
            getQuarterSeasonStartMonth(month) {
                let spring = 0; //春
                let summer = 3; //夏
                let fall = 6;   //秋
                let winter = 9; //冬
                //月份从0-11
                if (month < 3) {
                    return spring;
                }

                if (month < 6) {
                    return summer;
                }

                if (month < 9) {
                    return fall;
                }

                return winter;
            },

            /**
             * 获得该月的天数
             * @param year 年份
             * @param month 月份
             * */
            getMonthDays(year, month) {
                //本月第一天 1-31
                let relativeDate = new Date(year, month, 1);
                //获得当前月份0-11
                let relativeMonth = relativeDate.getMonth();
                //获得当前年份4位年
                let relativeYear = relativeDate.getFullYear();

                //当为12月的时候年份需要加1
                //月份需要更新为0 也就是下一年的第一个月
                if (relativeMonth == 11) {
                    relativeYear++;
                    relativeMonth = 0;
                } else {
                    //否则只是月份增加,以便求的下一月的第一天
                    relativeMonth++;
                }
                //一天的毫秒数
                let millisecond = 1000 * 60 * 60 * 24;
                //下月的第一天
                let nextMonthDayOne = new Date(relativeYear, relativeMonth, 1);
                //返回得到上月的最后一天,也就是本月总天数
                return new Date(nextMonthDayOne.getTime() - millisecond).getDate();
            },

            /**
             * 获得本季度的起止日期
             */
            getCurrentSeason() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前月份0-11
                let currentMonth = currentDate.getMonth();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();
                //获得本季度开始月份
                let quarterSeasonStartMonth = this.getQuarterSeasonStartMonth(currentMonth);
                //获得本季度结束月份
                let quarterSeasonEndMonth = quarterSeasonStartMonth + 2;

                //获得本季度开始的日期
                let quarterSeasonStartDate = new Date(currentYear, quarterSeasonStartMonth, 1);
                //获得本季度结束的日期
                let quarterSeasonEndDate = new Date(currentYear, quarterSeasonEndMonth, this.getMonthDays(currentYear, quarterSeasonEndMonth));
                //加入数组返回
                startStop.push(quarterSeasonStartDate);
                startStop.push(quarterSeasonEndDate);
                //返回
                return startStop;
            },

            /***
             * 得到本年的起止日期
             *
             */
            getCurrentYear() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();

                //本年第一天
                let currentYearFirstDate = new Date(currentYear, 0, 1);
                //本年最后一天
                let currentYearLastDate = new Date(currentYear, 11, 31);
                //添加至数组
                startStop.push(currentYearFirstDate);
                startStop.push(currentYearLastDate);
                //返回
                return startStop;
            },

            /**
             * 返回上一个月的第一天Date类型
             * @param year 年
             * @param month 月
             **/
            getPriorMonthFirstDay(year, month) {
                //年份为0代表,是本年的第一月,所以不能减
                if (month == 0) {
                    month = 11; //月份为上年的最后月份
                    year--; //年份减1
                    return new Date(year, month, 1);
                }
                //否则,只减去月份
                month--;
                return new Date(year, month, 1);
            },

            /**
             * 获得上一月的起止日期
             * ***/
            getPreviousMonth() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前月份0-11
                let currentMonth = currentDate.getMonth();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();
                //获得上一个月的第一天
                let priorMonthFirstDay = this.getPriorMonthFirstDay(currentYear, currentMonth);
                //获得上一月的最后一天
                let priorMonthLastDay = new Date(priorMonthFirstDay.getFullYear(), priorMonthFirstDay.getMonth(), this.getMonthDays(priorMonthFirstDay.getFullYear(), priorMonthFirstDay.getMonth()));
                //添加至数组
                startStop.push(priorMonthFirstDay);
                startStop.push(priorMonthLastDay);
                //返回
                return startStop;
            },


            /**
             * 获得上一周的起止日期
             * **/
            getPreviousWeek() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //返回date是一周中的某一天
                let week = currentDate.getDay();
                //一天的毫秒数
                let millisecond = 1000 * 60 * 60 * 24;
                //减去的天数
                let minusDay = week != 0 ? week - 1 : 6;
                //获得当前周的第一天
                let currentWeekDayOne = new Date(currentDate.getTime() - (millisecond * minusDay));
                //上周最后一天即本周开始的前一天
                let priorWeekLastDay = new Date(currentWeekDayOne.getTime() - millisecond);
                //上周的第一天
                let priorWeekFirstDay = new Date(priorWeekLastDay.getTime() - (millisecond * 6));

                //添加至数组
                startStop.push(priorWeekFirstDay);
                startStop.push(priorWeekLastDay);

                return startStop;
            },

            /**
             * 得到上季度的起始日期
             * year 这个年应该是运算后得到的当前本季度的年份
             * month 这个应该是运算后得到的当前季度的开始月份
             * */
            getPriorSeasonFirstDay(year, month) {
                let spring = 0; //春
                let summer = 3; //夏
                let fall = 6;   //秋
                let winter = 9; //冬
                //月份从0-11
                switch (month) {//季度的其实月份
                    case spring:
                        //如果是第一季度则应该到去年的冬季
                        year--;
                        month = winter;
                        break;
                    case summer:
                        month = spring;
                        break;
                    case fall:
                        month = summer;
                        break;
                    case winter:
                        month = fall;
                        break;

                }

                return new Date(year, month, 1);
            },

            /**
             * 得到上季度的起止日期
             * **/
            getPreviousSeason() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前月份0-11
                let currentMonth = currentDate.getMonth();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();
                //上季度的第一天
                let priorSeasonFirstDay = this.getPriorSeasonFirstDay(currentYear, currentMonth);
                //上季度的最后一天
                let priorSeasonLastDay = new Date(priorSeasonFirstDay.getFullYear(), priorSeasonFirstDay.getMonth() + 2, this.getMonthDays(priorSeasonFirstDay.getFullYear(), priorSeasonFirstDay.getMonth() + 2));
                //添加至数组
                startStop.push(priorSeasonFirstDay);
                startStop.push(priorSeasonLastDay);
                return startStop;
            },

            /**
             * 得到去年的起止日期
             * **/
            getPreviousYear() {
                //起止日期数组
                let startStop = [];
                //获取当前时间
                let currentDate = this.getCurrentDate();
                //获得当前年份4位年
                let currentYear = currentDate.getFullYear();
                currentYear--;
                let priorYearFirstDay = new Date(currentYear, 0, 1);
                let priorYearLastDay = new Date(currentYear, 11, 1);
                //添加至数组
                startStop.push(priorYearFirstDay);
                startStop.push(priorYearLastDay);
                return startStop;
            }
        },

        /**
         * 获取一些指定时间
         * @param str
         * @param retDate
         * @returns {*|string}
         */
        getSpecifyDate(str, retDate = false) {
            let time = new Date().getTime();
            switch (str) {
                case '昨天':
                    time -= 86400 * 1000;
                    break;
                case '前天':
                    time -= 86400 * 2000;
                    break;
                case '本周':
                    time = $A.dateRangeUtil.getCurrentWeek()[0].getTime();
                    break;
                case '本周结束':
                    time = $A.dateRangeUtil.getCurrentWeek()[1].getTime();
                    break;
                case '上周':
                    time = $A.dateRangeUtil.getPreviousWeek()[0].getTime();
                    break;
                case '上周结束':
                    time = $A.dateRangeUtil.getPreviousWeek()[1].getTime();
                    break;
                case '本月':
                    time = $A.dateRangeUtil.getCurrentMonth()[0].getTime();
                    break;
                case '本月结束':
                    time = $A.dateRangeUtil.getCurrentMonth()[1].getTime();
                    break;
                case '上个月':
                    time = $A.dateRangeUtil.getPreviousMonth()[0].getTime();
                    break;
                case '上个月结束':
                    time = $A.dateRangeUtil.getPreviousMonth()[1].getTime();
                    break;
                case '本季度':
                    time = $A.dateRangeUtil.getCurrentSeason()[0].getTime();
                    break;
                case '本季度结束':
                    time = $A.dateRangeUtil.getCurrentSeason()[1].getTime();
                    break;
            }
            time = $A.formatDate("Y-m-d", Math.floor(time / 1000))
            if (retDate === true) {
                return new Date(time);
            }
            return time
        },

        /**
         * 获取日期选择器的 shortcuts 模板参数
         * @returns {(*)[]|[{text, value(): [Date,*]},{text, value(): [Date,*]},{text, value(): [*,*]},{text, value(): [*,*]},{text, value(): [Date,*]},null,null]|(Date|*)[]}
         */
        timeOptionShortcuts() {
            const startSecond = $A.Date($A.formatDate("Y-m-d 00:00:00", Math.round(new Date().getTime() / 1000)));
            const lastSecond = (e) => {
                return $A.Date($A.formatDate("Y-m-d 00:00:00", Math.round(e / 1000)))
            };
            return [{
                text: $A.L('今天'),
                value() {
                    return [startSecond, lastSecond(new Date().getTime())];
                }
            }, {
                text: $A.L('明天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 1);
                    return [startSecond, lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('本周'),
                value() {
                    return [startSecond, lastSecond($A.getSpecifyDate('本周结束', true).getTime())];
                }
            }, {
                text: $A.L('本月'),
                value() {
                    return [startSecond, lastSecond($A.getSpecifyDate('本月结束', true).getTime())];
                }
            }, {
                text: $A.L('3天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 2);
                    return [startSecond, lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('5天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 4);
                    return [startSecond, lastSecond(e.getTime())];
                }
            }, {
                text: $A.L('7天'),
                value() {
                    let e = new Date();
                    e.setDate(e.getDate() + 6);
                    return [startSecond, lastSecond(e.getTime())];
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
         * 返回对话未读数量（不含免打扰，但如果免打扰中有@则返回@数量）
         * @param dialog
         * @returns {*|number}
         */
        getDialogNum(dialog) {
            if (!dialog) {
                return 0
            }
            const unread = !dialog.silence ? dialog.unread : 0
            return unread || dialog.mention || dialog.mark_unread || 0
        },

        /**
         * 返回对话未读数量
         * @param dialog
         * @param containSilence    是否包含免打扰消息（true:包含, false:不包含）
         * @returns {*|number}
         */
        getDialogUnread(dialog, containSilence) {
            if (!dialog) {
                return 0
            }
            const unread = (containSilence || !dialog.silence) ? dialog.unread : 0
            return unread || dialog.mark_unread || 0
        },

        /**
         * 返回对话@提及未读数量
         * @param dialog
         * @returns {*|number}
         */
        getDialogMention(dialog) {
            return dialog?.mention || 0
        },

        /**
         * 返回文本信息预览格式
         * @param text
         * @param imgClassName
         * @returns {*}
         */
        getMsgTextPreview(text, imgClassName = null) {
            if (!text) {
                return '';
            }
            //
            text = text.replace(/<img\s+class="emoticon"[^>]*?alt="(\S+)"[^>]*?>/g, "[$1]")
            text = text.replace(/<img\s+class="emoticon"[^>]*?>/g, `[${$A.L('动画表情')}]`)
            if (imgClassName) {
                text = text.replace(/<img\s+class="browse"[^>]*?src="(\S+)"[^>]*?>/g, function (res, src) {
                    const widthMatch = res.match("width=\"(\\d+)\""),
                        heightMatch = res.match("height=\"(\\d+)\"");
                    if (widthMatch && heightMatch) {
                        const width = parseInt(widthMatch[1]),
                            height = parseInt(heightMatch[1]),
                            maxSize = 40;
                        const scale = $A.scaleToScale(width, height, maxSize, maxSize);
                        imgClassName = `${imgClassName}" style="width:${scale.width}px;height:${scale.height}px`
                    }
                    return `[image:${src}]`
                })
            } else {
                text = text.replace(/<img\s+class="browse"[^>]*?>/g, `[${$A.L('图片')}]`)
            }
            text = text
                .replace(/<[^>]+>/g, "")
                .replace(/&nbsp;/g, " ")
                .replace(/&quot;/g, "\"")
                .replace(/&amp;/g, "&")
                .replace(/&lt;/g, "<")
                .replace(/&gt;/g, ">")
            if (imgClassName) {
                text = text.replace(/\[image:(.*?)\]/g, `<img class="${imgClassName}" src="$1">`)
                text = text.replace(/\{\{RemoteURL\}\}/g, this.apiUrl('../'))
            }
            return text
        },

        /**
         * 消息格式化处理（将消息内的RemoteURL换成真实地址）
         * @param data
         */
        formatMsgBasic(data) {
            if (!data) {
                return data
            }
            if ($A.isJson(data)) {
                for (let key in data) {
                    if (!data.hasOwnProperty(key)) continue;
                    data[key] = $A.formatMsgBasic(data[key]);
                }
            } else if ($A.isArray(data)) {
                data.forEach((val, index) => {
                    data[index] = $A.formatMsgBasic(val);
                });
            } else if (typeof data === "string") {
                data = data.replace(/\{\{RemoteURL\}\}/g, this.apiUrl('../'))
            }
            return data
        },

        /**
         * 消息格式化处理
         * @param text
         * @param userid
         * @returns {string|*}
         */
        formatTextMsg(text, userid) {
            if (!text) {
                return ""
            }
            const atReg = new RegExp(`<span class="mention user" data-id="${userid}">`, "g")
            text = text.trim().replace(/(\n\x20*){3,}/g, "\n\n");
            text = text.replace(/&nbsp;/g, ' ')
            text = text.replace(/<p><\/p>/g, '<p><br/></p>')
            text = text.replace(/\{\{RemoteURL\}\}/g, $A.mainUrl())
            text = text.replace(atReg, `<span class="mention me" data-id="${userid}">`)
            // 处理内容连接
            if (/https*:\/\//.test(text)) {
                text = text.split(/(<[^>]*>)/g).map(string => {
                    if (string && !/<[^>]*>/.test(string)) {
                        string = string.replace(/(^|[^'"])((https*:\/\/)((\w|=|\?|\.|\/|&|-|:|\+|%|;|#|@|,|!)+))/g, "$1<a href=\"$2\" target=\"_blank\">$2</a>")
                    }
                    return string;
                }).join("")
            }
            // 处理图片显示尺寸
            const array = text.match(/<img\s+[^>]*?>/g);
            if (array) {
                const widthReg = new RegExp("width=\"(\\d+)\""),
                    heightReg = new RegExp("height=\"(\\d+)\"")
                array.some(res => {
                    const widthMatch = res.match(widthReg),
                        heightMatch = res.match(heightReg);
                    if (widthMatch && heightMatch) {
                        const width = parseInt(widthMatch[1]),
                            height = parseInt(heightMatch[1]),
                            maxSize = res.indexOf("emoticon") > -1 ? 150 : 220; // 跟css中的设置一致
                        const scale = $A.scaleToScale(width, height, maxSize, maxSize);
                        const value = res
                            .replace(widthReg, `original-width="${width}"`)
                            .replace(heightReg, `original-height="${height}" style="width:${scale.width}px;height:${scale.height}px"`)
                        text = text.replace(res, value)
                    } else {
                        text = text.replace(res, `<div class="no-size-image-box">${res}</div>`);
                    }
                })
            }
            return text;
        },

        /**
         * 获取文本消息图片
         * @param text
         * @returns {*[]}
         */
        getTextImagesInfo(text) {
            const baseUrl = $A.mainUrl();
            const array = text.match(new RegExp(`<img[^>]*?>`, "g"));
            const list = [];
            if (array) {
                const srcReg = new RegExp("src=([\"'])([^'\"]*)\\1"),
                    widthReg = new RegExp("(original-)?width=\"(\\d+)\""),
                    heightReg = new RegExp("(original-)?height=\"(\\d+)\"")
                array.some(res => {
                    const srcMatch = res.match(srcReg),
                        widthMatch = res.match(widthReg),
                        heightMatch = res.match(heightReg);
                    if (srcMatch) {
                        list.push({
                            src: srcMatch[2].replace(/\{\{RemoteURL\}\}/g, baseUrl),
                            width: widthMatch ? widthMatch[2] : -1,
                            height: heightMatch ? heightMatch[2] : -1,
                        })
                    }
                })
            }
            return list;
        },

        /**
         * 消息简单描述
         * @param data
         * @param imgClassName
         * @returns {string|*}
         */
        getMsgSimpleDesc(data, imgClassName = null) {
            if ($A.isJson(data)) {
                switch (data.type) {
                    case 'text':
                        return $A.getMsgTextPreview(data.msg.type === 'md' ? MarkdownPreview(data.msg.text) : data.msg.text, imgClassName)
                    case 'vote':
                        return `[${$A.L('投票')}]` + $A.getMsgTextPreview(data.msg.text, imgClassName)
                    case 'word-chain':
                        return `[${$A.L('接龙')}]` + $A.getMsgTextPreview(data.msg.text, imgClassName)
                    case 'record':
                        return `[${$A.L('语音')}]`
                    case 'meeting':
                        return `[${$A.L('会议')}] ${data.msg.name}`
                    case 'file':
                        if (data.msg.type == 'img') {
                            if (imgClassName) {
                                // 缩略图，主要用于回复消息预览
                                const width = parseInt(data.msg.width),
                                    height = parseInt(data.msg.height),
                                    maxSize = 40;
                                const scale = $A.scaleToScale(width, height, maxSize, maxSize);
                                return `<img class="${imgClassName}" style="width:${scale.width}px;height:${scale.height}px" src="${data.msg.thumb}">`
                            }
                            return `[${$A.L('图片')}]`
                        } else if (data.msg.ext == 'mp4') {
                            return `[${$A.L('视频')}]`
                        }
                        return `[${$A.L('文件')}] ${data.msg.name}`
                    case 'tag':
                        return `[${$A.L(data.msg.action === 'remove' ? '取消标注' : '标注')}] ${$A.getMsgSimpleDesc(data.msg.data)}`
                    case 'top':
                        return `[${$A.L(data.msg.action === 'remove' ? '取消置顶' : '置顶')}] ${$A.getMsgSimpleDesc(data.msg.data)}`
                    case 'todo':
                        return `[${$A.L(data.msg.action === 'remove' ? '取消待办' : (data.msg.action === 'done' ? '完成' : '设待办'))}] ${$A.getMsgSimpleDesc(data.msg.data)}`
                    case 'notice':
                        return data.msg.notice
                    default:
                        return `[${$A.L('未知的消息')}]`
                }
            }
            return '';
        },

        /**
         * 获取文件标题
         * @param file
         * @returns {*}
         */
        getFileName(file) {
            let name = file.name || '';
            let ext = file.ext || '';
            if (ext != '') {
                name += "." + ext;
            }
            return name;
        },

        /**
         * 是否是doo服务器
         * @returns {boolean}
         */
        isDooServer() {
            const u = $A.getDomain($A.mainUrl())
            return /dootask\.com$/.test(u)
                || /hitosea\.com$/.test(u)
                || /^127\.0\.0\.1/.test(u)
                || /^(10)\./.test(u)
                || /^(172)\.(1[6-9]|2[0-9]|3[0-1])\./.test(u)
                || /^(192)\.(168)\./.test(u);
        },

        /**
         * 缩略图还原
         * @param url
         * @returns {*|string}
         */
        thumbRestore(url) {
            url = $A.rightDelete(url, '_thumb.jpg')
            url = $A.rightDelete(url, '_thumb.png')
            return url
        },

        /**
         * 拖拽或粘贴的数据是否包含文件夹
         * @param data
         * @returns {boolean}
         */
        dataHasFolder(data) {
            const {items} = data;
            if (items) {
                for (const item of items) {
                    if (item.kind === "directory" || (item.kind === "file" && item.webkitGetAsEntry().isDirectory)) {
                        return true;
                    }
                }
            }
            return false;
        },

        /**
         * 加载 VConsole 日志组件
         * @param key
         */
        loadVConsole(key = undefined) {
            if (typeof key === "string") {
                switch (key) {
                    case 'log.o':
                        $A.IDBSet("logOpen", "open").then(_ => {
                            $A.loadVConsole()
                        });
                        return true;
                    case 'log.c':
                        $A.IDBSet("logOpen", "close").then(_ => {
                            $A.loadVConsole()
                        });
                        return true;
                }
                return false
            }
            $A.IDBString("logOpen").then(r => {
                if (typeof window.vConsole !== "undefined") {
                    window.vConsole.destroy();
                    window.vConsole = null;
                }
                $A.openLog = r === "open"
                if ($A.openLog) {
                    $A.loadScript('js/vconsole.min.js').then(_ => {
                        window.vConsole = new window.VConsole({
                            onReady: () => {
                                console.log('VConsole: onReady');
                            },
                            onClearLog: () => {
                                console.log('VConsole: onClearLog');
                            }
                        });
                    }).catch(_ => {
                        $A.modalError("VConsole 组件加载失败！");
                    })
                }
            })
        }
    });

    /**
     * =============================================================================
     * *****************************   iviewui assist   ****************************
     * =============================================================================
     */
    $.extend({
        // 弹窗
        modalConfig(config) {
            if (typeof config === "undefined") {
                config = {content: "Undefined"};
            } else if (typeof config === "string") {
                config = {content: config};
            }
            config.title = config.title || (typeof config.render === 'undefined' ? '温馨提示' : '');
            config.content = config.content || '';
            config.okText = config.okText || '确定';
            config.cancelText = config.cancelText || '取消';
            if (config.language !== false) {
                delete config.language;
                config.title = $A.L(config.title);
                config.content = $A.L(config.content);
                config.okText = $A.L(config.okText);
                config.cancelText = $A.L(config.cancelText);
            }
            return config;
        },

        modalInput(config, millisecond = 0) {
            if (millisecond > 0) {
                setTimeout(() => { $A.modalInput(config) }, millisecond);
                return;
            }
            if (typeof config === "string") config = {title:config};
            let inputId = "modalInput_" + $A.randomString(6);
            let inputProps = {
                type: config.type || "text",
                value: config.value,
                placeholder: $A.L(config.placeholder),
                elementId: inputId,
            }
            if ($A.isJson(config.inputProps)) {
                inputProps = Object.assign(inputProps, config.inputProps)
            }
            const onOk = () => {
                return new Promise((resolve, reject) => {
                    if (!config.onOk) {
                        reject()    // 没有返回：取消等待
                        return
                    }
                    const call = config.onOk(config.value);
                    if (!call) {
                        resolve()   // 返回无内容：关闭弹窗
                        return
                    }
                    if (call.then) {
                        call.then(msg => {
                            msg && $A.messageSuccess(msg)
                            resolve()
                        }).catch(err => {
                            err && $A.messageError(err)
                            reject()
                        });
                    } else {
                        typeof call === "string" && $A.messageError(call)
                        reject()
                    }
                })
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
                            props: inputProps,
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
                okType: config.okType || 'primary',
                cancelType: config.cancelType || 'text',
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
            config = $A.modalConfig(config);
            if (config.loading) {
                const {onOk} = config;
                config.onOk = () => {
                    return new Promise((resolve, reject) => {
                        if (!onOk) {
                            reject()    // 没有返回：取消等待
                            return
                        }
                        const call = onOk();
                        if (!call) {
                            resolve()   // 返回无内容：关闭弹窗
                            return
                        }
                        if (call.then) {
                            call.then(msg => {
                                msg && $A.messageSuccess(msg)
                                resolve()
                            }).catch(err => {
                                err && $A.messageError(err)
                                reject()
                            });
                        } else {
                            typeof call === "string" && $A.messageError(call)
                            reject()
                        }
                    })
                }
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
            if (typeof config === "string" && config === "Network exception") {
                return;
            }
            if ($A.isJson(config) && config.content === "Network exception") {
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
            if (typeof config === "string" && config === "Network exception") {
                return;
            }
            if ($A.isJson(config) && config.content === "Network exception") {
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
            if (typeof msg === "string" && msg === "Network exception") {
                return;
            }
            $A.Message.warning($A.L(msg));
        },

        messageError(msg) {
            if (typeof msg === "string" && msg === "Network exception") {
                return;
            }
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
                supportMode() {
                    let ua = typeof window !== 'undefined' && window.navigator.userAgent.toLowerCase();
                    if (`${ua.match(/Chrome/i)}` === 'chrome') {
                        return 'chrome';
                    }
                    if (`${ua.match(/Webkit/i)}` === 'webkit') {
                        return 'webkit';
                    }
                    return null;
                },

                defaultFilter() {
                    return '-webkit-filter: invert(100%) hue-rotate(180deg) contrast(90%) !important; ' +
                        'filter: invert(100%) hue-rotate(180deg) contrast(90%) !important;';
                },

                reverseFilter() {
                    return '-webkit-filter: invert(100%) hue-rotate(180deg) contrast(110%) !important; ' +
                        'filter: invert(100%) hue-rotate(180deg) contrast(110%) !important;';
                },

                noneFilter() {
                    return '-webkit-filter: none !important; filter: none !important;';
                },

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

            createDarkStyle() {
                this.utils.addStyle('dark-mode-style', 'style', `
                @media screen {
                    html {
                        ${this.utils.defaultFilter()}
                        will-change: transform;
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
                    .no-dark-mode,
                    .no-dark-content,
                    .no-dark-before:before {
                        ${this.utils.reverseFilter()}
                        will-change: transform;
                    }

                    [style*="background:url"] *,
                    [style*="background-image:url"] *,
                    [style*="background: url"] *,
                    [style*="background-image: url"] *,
                    input,
                    [background] *,
                    .no-dark-content img,
                    .no-dark-content canvas,
                    .no-dark-content svg image {
                        ${this.utils.noneFilter()}
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
                        ${this.utils.noneFilter()}
                    }

                    /* Page background */
                    html {
                        min-width: 100%;
                        min-height: 100%;
                    }
                    .child-view {
                        background-color: #fff;
                    }
                    .page-login {
                        background-color: #f8f8f8;
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
                if (!this.utils.supportMode()) {
                    return;
                }
                if (this.isDarkEnabled()) {
                    return
                }
                this.createDarkStyle();
                this.utils.addClass(document.body, "dark-mode-reverse")
            },

            disableDarkMode() {
                if (!this.isDarkEnabled()) {
                    return
                }
                this.utils.removeElementById('dark-mode-style');
                this.utils.removeClass(document.body, "dark-mode-reverse")
            },

            autoDarkMode() {
                let darkScheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                if ($A.isEEUiApp) {
                    darkScheme = $A.eeuiAppGetThemeName() === "dark"
                }
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
