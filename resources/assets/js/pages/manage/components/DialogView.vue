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
            <div class="time" :title="msgData.created_at">{{$A.formatTime(msgData.created_at)}}</div>

            <div v-if="msgData.send > 1 || dialogType === 'group'" class="percent" @click="openReadPercentage">
                <EPopover
                    v-model="popperShow"
                    ref="percent"
                    placement="left-end"
                    :width="360">
                    <div class="dialog-wrapper-read-poptip-content">
                        <ul class="read overlay-y">
                            <li class="read-title"><em>{{ readList.length }}</em>{{ $L('已读') }}</li>
                            <li v-for="item in readList">
                                <UserAvatar :userid="item.userid" :size="26" showName/>
                            </li>
                        </ul>
                        <ul class="unread overlay-y">
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
        </div>
        <div v-else class="dialog-foot"><Loading/></div>

    </div>
</template>

<script>
import WCircle from "../../../components/WCircle";
import {mapState} from "vuex";

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
    },

    data() {
        return {
            popperLoad: 0,
            popperShow: false,
            allList: [],
        }
    },

    activated() {
        this.msgRead()
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
            let classArray = [];
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
            const array = text.match(/<img\s+[^>]*?>/g);
            if (array) {
                const widthReg = new RegExp("width=\"(\\d+)\"")
                const heightReg = new RegExp("height=\"(\\d+)\"")
                array.some(res => {
                    if (widthReg.test(res) && heightReg.test(res)) {
                        let width = parseInt(res.match(widthReg)[1]),
                            height = parseInt(res.match(heightReg)[1]),
                            maxSize = res.indexOf("emoticon") > -1 ? 150 : 220;
                        let scale = $A.scaleToScale(width, height, maxSize, maxSize);
                        let value = res.replace(widthReg, `width=${scale.width}`).replace(heightReg, `height=${scale.height}`)
                        text = text.replace(res, value)
                    }
                })
            }
            return text;
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
                        this.$store.state.previewImageList = [target.currentSrc];
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
            if (this.$Electron) {
                this.$Electron.sendMessage('windowRouter', {
                    name: `file-msg-${this.msgData.id}`,
                    path: `/single/file/msg/${this.msgData.id}`,
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
                const eeui = requireModuleJs("eeui");
                eeui.openPage({
                    pageType: 'web',
                    url: $A.apiUrl(`../#/single/file/msg/${this.msgData.id}?token=${this.userToken}`)
                }, _ => {});
            } else {
                window.open($A.apiUrl(`../single/file/msg/${this.msgData.id}`))
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
            let list = [];
            data.some(({type, msg}) => {
                if (type === 'file') {
                    list.push(msg.path)
                } else if (type === 'text') {
                    const baseUrl = $A.apiUrl('../');
                    const array = msg.text.match(/<img\s+class="browse"[^>]*?src="(.*?)"[^>]*?>/g);
                    array && array.some(res => {
                        list.push(res.match(/<img\s+class="browse"[^>]*?src="(.*?)"[^>]*?>/)[1].replace(/\{\{RemoteURL\}\}/g, baseUrl))
                    })
                }
            })
            //
            let index = list.findIndex(item => item === currentUrl);
            if (index > -1) {
                this.$store.state.previewImageIndex = index;
                this.$store.state.previewImageList = list;
            } else {
                this.$store.state.previewImageIndex = 0;
                this.$store.state.previewImageList = [currentUrl];
            }
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
