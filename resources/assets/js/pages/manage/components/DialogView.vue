<template>
    <div :class="`dialog-view ${msgData.type}`" :data-id="msgData.id">
        <!--昵称-->
        <div v-if="dialogType === 'group'" class="dialog-username">
            <UserAvatar :userid="msgData.userid" :show-icon="false" :show-name="true" :tooltip-disabled="true"/>
        </div>

        <div class="dialog-head">
            <!--详情-->
            <div class="dialog-content" :class="contentClass">
                <!--文本-->
                <div v-if="msgData.type === 'text'" class="content-text no-dark-content">
                    <pre @click="viewText" v-html="textMsg(msgData.msg.text)"></pre>
                </div>
                <!--文件-->
                <div v-else-if="msgData.type === 'file'" :class="`content-file ${msgData.msg.type}`">
                    <div class="dialog-file">
                        <img v-if="msgData.msg.type === 'img'" class="file-img" :style="imageStyle(msgData.msg)" :src="msgData.msg.thumb" @click="viewFile"/>
                        <div v-else class="file-box">
                            <img class="file-thumb" :src="msgData.msg.thumb"/>
                            <div class="file-info">
                                <div class="file-name">{{msgData.msg.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(msgData.msg.size)}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--录音-->
                <div v-else-if="msgData.type === 'record'" class="content-record no-dark-content">
                    <div class="dialog-record" :class="{playing: recordPlay}" :style="recordStyle(msgData.msg)" @click="playRecord">
                        <div class="record-time">{{recordDuration(msgData.msg.duration)}}</div>
                        <div class="record-icon taskfont"></div>
                    </div>
                </div>
                <!--等待-->
                <div v-else-if="msgData.type === 'loading'" class="content-loading">
                    <Loading/>
                </div>
                <!--未知-->
                <div v-else class="content-unknown">{{$L("未知的消息类型")}}</div>
            </div>

            <!--菜单-->
            <div v-if="showMenu" class="dialog-menu">
                <div class="menu-icon">
                    <Icon v-if="msgData.userid == userId" @click="withdraw" type="md-undo" :title="$L('撤回')"/>
                    <template v-if="msgData.type === 'file'">
                        <Icon @click="viewFile" type="md-eye" :title="$L('查看')"/>
                        <Icon @click="downFile" type="md-arrow-round-down" :title="$L('下载')"/>
                    </template>
                </div>
            </div>
        </div>

        <!--时间/阅读-->
        <div v-if="msgData.created_at" class="dialog-foot">
            <div v-if="timeShow" class="time" @click="timeShow=false">{{msgData.created_at}}</div>
            <div v-else class="time" :title="msgData.created_at" @click="timeShow=true">{{$A.formatTime(msgData.created_at)}}</div>

            <template v-if="!hidePercentage">
                <div v-if="msgData.send > 1 || dialogType === 'group'" class="percent" @click="openReadPercentage">
                    <EPopover
                        v-model="popperShow"
                        ref="percent"
                        popper-class="dialog-wrapper-read-poptip"
                        placement="left-end">
                        <div class="read-poptip-content">
                            <ul class="read scrollbar-overlay">
                                <li class="read-title"><em>{{ readList.length }}</em>{{ $L('已读') }}</li>
                                <li v-for="item in readList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                            <ul class="unread scrollbar-overlay">
                                <li class="read-title"><em>{{ unreadList.length }}</em>{{ $L('未读') }}</li>
                                <li v-for="item in unreadList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                        </div>
                        <div slot="reference"></div>
                    </EPopover>
                    <Loading v-if="popperLoad > 0"/>
                    <WCircle v-else :percent="msgData.percentage" :size="14"/>
                </div>
                <Icon v-else-if="msgData.percentage === 100" class="done" type="md-done-all"/>
                <Icon v-else class="done" type="md-checkmark"/>
            </template>
        </div>
        <div v-else class="dialog-foot"><Loading/></div>

    </div>
</template>

<script>
import WCircle from "../../../components/WCircle";
import {mapState} from "vuex";
import {Store} from "le5le-store";

export default {
    name: "DialogView",
    components: {WCircle},
    props: {
        msgData: {
            type: Object,
            default: () => {
                return {};
            }
        },
        dialogType: {
            type: String,
            default: ''
        },
        hidePercentage: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            popperLoad: 0,
            popperShow: false,
            timeShow: false,
            recordPlay: false,
            allList: [],
        }
    },

    activated() {
        this.msgRead()
    },

    beforeDestroy() {
        Store.set('audioSubscribe', this.msgData.id);
    },

    computed: {
        ...mapState(['userToken', 'userId', 'dialogMsgs']),

        readList() {
            return this.allList.filter(({read_at}) => read_at)
        },

        unreadList() {
            return this.allList.filter(({read_at}) => !read_at)
        },

        showMenu() {
            return this.msgData.userid == this.userId || this.msgData.type === 'file'
        },

        contentClass() {
            const {type, msg} = this.msgData;
            const classArray = [];
            if (type === 'text') {
                if (/^<img\s+class="emoticon"[^>]*?>$/.test(msg.text)) {
                    classArray.push('an-emoticon')
                } else if (/^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){3}\s*<\/p>\s*$/.test(msg.text)) {
                    classArray.push('three-emoji')
                } else if (/^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){2}\s*<\/p>\s*$/.test(msg.text)) {
                    classArray.push('two-emoji')
                } else if (/^\s*<p>\s*[\uD800-\uDBFF][\uDC00-\uDFFF]\s*<\/p>\s*$/.test(msg.text)) {
                    classArray.push('an-emoji')
                }
            }
            return classArray;
        },

        atUserReg() {
            return new RegExp(`<span class="mention user" data-id="${this.userId}">`, "g")
        }
    },

    watch: {
        msgData: {
            handler() {
                this.msgRead();
            },
            immediate: true,
        }
    },

    methods: {
        msgRead() {
            if (this.msgData._r === true) {
                return;
            }
            this.msgData._r = true;
            //
            setTimeout(() => {
                if (!this.$el.offsetParent) {
                    this.msgData._r = false;
                    return
                }
                this.$store.dispatch("dialogMsgRead", this.msgData);
            }, 50)
        },

        openReadPercentage() {
            if (this.popperLoad > 0) {
                return;
            }
            if (this.popperShow) {
                this.popperShow = false;
                return;
            }
            this.popperLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then(({data}) => {
                this.allList = data;
            }).catch(() => {
                this.allList = [];
            }).finally(_ => {
                setTimeout(() => {
                    this.popperLoad--;
                    this.popperShow = true
                }, 100)
            });
        },

        textMsg(text) {
            if (!text) {
                return ""
            }
            text = text.trim().replace(/(\n\x20*){3,}/g, "\n\n");
            text = text.replace(/\{\{RemoteURL\}\}/g, $A.apiUrl('../'))
            text = text.replace(/<p><\/p>/g, '<p><br/></p>')
            text = text.replace(this.atUserReg, `<span class="mention me" data-id="${this.userId}">`)
            // 处理内容连接
            if (/https*:\/\//.test(text)) {
                text = text.split(/(<[^>]*>)/g).map(string => {
                    if (string && !/<[^>]*>/.test(string)) {
                        string = string.replace(/(https*:\/\/)((\w|=|\?|\.|\/|&|-|:|\+|%|;)+)/g, "<a href=\"$1$2\" target=\"_blank\">$1$2</a>")
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
                            maxSize = res.indexOf("emoticon") > -1 ? 150 : 220;
                        const scale = $A.scaleToScale(width, height, maxSize, maxSize);
                        const value = res
                            .replace(widthReg, `original-width="${width}" width="${scale.width}"`)
                            .replace(heightReg, `original-height="${height}" height="${scale.height}"`)
                        text = text.replace(res, value)
                    }
                })
            }
            return text;
        },

        recordStyle(info) {
            const {duration} = info;
            const width = 50 + Math.min(180, Math.floor(duration / 150));
            return {
                width: width + 'px',
            };
        },

        recordDuration(duration) {
            const minute = Math.floor(duration / 60000),
                seconds = Math.floor(duration / 1000) % 60;
            if (minute > 0) {
                return `${minute}:${seconds}″`
            }
            return `${Math.max(1, seconds)}″`
        },

        imageStyle(info) {
            const {width, height} = info;
            if (width && height) {
                let maxW = 220,
                    maxH = 220,
                    tempW = width,
                    tempH = height;
                if (width > maxW || height > maxH) {
                    if (width > height) {
                        tempW = maxW;
                        tempH = height * (maxW / width);
                    } else {
                        tempW = width * (maxH / height);
                        tempH = maxH;
                    }
                }
                return {
                    width: tempW + 'px',
                    height: tempH + 'px',
                };
            }
            return {};
        },

        playRecord() {
            Store.set('audioSubscribe', {
                id: this.msgData.id,
                src: this.msgData.msg.path,
                callback: (play) => {
                    this.recordPlay = play;
                }
            });
        },

        withdraw() {
            $A.modalConfirm({
                content: `确定撤回此信息吗？`,
                okText: '撤回',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'dialog/msg/withdraw',
                        data: {
                            msg_id: this.msgData.id
                        },
                    }).then(() => {
                        $A.messageSuccess("消息已撤回");
                        this.$store.dispatch("forgetDialogMsg", this.msgData.id);
                    }).catch(({msg}) => {
                        $A.messageError(msg, 301);
                    }).finally(_ => {
                        this.$Modal.remove();
                    });
                }
            });
        },

        viewText({target}) {
            switch (target.nodeName) {
                case "IMG":
                    if (target.classList.contains('browse')) {
                        this.viewPicture(target.currentSrc);
                    } else {
                        this.$store.state.previewImageIndex = 0;
                        this.$store.state.previewImageList = this.getTextImageInfos(target.outerHTML);
                    }
                    break;

                case "SPAN":
                    if (target.classList.contains('mention') && target.classList.contains('task')) {
                        this.$store.dispatch("openTask", $A.runNum(target.getAttribute("data-id")));
                    }
                    break;
            }
        },

        viewFile() {
            const {msg} = this.msgData;
            if (['jpg', 'jpeg', 'gif', 'png'].includes(msg.ext)) {
                this.viewPicture(msg.path);
                return
            }
            const uri = `/single/file/msg/${this.msgData.id}`;
            if (this.$Electron) {
                this.$Electron.sendMessage('windowRouter', {
                    name: `file-msg-${this.msgData.id}`,
                    path: uri,
                    userAgent: "/hideenOfficeTitle/",
                    force: false,
                    config: {
                        title: `${this.msgData.msg.name} (${$A.bytesToSize(this.msgData.msg.size)})`,
                        titleFixed: true,
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    }
                });
            } else if (this.$isEEUiApp) {
                $A.eeuiAppOpenPage({
                    pageType: 'web',
                    pageTitle: `${this.msgData.msg.name} (${$A.bytesToSize(this.msgData.msg.size)})`,
                    statusBarStyle: false,
                    url: $A.apiUrl(`../token?token=${this.userToken}&from=${encodeURIComponent($A.apiUrl(`..${uri}`))}`)
                });
            } else {
                window.open($A.apiUrl(`..${uri}`))
            }
        },

        viewPicture(currentUrl) {
            const {dialog_id} = this.msgData;
            const data = $A.cloneJSON(this.dialogMsgs.filter(item => {
                if (item.dialog_id === dialog_id) {
                    if (item.type === 'file') {
                        return ['jpg', 'jpeg', 'gif', 'png'].includes(item.msg.ext);
                    } else if (item.type === 'text') {
                        return item.msg.text.match(/<img\s+class="browse"[^>]*?>/);
                    }
                }
                return false;
            })).sort((a, b) => {
                return a.id - b.id;
            });
            //
            const list = [];
            data.some(({type, msg}) => {
                if (type === 'file') {
                    list.push({
                        src: msg.path,
                        width: msg.width,
                        height: msg.height,
                    })
                } else if (type === 'text') {
                    list.push(...this.getTextImageInfos(msg.text))
                }
            })
            //
            const index = list.findIndex(({src}) => src === currentUrl);
            if (index > -1) {
                this.$store.state.previewImageIndex = index;
                this.$store.state.previewImageList = list;
            } else {
                this.$store.state.previewImageIndex = 0;
                this.$store.state.previewImageList = [currentUrl];
            }
        },

        getTextImageInfos(text) {
            const baseUrl = $A.apiUrl('../');
            const array = text.match(new RegExp(`<img[^>]*?>`, "g"));
            const list = [];
            if (array) {
                const srcReg = new RegExp("src=\"(.*?)\""),
                    widthReg = new RegExp("(original-)?width=\"(\\d+)\""),
                    heightReg = new RegExp("(original-)?height=\"(\\d+)\"")
                array.some(res => {
                    const srcMatch = res.match(srcReg),
                        widthMatch = res.match(widthReg),
                        heightMatch = res.match(heightReg);
                    if (srcMatch && widthMatch && heightMatch) {
                        list.push({
                            src: srcMatch[1].replace(/\{\{RemoteURL\}\}/g, baseUrl),
                            width: widthMatch[2],
                            height: heightMatch[2],
                        })
                    }
                })
            }
            return list;
        },

        downFile() {
            $A.modalConfirm({
                title: '下载文件',
                content: `${this.msgData.msg.name} (${$A.bytesToSize(this.msgData.msg.size)})`,
                okText: '立即下载',
                onOk: () => {
                    this.$store.dispatch('downUrl', $A.apiUrl(`dialog/msg/download?msg_id=${this.msgData.id}`))
                }
            });
        }
    }
}
</script>
