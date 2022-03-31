<template>
    <div :class="`dialog-view ${msgData.type}`" :data-id="msgData.id">

        <div class="dialog-head">
            <!--详情-->
            <div class="dialog-content">
                <!--文本-->
                <div v-if="msgData.type === 'text'" class="content-text">
                    <pre class="no-dark-mode">{{textMsg(msgData.msg.text)}}</pre>
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
            <EPopover
                v-if="msgData.send > 1 || dialogType == 'group'"
                v-model="popperShow"
                ref="percent"
                class="percent"
                placement="left-end"
                :width="360"
                :offset="-8">
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
                <WCircle slot="reference" :percent="msgData.percentage" :size="14"/>
            </EPopover>
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
        }
    },

    watch: {
        msgData: {
            handler() {
                this.msgRead();
            },
            immediate: true,
        },
        popperShow(val) {
            if (val) {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/readlist',
                    data: {
                        msg_id: this.msgData.id,
                    },
                }).then(({data}) => {
                    this.allList = data;
                    setTimeout(this.$refs.percent.updatePopper, 10)
                }).catch(() => {
                    this.allList = [];
                    setTimeout(this.$refs.percent.updatePopper, 10)
                });
            }
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

        textMsg(text) {
            if (!text) {
                return ""
            }
            text = text.trim().replace(/(\n\x20*){3,}/g, "\n\n");
            return text;
        },

        imageStyle(info) {
            const {width, height} = info;
            if (width && height) {
                let maxW = 180,
                    maxH = 180,
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
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.messageError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        viewFile() {
            const {id, dialog_id, msg} = this.msgData;
            if (['jpg', 'jpeg', 'gif', 'png'].includes(msg.ext)) {
                const list = $A.cloneJSON(this.dialogMsgs.filter(item => {
                    return item.dialog_id === dialog_id && item.type === 'file' && ['jpg', 'jpeg', 'gif', 'png'].includes(item.msg.ext);
                })).sort((a, b) => {
                    return a.id - b.id;
                });
                const index = list.findIndex(item => item.id === id);
                if (index > -1) {
                    this.$store.state.previewImageIndex = index;
                    this.$store.state.previewImageList = list.map(({msg}) => msg.path);
                } else {
                    this.$store.state.previewImageIndex = 0;
                    this.$store.state.previewImageList = [msg.path];
                }
                return
            }
            if (this.$Electron) {
                this.$Electron.sendMessage('windowRouter', {
                    name: 'file-msg-' + this.msgData.id,
                    path: "/single/file/msg/" + this.msgData.id,
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
            } else {
                window.open($A.apiUrl(`../single/file/msg/${this.msgData.id}`))
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
