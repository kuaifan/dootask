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
            return $A.mainUrl(`online/preview/${name}?key=${key}&version=${window.systemInfo.version}&__=${$A.dayjs().valueOf()}`)
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
         * 获取日期选择器的 shortcuts 模板参数
         * @returns {(*)[]|[{text, value(): [Date,*]},{text, value(): [Date,*]},{text, value(): [*,*]},{text, value(): [*,*]},{text, value(): [Date,*]},null,null]|(Date|*)[]}
         */
        timeOptionShortcuts() {
            const startSecond = $A.dayjs().startOf('day').toDate();
            return [{
                text: $A.L('今天'),
                value() {
                    return [startSecond, $A.dayjs().endOf('day').toDate()];
                }
            }, {
                text: $A.L('明天'),
                value() {
                    return [startSecond, $A.dayjs().add(1, 'day').endOf('day').toDate()];
                }
            }, {
                text: $A.L('本周'),
                value() {
                    return [startSecond, $A.dayjs().endOf('week').toDate()];
                }
            }, {
                text: $A.L('本月'),
                value() {
                    return [startSecond, $A.dayjs().endOf('month').toDate()];
                }
            }, {
                text: $A.L('3天'),
                value() {
                    return [startSecond, $A.dayjs().add(2, 'day').endOf('day').toDate()];
                }
            }, {
                text: $A.L('5天'),
                value() {
                    return [startSecond, $A.dayjs().add(4, 'day').endOf('day').toDate()];
                }
            }, {
                text: $A.L('7天'),
                value() {
                    return [startSecond, $A.dayjs().add(6, 'day').endOf('day').toDate()];
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
