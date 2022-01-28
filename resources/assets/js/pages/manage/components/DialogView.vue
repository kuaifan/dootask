<template>
    <div :class="`dialog-view ${msgData.type}`" :data-id="msgData.id">

        <!--文本-->
        <div v-if="msgData.type === 'text'" class="dialog-content">
            <pre class="no-dark-mode">{{textMsg(msgData.msg.text)}}</pre>
        </div>

        <!--等待-->
        <div v-else-if="msgData.type === 'loading'" class="dialog-content loading"><Loading/></div>

        <!--文件-->
        <div v-else-if="msgData.type === 'file'" :class="['dialog-content', msgData.msg.type]">
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
            <div class="dialog-file-menu">
                <div class="file-menu-warp"></div>
                <div class="file-menu-icon">
                    <Icon @click="viewFile" type="md-eye" />
                    <Icon @click="downFile" type="md-arrow-round-down" />
                </div>
            </div>
        </div>

        <!--未知-->
        <div v-else class="dialog-content unknown">{{$L("未知的消息类型")}}</div>

        <!--时间/阅读-->
        <div v-if="msgData.created_at" class="dialog-foot">
            <div class="time">{{$A.formatTime(msgData.created_at)}}</div>
            <Poptip
                v-if="msgData.send > 1 || dialogType == 'group'"
                class="percent"
                placement="left-end"
                transfer
                :width="360"
                :offset="8"
                @on-popper-show="popperShow">
                <div slot="content" class="dialog-wrapper-read-poptip-content">
                    <ul class="read">
                        <li class="read-title"><em>{{readList.length}}</em>{{$L('已读')}}</li>
                        <li v-for="item in readList"><UserAvatar :userid="item.userid" :size="26" showName/></li>
                    </ul>
                    <ul class="unread">
                        <li class="read-title"><em>{{unreadList.length}}</em>{{$L('未读')}}</li>
                        <li v-for="item in unreadList"><UserAvatar :userid="item.userid" :size="26" showName/></li>
                    </ul>
                </div>
                <WCircle :percent="msgData.percentage" :size="14"/>
            </Poptip>
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
            read_list: []
        }
    },

    activated() {
        this.msgRead()
    },

    computed: {
        ...mapState(['userToken']),

        readList() {
            return this.read_list.filter(({read_at}) => read_at)
        },

        unreadList() {
            return this.read_list.filter(({read_at}) => !read_at)
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
            this.$nextTick(() => {
                if (!this.$el.offsetParent) {
                    this.msgData._r = false;
                    return
                }
                this.$store.dispatch("dialogMsgRead", this.msgData);
            })
        },

        popperShow() {
            this.$store.dispatch("call", {
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then(({data}) => {
                this.read_list = data;
            }).catch(() => {
                this.read_list = [];
            });
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

        viewFile() {
            if (this.$Electron) {
                this.$Electron.ipcRenderer.send('windowRouter', {
                    title: `${this.msgData.msg.name} (${$A.bytesToSize(this.msgData.msg.size)})`,
                    titleFixed: true,
                    name: 'file-msg-' + this.msgData.id,
                    path: "/single/file/msg/" + this.msgData.id,
                    force: false,
                    config: {
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
                    $A.downFile($A.apiUrl(`dialog/msg/download?msg_id=${this.msgData.id}&token=${this.userToken}`))
                }
            });
        }
    }
}
</script>
